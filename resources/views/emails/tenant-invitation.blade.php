<x-mail::message>
# You've Been Invited

You have been invited to join **{{ $invitation->tenant->name }}** as a **{{ $invitation->role->value }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation expires on {{ $invitation->expires_at->toFormattedDateString() }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
