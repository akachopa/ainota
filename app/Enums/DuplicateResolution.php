<?php

namespace App\Enums;

enum DuplicateResolution: string
{
    case KeepBoth = 'keep_both';
    case MarkDuplicate = 'mark_duplicate';
    case ProcessAnyway = 'process_anyway';
    case Replace = 'replace';
}
