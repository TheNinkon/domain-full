<?php

namespace App\Http\Controllers\settings;

use App\Http\Controllers\Controller;
use App\Models\CaptchaSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaptchaSettingController extends Controller
{
    public function edit(): View
    {
        return view('content.settings.captcha', [
            'settings' => CaptchaSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = CaptchaSetting::current();

        // Keep the current secret unless a new one was typed (never re-display it).
        if (blank($validated['secret_key'])) {
            unset($validated['secret_key']);
        }

        $settings->update($validated);

        return redirect()->route('settings.captcha.edit')->with('success', 'Configuración de reCAPTCHA guardada.');
    }
}
