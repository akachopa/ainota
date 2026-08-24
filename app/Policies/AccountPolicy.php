<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $user->canIn($account->workspace, Permission::CoaView);
    }

    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->canIn($workspace, Permission::CoaView);
    }

    public function manage(User $user, Workspace $workspace): bool
    {
        return $user->canIn($workspace, Permission::CoaManage);
    }
}
