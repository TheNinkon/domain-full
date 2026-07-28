<?php

namespace App\Http\Controllers\settings;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\MailSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class MailSettingController extends Controller
{
    public function edit(): View
    {
        return view('content.settings.mail', [
            'settings' => MailSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = MailSetting::current();

        // Keep the current password unless a new one was typed (never re-display it).
        if (blank($validated['password'])) {
            unset($validated['password']);
        }

        $settings->update($validated);

        return redirect()->route('settings.mail.edit')->with('success', 'Configuración de email guardada.');
    }

    public function test(Request $request): RedirectResponse
    {
        $settings = MailSetting::current();

        if (! $settings->isConfigured()) {
            return back()->with('error', 'Completa al menos host, puerto y "from address" antes de probar.');
        }

        try {
            Mail::to($request->user()->email)->send(new TestMail());
        } catch (Throwable $e) {
            return back()->with('error', 'No se pudo enviar el email de prueba: ' . $e->getMessage());
        }

        return back()->with('success', 'Email de prueba enviado a ' . $request->user()->email . '.');
    }
}
