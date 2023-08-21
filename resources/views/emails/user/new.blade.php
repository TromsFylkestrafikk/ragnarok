@component('mail::message')
Hei, {{ $user->name }}.

Du har nå blitt registrert som bruker av Ragnarok.
Vennligst åpne følgende link i en nettleser for å logge inn:
<a href="{{ url('/') }}">{{ $app_url }}</a>

@component('mail::panel')
Brukernavn: {{ $user->email }}  
Passord: {{ $password }}  
@endcomponent

<strong>Vi anbefaler at du endrer passordet ditt ved første anledning.</strong>

--

Med vennlig hilsen

Ragnarok Dev Team
@endcomponent
