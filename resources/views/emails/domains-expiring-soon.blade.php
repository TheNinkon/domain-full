<x-mail::message>
# Dominios por vencer

Los siguientes dominios vencen dentro de los próximos 7 días:

<x-mail::table>
| Dominio | Vence | Días restantes |
| :--- | :--- | :--- |
@foreach ($domains as $domain)
| {{ $domain->name }} | {{ $domain->expiration_date->format('d/m/Y') }} | {{ $domain->days_until_expiration }} |
@endforeach
</x-mail::table>

<x-mail::button :url="url('/domains?expiring=7')">
Ver en el panel
</x-mail::button>

Saludos,<br>
{{ config('variables.templateName') }}
</x-mail::message>
