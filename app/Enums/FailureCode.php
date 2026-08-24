<?php

namespace App\Enums;

enum FailureCode: string
{
    case AiProviderError = 'AI_PROVIDER_ERROR';
    case UnsupportedFile = 'UNSUPPORTED_FILE';
    case CorruptedImage = 'CORRUPTED_IMAGE';
    case EncryptedPdf = 'ENCRYPTED_PDF';
    case TooManyPages = 'TOO_MANY_PAGES';
    case StaleProcessing = 'STALE_PROCESSING';
    case QuotaExceeded = 'QUOTA_EXCEEDED';
    case UnreadableDocument = 'UNREADABLE_DOCUMENT';
}
