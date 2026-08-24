<?php

namespace App\Enums;

enum ExportType: string
{
    case TransactionTable = 'transaction_table';
    case AccountingJournal = 'accounting_journal';
}
