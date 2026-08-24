<?php

namespace App\Enums;

enum EntryType: string
{
    case Debit = 'DEBIT';
    case Credit = 'CREDIT';
}
