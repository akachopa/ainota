<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function review(User $user, Transaction $transaction): bool
    {
        return $user->canIn($transaction->workspace, Permission::TransactionReview);
    }

    public function approve(User $user, Transaction $transaction): bool
    {
        return $user->canIn($transaction->workspace, Permission::TransactionApprove);
    }

    public function export(User $user, Transaction $transaction): bool
    {
        return $user->canIn($transaction->workspace, Permission::TransactionExport);
    }
}
