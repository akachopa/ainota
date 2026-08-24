<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'DRAFT';
    case NeedsReview = 'NEEDS_REVIEW';
    case Reviewed = 'REVIEWED';
    case WaitingApproval = 'WAITING_APPROVAL';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::NeedsReview => 'Perlu ditinjau',
            self::Reviewed => 'Sudah ditinjau',
            self::WaitingApproval => 'Menunggu persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
