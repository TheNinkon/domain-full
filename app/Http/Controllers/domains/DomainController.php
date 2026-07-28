<?php

namespace App\Http\Controllers\domains;

use App\Enums\DomainLogType;
use App\Enums\DomainPriority;
use App\Enums\DomainStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainCategory;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function index(Request $request): View
    {
        $query = Domain::with(['category', 'project']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->where('domain_category_id', $request->integer('category'));
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->string('q') . '%');
        }

        if ($request->filled('expiring')) {
            $query->whereBetween('expiration_date', [now(), now()->addDays($request->integer('expiring'))]);
        }

        $domains = $query->orderBy('expiration_date')->paginate(15)->withQueryString();

        return view('content.domains.index', [
            'domains' => $domains,
            'categories' => DomainCategory::orderBy('name')->get(),
            'statuses' => DomainStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('content.domains.create', [
            'categories' => DomainCategory::orderBy('name')->get(),
            'priorities' => DomainPriority::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateDomain($request);

        $domain = Domain::create($validated + ['status' => DomainStatus::Watching->value]);

        $domain->logs()->create([
            'user_id' => $request->user()->id,
            'type' => DomainLogType::System->value,
            'description' => 'Dominio agregado a la cartera.',
        ]);

        return redirect()->route('domains.show', $domain)->with('success', 'Dominio creado correctamente.');
    }

    public function show(Domain $domain): View
    {
        $domain->load(['category', 'project', 'logs.user', 'offers']);

        $statsByDate = $domain->dailyStats()
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->get()
            ->keyBy(fn ($stat) => $stat->date->toDateString());

        $last30Days = collect(range(29, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->toDateString());

        return view('content.domains.show', [
            'domain' => $domain,
            'projects' => Project::orderBy('name')->get(),
            'statuses' => DomainStatus::cases(),
            'visitChartLabels' => $last30Days->map(fn ($date) => Carbon::parse($date)->format('d/m')),
            'visitChartSeries' => $last30Days->map(fn ($date) => $statsByDate->get($date)?->visits ?? 0),
            'totalVisits' => $domain->dailyStats()->sum('visits'),
        ]);
    }

    public function edit(Domain $domain): View
    {
        return view('content.domains.edit', [
            'domain' => $domain,
            'categories' => DomainCategory::orderBy('name')->get(),
            'priorities' => DomainPriority::cases(),
        ]);
    }

    public function update(Request $request, Domain $domain): RedirectResponse
    {
        $validated = $this->validateDomain($request, $domain);

        $this->logFieldChanges($domain, $validated, $request->user()->id);

        $domain->update($validated);

        return redirect()->route('domains.show', $domain)->with('success', 'Dominio actualizado correctamente.');
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->delete();

        return redirect()->route('domains.index')->with('success', 'Dominio eliminado.');
    }

    public function storeNote(Request $request, Domain $domain): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string'],
        ]);

        $domain->logs()->create([
            'user_id' => $request->user()->id,
            'type' => DomainLogType::Note->value,
            'description' => $validated['description'],
        ]);

        return redirect()->route('domains.show', $domain)->with('success', 'Nota agregada.');
    }

    public function updateStatus(Request $request, Domain $domain): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_column(DomainStatus::cases(), 'value'))],
            'project_id' => ['nullable', 'exists:projects,id'],
            'new_project_name' => ['nullable', 'string', 'max:255'],
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === DomainStatus::ActiveProject->value) {
            if (! empty($validated['new_project_name'])) {
                $project = Project::create([
                    'name' => $validated['new_project_name'],
                    'status' => ProjectStatus::InProgress->value,
                ]);
                $domain->project_id = $project->id;
            } elseif (! empty($validated['project_id'])) {
                $domain->project_id = $validated['project_id'];
            } else {
                return back()
                    ->withErrors(['project_id' => 'Selecciona un proyecto existente o escribe el nombre de uno nuevo.'])
                    ->withInput();
            }
        }

        $oldStatus = $domain->status;

        if ($oldStatus->value !== $newStatus) {
            $domain->logs()->create([
                'user_id' => $request->user()->id,
                'type' => DomainLogType::StatusChange->value,
                'description' => sprintf(
                    'Estado cambiado de "%s" a "%s".',
                    $oldStatus->label(),
                    DomainStatus::from($newStatus)->label()
                ),
                'meta' => ['from' => $oldStatus->value, 'to' => $newStatus],
            ]);
        }

        $domain->status = $newStatus;
        $domain->is_for_sale = $newStatus === DomainStatus::ForSale->value;
        $domain->save();

        return redirect()->route('domains.show', $domain)->with('success', 'Estado actualizado.');
    }

    /**
     * Renewal is a recurring, standalone action instead of something set at
     * creation time: you don't know next year's price or provider when you
     * first buy the domain. Each call here logs a full historical record
     * (previous/new registrar, previous/new expiration, cost) and supports
     * transferring to a different registrar as part of the same renewal.
     */
    public function renew(Request $request, Domain $domain): RedirectResponse
    {
        $validated = $request->validate([
            'registrar' => ['required', 'string', 'max:255'],
            'expiration_date' => ['required', 'date', 'after:' . $domain->expiration_date->toDateString()],
            'renewal_cost' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $oldRegistrar = $domain->registrar;
        $oldExpiration = $domain->expiration_date;
        $transferred = filled($oldRegistrar) && $oldRegistrar !== $validated['registrar'];

        $domain->registrar = $validated['registrar'];
        $domain->expiration_date = $validated['expiration_date'];
        $domain->renewal_date = now();
        $domain->renewal_cost = $validated['renewal_cost'];
        $domain->currency = $validated['currency'];

        if (in_array($domain->status, [DomainStatus::Expired, DomainStatus::PendingRenewal], true)) {
            $domain->status = DomainStatus::Watching;
        }

        $domain->save();

        $domain->logs()->create([
            'user_id' => $request->user()->id,
            'type' => DomainLogType::Renewal->value,
            'description' => sprintf(
                'Dominio renovado%s. Nuevo vencimiento: %s. Costo: %s %s.',
                $transferred ? " (transferido de \"$oldRegistrar\" a \"{$validated['registrar']}\")" : '',
                Carbon::parse($validated['expiration_date'])->format('d/m/Y'),
                $validated['currency'],
                number_format((float) $validated['renewal_cost'], 2)
            ),
            'meta' => [
                'from_expiration' => optional($oldExpiration)->toDateString(),
                'to_expiration' => $validated['expiration_date'],
                'from_registrar' => $oldRegistrar,
                'to_registrar' => $validated['registrar'],
                'cost' => $validated['renewal_cost'],
                'currency' => $validated['currency'],
            ],
        ]);

        return redirect()->route('domains.show', $domain)->with('success', 'Renovación registrada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDomain(Request $request, ?Domain $domain = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                'unique:domains,name' . ($domain ? ',' . $domain->id : ''),
            ],
            'domain_category_id' => ['nullable', 'exists:domain_categories,id'],
            'registrar' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:' . implode(',', array_column(DomainPriority::cases(), 'value'))],
            'purchase_date' => ['required', 'date'],
            'expiration_date' => ['required', 'date', 'after_or_equal:purchase_date'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'auto_renew' => ['sometimes', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]) + ['auto_renew' => $request->boolean('auto_renew')];
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function logFieldChanges(Domain $domain, array $validated, int $userId): void
    {
        $costFields = ['purchase_cost'];
        $dateFields = ['expiration_date'];

        foreach ($costFields as $field) {
            $old = $domain->getOriginal($field);
            $new = $validated[$field] ?? null;

            if ((string) $old !== (string) $new) {
                $domain->logs()->create([
                    'user_id' => $userId,
                    'type' => DomainLogType::PriceChange->value,
                    'description' => sprintf('Costo "%s" actualizado de %s a %s.', $field, $old ?? '—', $new ?? '—'),
                    'meta' => ['field' => $field, 'from' => $old, 'to' => $new],
                ]);
            }
        }

        foreach ($dateFields as $field) {
            $old = optional($domain->{$field})->toDateString();
            $new = $validated[$field] ?? null;

            if ($old !== $new) {
                $domain->logs()->create([
                    'user_id' => $userId,
                    'type' => DomainLogType::Renewal->value,
                    'description' => sprintf('Fecha "%s" actualizada de %s a %s.', $field, $old ?? '—', $new ?? '—'),
                    'meta' => ['field' => $field, 'from' => $old, 'to' => $new],
                ]);
            }
        }
    }
}
