<?php

namespace App\Enums;

enum DocumentFlag: string
{
    case LowConfidence = 'LOW_CONFIDENCE';
    case TotalMismatch = 'TOTAL_MISMATCH';
    case ItemSumMismatch = 'ITEM_SUM_MISMATCH';
    case PossibleDuplicate = 'POSSIBLE_DUPLICATE';
    case UnknownVendor = 'UNKNOWN_VENDOR';
    case AccountNotMapped = 'ACCOUNT_NOT_MAPPED';
    case UnbalancedJournal = 'UNBALANCED_JOURNAL';
    case UnreadableDocument = 'UNREADABLE_DOCUMENT';
    case FutureDate = 'FUTURE_DATE';
    case AiFailed = 'AI_FAILED';
    case MissingGrandTotal = 'MISSING_GRAND_TOTAL';
}
