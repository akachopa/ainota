<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'ASSET';
    case Liability = 'LIABILITY';
    case Equity = 'EQUITY';
    case Revenue = 'REVENUE';
    case Cogs = 'COGS';
    case Expense = 'EXPENSE';
    case OtherIncome = 'OTHER_INCOME';
    case OtherExpense = 'OTHER_EXPENSE';

    public function defaultNormalBalance(): NormalBalance
    {
        return match ($this) {
            self::Asset, self::Cogs, self::Expense, self::OtherExpense => NormalBalance::Debit,
            self::Liability, self::Equity, self::Revenue, self::OtherIncome => NormalBalance::Credit,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Aset',
            self::Liability => 'Liabilitas',
            self::Equity => 'Ekuitas',
            self::Revenue => 'Pendapatan',
            self::Cogs => 'HPP',
            self::Expense => 'Beban',
            self::OtherIncome => 'Pendapatan Lain',
            self::OtherExpense => 'Beban Lain',
        };
    }
}
