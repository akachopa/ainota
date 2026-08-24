<?php

namespace App\Models;

use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Enums\FailureCode;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property DocumentStatus $status
 * @property DocumentSource $source
 * @property FailureCode|null $failure_code
 * @property array<int, string>|null $flags
 * @property-read Workspace $workspace
 * @property-read DocumentVersion|null $currentVersionRecord
 * @property-read AiExtraction|null $latestExtraction
 * @property-read Transaction|null $transaction
 */
#[Fillable([
    'workspace_id', 'uploaded_by', 'batch_id', 'upload_link_id', 'source', 'status',
    'original_filename', 'mime_type', 'file_size', 'storage_disk', 'storage_path',
    'sha256', 'perceptual_hash', 'current_version', 'page_count', 'document_type',
    'transaction_date', 'vendor_id', 'grand_total', 'currency', 'duplicate_score',
    'possible_duplicate_of', 'content_fingerprint', 'flags', 'failure_code',
    'failure_message', 'progress', 'guest_uploader_name', 'processing_started_at',
    'processing_completed_at',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToWorkspace, HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'source' => DocumentSource::class,
            'failure_code' => FailureCode::class,
            'transaction_date' => 'date',
            'grand_total' => 'decimal:2',
            'duplicate_score' => 'decimal:2',
            'flags' => 'array',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UploadBatch::class, 'batch_id');
    }

    public function uploadLink(): BelongsTo
    {
        return $this->belongsTo(UploadLink::class);
    }

    public function possibleDuplicate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'possible_duplicate_of');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class)->orderBy('page_number');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('sort_order');
    }

    public function hashes(): HasMany
    {
        return $this->hasMany(DocumentHash::class);
    }

    public function duplicateCandidates(): HasMany
    {
        return $this->hasMany(DuplicateCandidate::class);
    }

    public function extractions(): HasMany
    {
        return $this->hasMany(AiExtraction::class);
    }

    /**
     * @return HasOne<DocumentVersion, $this>
     */
    public function currentVersionRecord(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->where('is_active', true);
    }

    /**
     * @return HasOne<AiExtraction, $this>
     */
    public function latestExtraction(): HasOne
    {
        return $this->hasOne(AiExtraction::class)->latestOfMany();
    }

    /**
     * @return HasOne<Transaction, $this>
     */
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags ?? [], true);
    }
}
