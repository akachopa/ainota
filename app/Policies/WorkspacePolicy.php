<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->memberFor($user) !== null;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->canIn($workspace, Permission::WorkspaceManage);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->canIn($workspace, Permission::WorkspaceDelete)
            || $workspace->memberFor($user)?->role === WorkspaceRole::Owner;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $user->canIn($workspace, Permission::MemberManage);
    }
}
