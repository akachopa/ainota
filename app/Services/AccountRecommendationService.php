<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\EntryType;
use App\Enums\RecommendationSource;
use App\Models\Account;
use App\Models\AiAccountSuggestion;
use App\Models\PaymentAccountMapping;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VendorAccountMapping;
use App\Models\Workspace;

class AccountRecommendationService
{
    /**
     * @return array{debit: ?Account, credit: ?Account, source: RecommendationSource, confidence: float, reason: string}
     */
    public function recommend(Workspace $workspace, ?Vendor $vendor, array $normalized): array
    {
        $credit = $this->creditFromPayment($workspace, $normalized);
        $mapping = $vendor?->accountMapping;

        if ($mapping?->debit_account_id) {
            return [
                'debit' => $mapping->debitAccount,
                'credit' => $mapping->credit_account_id ? $mapping->creditAccount : $credit,
                'source' => RecommendationSource::VendorRule,
                'confidence' => 0.98,
                'reason' => 'Mapping vendor workspace',
            ];
        }

        if ($vendor?->default_expense_account_id) {
            return [
                'debit' => $vendor->defaultExpenseAccount,
                'credit' => $credit,
                'source' => RecommendationSource::VendorRule,
                'confidence' => 0.95,
                'reason' => 'Akun default vendor',
            ];
        }

        $history = $this->historicalAccount($workspace, $vendor);
        if ($history) {
            return [
                'debit' => $history,
                'credit' => $credit,
                'source' => RecommendationSource::HistoricalRule,
                'confidence' => 0.92,
                'reason' => 'Riwayat transaksi disetujui',
            ];
        }

        $keyword = $this->keywordAccount($workspace, $normalized);
        if ($keyword) {
            return [
                'debit' => $keyword,
                'credit' => $credit,
                'source' => RecommendationSource::KeywordRule,
                'confidence' => 0.7,
                'reason' => 'Kecocokan kata kunci',
            ];
        }

        $fallback = Account::query()
            ->forWorkspace($workspace)
            ->where('type', AccountType::Expense)
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        return [
            'debit' => $fallback,
            'credit' => $credit,
            'source' => RecommendationSource::Ai,
            'confidence' => 0.4,
            'reason' => 'Perlu dipilih manual',
        ];
    }

    public function remember(Workspace $workspace, Vendor $vendor, Account $debit, ?Account $credit): void
    {
        VendorAccountMapping::query()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'vendor_id' => $vendor->id],
            [
                'debit_account_id' => $debit->id,
                'credit_account_id' => $credit?->id,
                'source' => RecommendationSource::Manual,
            ],
        );

        $vendor->update(['default_expense_account_id' => $debit->id]);
    }

    public function storeSuggestion(Workspace $workspace, string $documentId, ?Account $account, EntryType $type, RecommendationSource $source, float $confidence, string $reason): void
    {
        if (! $account) {
            return;
        }

        AiAccountSuggestion::query()->create([
            'workspace_id' => $workspace->id,
            'document_id' => $documentId,
            'account_id' => $account->id,
            'entry_type' => $type,
            'source' => $source,
            'confidence' => $confidence,
            'reason' => $reason,
        ]);
    }

    private function creditFromPayment(Workspace $workspace, array $normalized): ?Account
    {
        $hint = mb_strtolower((string) ($normalized['bank_hint'] ?? $normalized['payment_method'] ?? ''));

        $mappings = PaymentAccountMapping::query()->forWorkspace($workspace)->with('account')->get();
        foreach ($mappings as $mapping) {
            if ($hint !== '' && str_contains($hint, mb_strtolower($mapping->keyword))) {
                return $mapping->account;
            }
        }

        $default = $mappings->firstWhere('is_default', true) ?? $mappings->first();

        return $default?->account ?? $workspace->settings?->defaultCashAccount;
    }

    private function historicalAccount(Workspace $workspace, ?Vendor $vendor): ?Account
    {
        if (! $vendor) {
            return null;
        }

        $transaction = Transaction::query()
            ->forWorkspace($workspace)
            ->where('vendor_id', $vendor->id)
            ->where('status', 'APPROVED')
            ->latest('approved_at')
            ->with('entries.account')
            ->first();

        $debit = $transaction?->entries->firstWhere('entry_type', EntryType::Debit);

        return $debit?->account;
    }

    private function keywordAccount(Workspace $workspace, array $normalized): ?Account
    {
        $haystack = mb_strtolower(implode(' ', [
            (string) data_get($normalized, 'merchant.name'),
            (string) data_get($normalized, 'items.0.description'),
            (string) ($normalized['notes'] ?? ''),
        ]));

        $map = [
            'bbm' => '610104',
            'pertamina' => '610104',
            'pertamax' => '610104',
            'listrik' => '610102',
            'pln' => '610102',
            'internet' => '610103',
            'telkom' => '610103',
            'gaji' => '610101',
            'atk' => '610105',
            'konsumsi' => '610106',
        ];

        foreach ($map as $keyword => $code) {
            if (str_contains($haystack, $keyword)) {
                $account = Account::query()->forWorkspace($workspace)->where('code', $code)->first();
                if ($account) {
                    return $account;
                }
            }
        }

        return null;
    }
}
