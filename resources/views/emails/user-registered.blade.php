<x-mail::message>
# Welcome to the UWEZO LMS, {{ $user->name }}

Your account has been successfully created by the administrator. You can now log in to the Loan Management System using the credentials below:

**Email:** {{ $user->email }}
**Temporary Password:** `{{ $password }}`

<x-mail::button :url="route('login')">
Login to Dashboard
</x-mail::button>

*Note: For security reasons, please change your password immediately after your first login.*

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
