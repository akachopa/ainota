<?php

namespace App\Models;

use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Enums\ExportType;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ExportType $type
 * @property ExportFormat $format
 * @property ExportStatus $status
 * @property-read Workspace $workspace
 */
#[Fillable([
    'workspace_id', 'requested_by', 'type', 'format', 'status', 'preset_id',
    'filters', 'row_count', 'storage_disk', 'storage_path', 'error_message',
    'ready_at', 'expires_at', 'downloaded_at',
])]
class Export extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => ExportType::class,
            'format' => ExportFormat::class,
            'status' => ExportStatus::class,
            'filters' => 'array',
            'ready_at' => 'datetime',
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function preset(): BelongsTo
    {
        return $this->belongsTo(ExportPreset::class, 'preset_id');
    }
}
