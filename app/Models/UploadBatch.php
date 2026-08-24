<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'created_by', 'name', 'total_files', 'processed_files',
    'failed_files', 'status', 'completed_at',
])]
class UploadBatch extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'batch_id');
    }

    public function progressPercent(): int
    {
        if ($this->total_files < 1) {
            return 0;
        }

        return (int) floor((($this->processed_files + $this->failed_files) / $this->total_files) * 100);
    }
}
