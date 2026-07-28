<?php

namespace App\Http\Controllers\marketplace;

use App\Enums\DomainLogType;
use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Mail\OfferReceivedMail;
use App\Models\CaptchaSetting;
use App\Models\Domain;
use App\Models\DomainDailyStat;
use App\Models\DomainOffer;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * No session/CSRF is available here: ResolveMarketplaceHost intercepts these
 * requests before the 'web' middleware group runs (see docs/06-roadmap.md,
 * Milestone 8). So instead of redirect()->with(...) / withErrors(), actions
 * render the relevant view directly with whatever state applies.
 */
class MarketplaceLandingController extends Controller
{
    public function show(Request $request): View
    {
        $domain = $this->resolveDomain($request);

        $this->recordVisit($domain, $request);

        return $this->render($request, $domain);
    }

    public function metrics(Request $request): View
    {
        $domain = $this->resolveDomain($request);

        $statsByDate = $domain->dailyStats()
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->get()
            ->keyBy(fn ($stat) => $stat->date->toDateString());

        $last30Days = collect(range(29, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->toDateString());

        return view('content.marketplace.metrics', [
            'domain' => $domain,
            'siteRoot' => $this->siteRoot($request),
            'totalVisits' => $domain->dailyStats()->sum('visits'),
            'visitsThisMonth' => $domain->dailyStats()
                ->whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('visits'),
            'chartLabels' => $last30Days->map(fn ($date) => Carbon::parse($date)->format('M d')),
            'chartSeries' => $last30Days->map(fn ($date) => $statsByDate->get($date)?->visits ?? 0),
        ]);
    }

    /**
     * Per-domain favicon: same idea as the sidebar "logo" (initial letter,
     * same brand gradient) instead of Domain Manager's own icon — this page
     * represents the domain being sold, not the admin app. Served as SVG so
     * there's nothing to generate/store per domain; it's built on the fly
     * from the domain's own name.
     */
    public function favicon(Request $request): Response
    {
        $domain = $this->resolveDomain($request);
        $initial = htmlspecialchars(mb_strtoupper(mb_substr($domain->name, 0, 1)), ENT_XML1);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
          <defs>
            <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#696cff"/>
              <stop offset="100%" stop-color="#8f92ff"/>
            </linearGradient>
          </defs>
          <rect width="100" height="100" rx="22" fill="url(#g)"/>
          <text x="50" y="54" text-anchor="middle" dominant-baseline="middle"
                font-family="Arial, Helvetica, sans-serif" font-size="52" font-weight="700"
                fill="#ffffff">{$initial}</text>
        </svg>
        SVG;

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function offers(Request $request): View
    {
        $domain = $this->resolveDomain($request);

        $offers = $domain->offers()
            ->where('status', '!=', OfferStatus::Rejected->value)
            ->get();

        return view('content.marketplace.offers', [
            'domain' => $domain,
            'siteRoot' => $this->siteRoot($request),
            'offers' => $offers,
        ]);
    }

    public function storeOffer(Request $request): View
    {
        $domain = $this->resolveDomain($request);

        // Honeypot: real visitors never fill this hidden field, bots often do.
        // Pretend it worked so bots don't learn anything from the response.
        if (filled($request->input('website'))) {
            return $this->render($request, $domain, offerSubmitted: true);
        }

        $rateLimitKey = 'domain-offer:' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return $this->render($request, $domain, errors: ['Too many offers submitted. Please try again later.'], old: $request->all());
        }

        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:1'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->render($request, $domain, errors: $validator->errors()->all(), old: $request->all());
        }

        if (! $this->verifyCaptcha($request)) {
            return $this->render($request, $domain, errors: ['Please complete the "I\'m not a robot" verification.'], old: $request->all());
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $offer = $domain->offers()->create($validator->validated() + [
            'currency' => 'USD',
            'status' => OfferStatus::Pending->value,
            'ip_address' => $request->ip(),
        ]);

        $domain->logs()->create([
            'type' => DomainLogType::OfferReceived->value,
            'description' => sprintf(
                'Nueva oferta de %s: %s %s.',
                $offer->name ?: $offer->email,
                $offer->currency,
                number_format((float) $offer->amount, 2)
            ),
        ]);

        $this->notifyAdmins($domain, $offer);

        return $this->render($request, $domain, offerSubmitted: true);
    }

    private function render(Request $request, Domain $domain, bool $offerSubmitted = false, array $errors = [], array $old = []): View
    {
        $offers = $domain->offers()->where('status', '!=', OfferStatus::Rejected->value)->get();

        $visitsThisMonth = $domain->dailyStats()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('visits');

        return view('content.marketplace.landing', [
            'domain' => $domain,
            'siteRoot' => $this->siteRoot($request),
            'offerCount' => $offers->count(),
            'averageOffer' => $offers->isNotEmpty() ? $offers->avg('amount') : null,
            'visitsThisMonth' => $visitsThisMonth,
            'offerSubmitted' => $offerSubmitted,
            'formErrors' => $errors,
            'old' => $old,
            'captchaSiteKey' => CaptchaSetting::current()->isConfigured() ? CaptchaSetting::current()->site_key : null,
        ]);
    }

    private function resolveDomain(Request $request): Domain
    {
        return Domain::where('name', $request->getHost())
            ->where('is_for_sale', true)
            ->firstOrFail();
    }

    private function notifyAdmins(Domain $domain, DomainOffer $offer): void
    {
        User::where('role', 'admin')->get()->each(
            fn (User $admin) => Mail::to($admin->email)->send(new OfferReceivedMail($domain, $offer))
        );
    }

    /**
     * reCAPTCHA v3: no checkbox, the token comes from an invisible
     * grecaptcha.execute() call the form's JS runs on submit (see
     * landing.blade.php). Google's response includes a 0.0-1.0 "score"
     * instead of a plain pass/fail — 0.5 is Google's own suggested cutoff.
     * Skips verification entirely if no reCAPTCHA is configured yet, so the
     * form keeps working before the admin sets it up (see /settings/captcha).
     */
    private function verifyCaptcha(Request $request): bool
    {
        $settings = CaptchaSetting::current();

        if (! $settings->isConfigured()) {
            return true;
        }

        $token = (string) $request->input('g-recaptcha-response');

        if ($token === '') {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $settings->secret_key,
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (! ($response->json('success') ?? false)) {
            return false;
        }

        return (float) ($response->json('score') ?? 0) >= 0.5;
    }

    /**
     * Simple, cookie-free page view counter. Skips known bots/crawlers and
     * scripted clients. "Unique" is approximated with a same-day cache key
     * keyed by IP + User-Agent — good enough for a low-traffic MVP, no
     * fingerprinting or tracking cookies involved.
     */
    private function recordVisit(Domain $domain, Request $request): void
    {
        if ($this->isBot($request->userAgent())) {
            return;
        }

        $today = now()->toDateString();
        $visitorKey = 'domain-visit:' . $domain->id . ':' . sha1($request->ip() . '|' . $request->userAgent() . '|' . $today);
        $isNewVisitor = ! Cache::has($visitorKey);
        Cache::put($visitorKey, true, now()->endOfDay());

        $stat = DomainDailyStat::firstOrCreate(
            ['domain_id' => $domain->id, 'date' => $today],
            ['visits' => 0, 'unique_visitors' => 0]
        );

        $stat->increment('visits');

        if ($isNewVisitor) {
            $stat->increment('unique_visitors');
        }
    }

    private function isBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return true;
        }

        return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|curl|wget/i', $userAgent);
    }

    /**
     * Absolute base ("scheme://host" + subdirectory if any, e.g. this local
     * setup's "/domain/public") for the CURRENT request. Used for every link
     * on marketplace pages instead of hardcoded root-relative paths like
     * "/metrics" — those break here (app lives under a subdirectory) even
     * though they'd work fine on a real production domain at its own root.
     */
    private function siteRoot(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');
    }
}
