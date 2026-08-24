<x-mail::message>
# Undangan Workspace

Anda diundang bergabung ke **{{ $workspace }}** sebagai **{{ $role }}**.

<x-mail::button :url="$url">
Terima undangan
</x-mail::button>

Tautan ini hanya berlaku sekali dan akan kedaluwarsa.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
