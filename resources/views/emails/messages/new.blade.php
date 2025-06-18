@component('mail::message')
# Nouveau message de {{ $message->sender->name }}

{{ $message->body }}

@if($message->chantier)
**Chantier concerné :** {{ $message->chantier->titre }}
@endif

@component('mail::button', ['url' => $url])
Voir le message
@endcomponent

Merci,<br>
{{ config('app.name') }}
@endcomponent
