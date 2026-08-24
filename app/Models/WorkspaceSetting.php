<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property bool $require_approval
 * @property bool $require_separate_approver
 * @property bool $allow_approver_edit
 * @property bool $allow_export_unapproved
 * @property bool $uploader_can_view_amounts
 * @property bool $ai_processing_enabled
 */
#[Fillable([
    'workspace_id',
    'require_approval',
    'require_separate_approver',
    'allow_approver_edit',
    'allow_export_unapproved',
    'uploader_can_view_amounts',
    'ai_processing_enabled',
    'duplicate_threshold',
    'default_cash_account_id',
    'coa_template_slug',
    'extra',
])]
class WorkspaceSetting extends Model
{
    use BelongsToWorkspace, HasUuids;

    protected function casts(): array
    {
        return [
            'require_approval' => 'boolean',
            'require_separate_approver' => 'boolean',
            'allow_approver_edit' => 'boolean',
            'allow_export_unapproved' => 'boolean',
            'uploader_can_view_amounts' => 'boolean',
            'ai_processing_enabled' => 'boolean',
            'duplicate_threshold' => 'decimal:2',
            'extra' => 'array',
        ];
    }

    public function defaultCashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_cash_account_id');
    }
}
