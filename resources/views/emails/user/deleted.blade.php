@component('mail::message')
Hei, {{ $user->name }}.

Din brukerkonto på Ragnarok ({{ $app_url }}) ble slettet av
<a href="mailto:{{ $admin }}">{{ $admin }}</a>.
Ta kontakt dersom du trenger tilgang til Ragnarok på et senere tidspunkt.

--

Med vennlig hilsen

Ragnarok Dev Team
@endcomponent
