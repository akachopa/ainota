<?php

use App\Enums\WorkspaceRole;
use App\Models\UploadLink;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('rejects expired invitations', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $invitee = User::factory()->create(['email' => 'new@example.test']);

    $invitation = app(InvitationService::class)->invite($workspace, $owner, 'new@example.test', WorkspaceRole::Uploader);
    $invitation->update(['expires_at' => now()->subDay()]);

    $token = 'not-the-real-token';

    expect(fn () => app(InvitationService::class)->accept($token, $invitee))
        ->toThrow(ModelNotFoundException::class);
});

it('rejects expired upload links', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $link = UploadLink::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Klien',
        'public_token' => 'abc',
        'token_hash' => hash('sha256', 'abc'),
        'expires_at' => now()->subDay(),
        'is_active' => true,
        'created_by' => $owner->id,
    ]);

    $this->get(route('upload-links.public', 'abc'))->assertForbidden();
    expect($link->isUsable())->toBeFalse();
});
