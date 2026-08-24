<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\WorkspaceRole;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property InvitationStatus $status
 * @property-read Workspace $workspace
 */
#[Fillable([
    'workspace_id',
    'email',
    'role',
    'token_hash',
    'expires_at',
    'invited_by',
    'accepted_at',
    'status',
])]
class WorkspaceInvitation extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAcceptable(): bool
    {
        return $this->status === InvitationStatus::Pending
            && $this->expires_at?->isFuture();
    }
}
