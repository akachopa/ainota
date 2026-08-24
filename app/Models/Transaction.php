<?php

namespace App\Models;

use App\Enums\EntryType;
use App\Enums\TransactionStatus;
use App\Models\Concerns\BelongsToWorkspace;
use App\Support\Money;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property TransactionStatus $status
 * @property-read Workspace $workspace
 * @property-read Document|null $document
 */
#[Fillable([
    'workspace_id', 'document_id', 'vendor_id', 'transaction_date', 'reference_number',
    'description', 'currency', 'subtotal', 'discount_amount', 'tax_amount',
    'service_charge', 'total_amount', 'payment_method', 'status', 'reviewed_by',
    'reviewed_at', 'approved_by', 'approved_at', 'created_by', 'review_note', 'approval_note',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'transaction_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class)->orderBy('sort_order');
    }

    public function debitTotal(): float
    {
        return (float) $this->entries->where('entry_type', EntryType::Debit)->sum('amount');
    }

    public function creditTotal(): float
    {
        return (float) $this->entries->where('entry_type', EntryType::Credit)->sum('amount');
    }

    public function isBalanced(float $tolerance = 0.01): bool
    {
        return Money::equal($this->debitTotal(), $this->creditTotal(), $tolerance);
    }
}
