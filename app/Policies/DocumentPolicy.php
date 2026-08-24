<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->canIn($document->workspace, Permission::DocumentViewAll)) {
            return true;
        }

        return $user->canIn($document->workspace, Permission::DocumentViewOwn)
            && $document->uploaded_by === $user->id;
    }

    public function update(User $user, Document $document): bool
    {
        return $user->canIn($document->workspace, Permission::DocumentEdit);
    }

    public function upload(User $user, Document $document): bool
    {
        return $user->canIn($document->workspace, Permission::DocumentUpload);
    }
}
