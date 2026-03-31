<x-mail::message>
# Password Updated

Hello {{ $user->name }},

Your login password for the **Uwezo Loan Management System** has been updated by an administrator.

**New Password:** `{{ $password }}`

<x-mail::button :url="route('login')">
Login to Your Account
</x-mail::button>

If you did not request this change, please contact the ICT department immediately.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
