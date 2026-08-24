<?php

namespace App\Enums;

enum RecommendationSource: string
{
    case Manual = 'manual';
    case VendorRule = 'vendor_rule';
    case HistoricalRule = 'historical_rule';
    case KeywordRule = 'keyword_rule';
    case WorkspaceRule = 'workspace_rule';
    case PaymentMapping = 'payment_mapping';
    case Ai = 'ai';
}
