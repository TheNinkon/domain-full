<?php

namespace App\Console\Commands;

use App\Enums\DomainLogType;
use App\Enums\DomainPriority;
use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One-off import of the user's real domain portfolio, previously tracked in
 * Notion, into this app. Source: notion/ExportBlock-.../Part-1 (CSV + one
 * markdown file per domain with extra context). Meant to be run once; safe
 * to delete after a successful run.
 */
class ImportNotionDomains extends Command
{
    protected $signature = 'domains:import-notion';

    protected $description = 'Importa la cartera real de dominios exportada desde Notion';

    private const SPANISH_MONTHS = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    public function handle(): int
    {
        $baseDir = base_path('notion/ExportBlock-04126a56-dc2a-4dca-8af4-ca33eeefaa56-Part-1');
        $csvPath = $baseDir . '/DOMAINS AND PROJECTS 615de06ace67831ca9258158aee33799_all.csv';
        $mdDir = $baseDir . '/DOMAINS AND PROJECTS';

        if (! is_file($csvPath)) {
            $this->error("No se encontró el CSV en: $csvPath");

            return self::FAILURE;
        }

        $notes = $this->indexMarkdownFiles($mdDir);
        $admin = User::where('role', 'admin')->first();

        $rows = array_map('str_getcsv', file($csvPath));
        $header = array_map(fn ($col) => trim($col, "\xEF\xBB\xBF"), array_shift($rows));

        $imported = 0;

        foreach ($rows as $row) {
            if (count($row) < count($header)) {
                continue;
            }

            $data = array_combine($header, $row);
            $name = strtolower(trim($data['NOMBRE DE DOMINIO']));

            if ($name === '') {
                continue;
            }

            $domain = $this->importRow($data, $name, $notes[strtoupper($name)] ?? null, $admin);
            $imported++;

            $this->info("Importado: {$domain->name} ({$domain->status->label()}, prioridad {$domain->priority->label()})");
        }

        $this->newLine();
        $this->info("Listo: $imported dominios importados.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{why: ?string, hasRenewalHistory: bool}>
     */
    private function indexMarkdownFiles(string $mdDir): array
    {
        $index = [];

        foreach (glob($mdDir . '/*.md') as $file) {
            $content = file_get_contents($file);

            if (! preg_match('/^#\s+(.+)$/m', $content, $titleMatch)) {
                continue;
            }

            $domainName = strtoupper(trim($titleMatch[1]));

            $why = null;
            if (preg_match('/#\s+\*\*POR QUE COMPRASTE ESTE DOMINIO\*\*\s*\n(.*)/s', $content, $whyMatch)) {
                $why = trim(preg_replace('/\n{3,}/', "\n\n", $whyMatch[1]));
                $why = trim(preg_replace('/^-{3,}\s*$/m', '', $why));
                $why = trim($why);
                $why = $why === '' ? null : $why;
            }

            $index[$domainName] = [
                'why' => $why,
                'hasRenewalHistory' => str_contains($content, 'RENOVACIONES'),
            ];
        }

        return $index;
    }

    /**
     * @param array<string, string> $data
     * @param array{why: ?string, hasRenewalHistory: bool}|null $note
     */
    private function importRow(array $data, string $name, ?array $note, ?User $admin): Domain
    {
        $estado = trim($data['ESTADO'] ?? '');
        $ads = trim($data['ADS'] ?? '');
        $isForSale = $ads === 'En Venta';

        $status = match (true) {
            $isForSale => DomainStatus::ForSale,
            $estado === 'Vencido' => DomainStatus::Expired,
            default => DomainStatus::Watching, // Activo, Por Comprar, Sin Verificar
        };

        $purchaseDate = $this->parseSpanishDate($data['FECHA DE COMPRA'] ?? '') ?? now();
        $expirationDate = $this->parseSpanishDate($data['FECHA DE RENOVACION'] ?? '') ?? $purchaseDate->copy()->addYear();

        [$cost, $currency] = $this->parseCost($data['VALOR DE LA COMPRA'] ?? '');

        $priority = match (strtolower(trim($data['PRIORIDAD'] ?? ''))) {
            'low' => DomainPriority::Low,
            'high' => DomainPriority::High,
            default => DomainPriority::Medium,
        };

        $notesParts = [];
        if ($estado === 'Por Comprar') {
            $notesParts[] = 'PENDIENTE DE COMPRA (importado desde Notion, no confirmado como comprado).';
        }
        if ($estado === 'Sin Verificar') {
            $notesParts[] = 'SIN VERIFICAR (importado desde Notion, revisar estado real).';
        }
        if (filled($data['NOTE'] ?? null)) {
            $notesParts[] = trim($data['NOTE']);
        }

        $domain = Domain::updateOrCreate(
            ['name' => $name],
            [
                'registrar' => $this->parseRegistrar($data['URL'] ?? ''),
                'status' => $status->value,
                'priority' => $priority->value,
                'purchase_date' => $purchaseDate,
                'renewal_date' => $expirationDate,
                'expiration_date' => $expirationDate,
                'purchase_cost' => $cost,
                'renewal_cost' => null,
                'currency' => $currency,
                'notes' => $notesParts ? implode(' ', $notesParts) : null,
                'is_for_sale' => $isForSale,
                'auto_renew' => true,
            ]
        );

        if ($domain->wasRecentlyCreated) {
            $domain->logs()->create([
                'user_id' => $admin?->id,
                'type' => DomainLogType::System->value,
                'description' => 'Dominio importado desde Notion.',
            ]);

            if ($note && $note['why']) {
                $log = $domain->logs()->create([
                    'user_id' => $admin?->id,
                    'type' => DomainLogType::Note->value,
                    'description' => $note['why'],
                ]);
                $log->forceFill(['created_at' => $purchaseDate])->save();
            }

            if ($name === 'mariachis.co') {
                $log = $domain->logs()->create([
                    'user_id' => $admin?->id,
                    'type' => DomainLogType::Renewal->value,
                    'description' => 'Renovado el 29/04/2026 por 250.000 COP. Nueva fecha de vencimiento: 29/04/2027.',
                    'meta' => ['field' => 'expiration_date', 'from' => '2026-04-29', 'to' => '2027-04-29', 'cost' => '250000 COP'],
                ]);
                $log->forceFill(['created_at' => Carbon::create(2026, 4, 29)])->save();
            }
        }

        return $domain;
    }

    private function parseSpanishDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/(\d{1,2}) de (\w+) de (\d{4})/u', $value, $m)) {
            return null;
        }

        $month = self::SPANISH_MONTHS[Str::lower($m[2])] ?? null;

        if (! $month) {
            return null;
        }

        return Carbon::create((int) $m[3], $month, (int) $m[1]);
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function parseCost(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [0.0, 'USD'];
        }

        if (preg_match('/^([\d.,]+)\s*([A-Za-z]{3})$/', $value, $m)) {
            $amount = (float) str_replace(['.', ','], '', $m[1]);

            return [$amount, strtoupper($m[2])];
        }

        $amount = (float) preg_replace('/[^\d.]/', '', $value);

        return [$amount, 'USD'];
    }

    private function parseRegistrar(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        return match (true) {
            str_contains($url, 'namecheap') => 'Namecheap',
            str_contains($url, 'godaddy') => 'GoDaddy',
            str_contains($url, 'ionos') => 'IONOS',
            str_contains($url, 'arsys') => 'Arsys',
            default => parse_url($url, PHP_URL_HOST) ?: $url,
        };
    }
}
