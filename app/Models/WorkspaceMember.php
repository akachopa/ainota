<?php

namespace App\Models;

use App\Enums\MemberStatus;
use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\WorkspaceMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property WorkspaceRole $role
 * @property MemberStatus $status
 * @property array<int, string>|null $permissions
 * @property-read User $user
 * @property-read Workspace $workspace
 */
#[Fillable([
    'workspace_id',
    'user_id',
    'role',
    'permissions',
    'status',
    'joined_at',
])]
class WorkspaceMember extends Model
{
    /** @use HasFactory<WorkspaceMemberFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'status' => MemberStatus::class,
            'permissions' => 'array',
            'joined_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(Permission $permission): bool
    {
        $custom = $this->permissions ?? [];

        if ($custom !== []) {
            return in_array($permission->value, $custom, true);
        }

        return $this->role->has($permission);
    }
}
