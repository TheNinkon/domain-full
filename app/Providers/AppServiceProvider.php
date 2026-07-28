<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This theme is Bootstrap 5, not Tailwind — Laravel's default pagination
        // view is Tailwind-styled and renders unstyled/broken here otherwise.
        Paginator::useBootstrapFive();

        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                ];
            }
            return [];
        });

        $this->applyMailSettingsFromDatabase();
    }

    /**
     * Override the .env-based mail config with whatever the admin configured in
     * /settings/mail, if any. Lets SMTP be set up from the UI instead of editing
     * the server's .env file. See docs/06-roadmap.md (Milestone 9).
     */
    private function applyMailSettingsFromDatabase(): void
    {
        if (! Schema::hasTable('mail_settings')) {
            return;
        }

        $settings = MailSetting::query()->find(1);

        if (! $settings || ! $settings->isConfigured()) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->password,
            'mail.mailers.smtp.encryption' => $settings->encryption ?: null,
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name ?: config('mail.from.name'),
        ]);
    }
}
