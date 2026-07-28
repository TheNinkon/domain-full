<?php

namespace App\Console\Commands;

use App\Mail\DomainsExpiringSoonMail;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckDomainExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un aviso por email si hay dominios que vencen en los próximos 7 días';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $domains = Domain::whereBetween('expiration_date', [now(), now()->addDays(7)])
            ->orderBy('expiration_date')
            ->get();

        if ($domains->isEmpty()) {
            $this->info('No hay dominios por vencer en los próximos 7 días.');

            return self::SUCCESS;
        }

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new DomainsExpiringSoonMail($domains));
        }

        $this->info(sprintf(
            'Aviso enviado: %d dominio(s) por vencer, a %d administrador(es).',
            $domains->count(),
            $admins->count()
        ));

        return self::SUCCESS;
    }
}
