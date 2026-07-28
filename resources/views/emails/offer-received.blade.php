<x-mail::message>
# New offer for {{ $domain->name }}

@component('mail::table')
| | |
| :--- | :--- |
| Domain | {{ $domain->name }} |
| Amount | {{ $offer->currency }} {{ number_format((float) $offer->amount, 2) }} |
| From | {{ $offer->name ?: '—' }} |
| Email | {{ $offer->email }} |
| Phone | {{ $offer->phone ?: '—' }} |
@endcomponent

@if ($offer->message)
> {{ $offer->message }}
@endif

<x-mail::button :url="route('domains.show', $domain)">
View domain in the panel
</x-mail::button>

Saludos,<br>
{{ config('variables.templateName') }}
</x-mail::message>
