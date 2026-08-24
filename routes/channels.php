<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('workspace.{workspaceId}', function ($user, string $workspaceId) {
    return $user->memberships()->where('workspace_id', $workspaceId)->exists();
});
