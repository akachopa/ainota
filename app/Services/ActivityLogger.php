<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public function log(
        string $action,
        ?Workspace $workspace = null,
        ?User $user = null,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        array $metadata = [],
    ): ActivityLog {
        return ActivityLog::query()->create([
            'workspace_id' => $workspace?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
