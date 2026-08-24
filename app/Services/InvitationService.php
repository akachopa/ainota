<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Enums\MemberStatus;
use App\Enums\WorkspaceRole;
use App\Mail\WorkspaceInvitationMail;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use App\Notifications\GenericWorkspaceNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class InvitationService
{
    public function __construct(private ActivityLogger $logger) {}

    public function invite(Workspace $workspace, User $actor, string $email, WorkspaceRole $role): WorkspaceInvitation
    {
        $email = Str::lower($email);
        $existing = WorkspaceMember::query()->forWorkspace($workspace)->whereHas('user', fn ($q) => $q->where('email', $email))->exists();
        if ($existing) {
            throw new RuntimeException('User sudah menjadi anggota workspace.');
        }

        $plain = Str::random(48);

        $invitation = WorkspaceInvitation::query()->create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays((int) config('ainota.invitations.expires_days')),
            'invited_by' => $actor->id,
            'status' => InvitationStatus::Pending,
        ]);

        Mail::to($email)->queue(new WorkspaceInvitationMail($invitation, $plain));

        $this->logger->log('member.invited', $workspace, $actor, $invitation, after: [
            'email' => $email,
            'role' => $role->value,
        ]);

        return $invitation;
    }

    public function accept(string $plainToken, User $user): Workspace
    {
        $invitation = WorkspaceInvitation::query()->where('token_hash', hash('sha256', $plainToken))->firstOrFail();

        if (! $invitation->isAcceptable()) {
            throw new RuntimeException('Undangan tidak valid atau sudah kedaluwarsa.');
        }

        if (Str::lower($invitation->email) !== Str::lower($user->email)) {
            throw new RuntimeException('Undangan ini ditujukan ke email lain.');
        }

        WorkspaceMember::query()->updateOrCreate(
            ['workspace_id' => $invitation->workspace_id, 'user_id' => $user->id],
            [
                'role' => $invitation->role,
                'status' => MemberStatus::Active,
                'joined_at' => now(),
            ],
        );

        $invitation->update([
            'accepted_at' => now(),
            'status' => InvitationStatus::Accepted,
        ]);

        $workspace = $invitation->workspace;
        $this->logger->log('member.joined', $workspace, $user, $invitation);

        $user->notify(new GenericWorkspaceNotification(
            $workspace,
            'Bergabung ke workspace',
            'Anda telah bergabung ke '.$workspace->name.'.',
        ));

        return $workspace;
    }
}
