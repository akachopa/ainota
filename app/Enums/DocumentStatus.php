<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Uploading = 'UPLOADING';
    case Queued = 'QUEUED';
    case DuplicateChecking = 'DUPLICATE_CHECKING';
    case DuplicateExact = 'DUPLICATE_EXACT';
    case DuplicatePossible = 'DUPLICATE_POSSIBLE';
    case AiProcessing = 'AI_PROCESSING';
    case AiProcessed = 'AI_PROCESSED';
    case ValidationFailed = 'VALIDATION_FAILED';
    case NeedsReview = 'NEEDS_REVIEW';
    case Reviewed = 'REVIEWED';
    case WaitingApproval = 'WAITING_APPROVAL';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Failed = 'FAILED';
    case Archived = 'ARCHIVED';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Uploading => [self::Queued, self::Failed],
            self::Queued => [
                self::DuplicateChecking,
                self::DuplicateExact,
                self::Failed,
                self::Archived,
            ],
            self::DuplicateChecking => [
                self::DuplicateExact,
                self::DuplicatePossible,
                self::AiProcessing,
                self::Failed,
            ],
            self::DuplicateExact => [self::AiProcessing, self::Archived, self::Queued],
            self::DuplicatePossible => [self::AiProcessing, self::Archived, self::Queued],
            self::AiProcessing => [self::AiProcessed, self::Failed],
            self::AiProcessed => [self::ValidationFailed, self::NeedsReview, self::Failed],
            self::ValidationFailed => [self::NeedsReview, self::Failed, self::Queued],
            self::NeedsReview => [
                self::Reviewed,
                self::WaitingApproval,
                self::Approved,
                self::Rejected,
                self::Failed,
                self::Queued,
            ],
            self::Reviewed => [self::WaitingApproval, self::NeedsReview],
            self::WaitingApproval => [self::Approved, self::Rejected, self::NeedsReview],
            self::Rejected => [self::NeedsReview, self::Archived],
            self::Approved => [self::Archived],
            self::Failed => [self::Queued, self::Archived],
            self::Archived => [self::Queued],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isProcessing(): bool
    {
        return in_array($this, [
            self::Uploading,
            self::Queued,
            self::DuplicateChecking,
            self::AiProcessing,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Uploading => 'Mengunggah',
            self::Queued => 'Antrian',
            self::DuplicateChecking => 'Cek duplikasi',
            self::DuplicateExact => 'Duplikat persis',
            self::DuplicatePossible => 'Kemungkinan duplikat',
            self::AiProcessing => 'AI memproses',
            self::AiProcessed => 'AI selesai',
            self::ValidationFailed => 'Validasi gagal',
            self::NeedsReview => 'Perlu ditinjau',
            self::Reviewed => 'Sudah ditinjau',
            self::WaitingApproval => 'Menunggu persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Failed => 'Gagal',
            self::Archived => 'Diarsipkan',
        };
    }
}
