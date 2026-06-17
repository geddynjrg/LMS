<x-mail::message>
# {{ $headline }}

@foreach($lines as $line)
{{ $line }}

@endforeach

@if($actionUrl)
<x-mail::button :url="$actionUrl">
{{ $actionLabel ?? 'Open in LMS' }}
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
