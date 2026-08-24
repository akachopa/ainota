<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\ExportFormat;
use App\Enums\ExportStatus;
use App\Enums\ExportType;
use App\Enums\TransactionStatus;
use App\Jobs\Exports\GenerateExportJob;
use App\Models\Export;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\GenericWorkspaceNotification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    public function __construct(private ActivityLogger $logger) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function request(Workspace $workspace, User $user, ExportType $type, ExportFormat $format, array $filters = []): Export
    {
        $query = $this->filteredTransactions($workspace, $type, $filters);
        $count = $query->count();

        $export = Export::query()->create([
            'workspace_id' => $workspace->id,
            'requested_by' => $user->id,
            'type' => $type,
            'format' => $format,
            'status' => ExportStatus::Queued,
            'filters' => $filters,
            'row_count' => $count,
        ]);

        $this->logger->log('export.created', $workspace, $user, $export);

        if ($count > (int) config('ainota.export.async_row_threshold')) {
            GenerateExportJob::dispatch($export->id)->onQueue('exports');

            return $export;
        }

        $this->generate($export);

        return $export->fresh();
    }

    public function generate(Export $export): Export
    {
        $export->update(['status' => ExportStatus::Processing]);
        $workspace = $export->workspace;
        $rows = $this->buildRows($export);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $sheet->setCellValue([$c + 1, $r + 1], $value);
            }
        }

        $disk = (string) config('ainota.documents.disk');
        $path = "workspaces/{$workspace->id}/exports/{$export->id}.".$export->format->value;
        $tmp = tmpfile();
        $tmpPath = stream_get_meta_data($tmp)['uri'];

        if ($export->format === ExportFormat::Csv) {
            (new Csv($spreadsheet))->save($tmpPath);
        } else {
            (new Xlsx($spreadsheet))->save($tmpPath);
        }

        Storage::disk($disk)->put($path, (string) file_get_contents($tmpPath));
        fclose($tmp);

        $export->update([
            'status' => ExportStatus::Ready,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'row_count' => max(0, count($rows) - 1),
            'ready_at' => now(),
            'expires_at' => now()->addDays((int) config('ainota.export.retention_days')),
        ]);

        $export->requester?->notify(new GenericWorkspaceNotification(
            $workspace,
            'Export siap diunduh',
            'File export '.$export->type->value.' sudah siap.',
        ));

        return $export;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    public function buildRows(Export $export): array
    {
        $workspace = $export->workspace;
        $transactions = $this->filteredTransactions($workspace, $export->type, $export->filters ?? [])
            ->with(['vendor', 'entries.account', 'document'])
            ->orderBy('transaction_date')
            ->get();

        if ($export->type === ExportType::TransactionTable) {
            $rows = [[
                'Transaction Date', 'Document Number', 'Vendor', 'Description', 'Subtotal',
                'Discount', 'Tax', 'Grand Total', 'Payment Method', 'Category', 'Status', 'Workspace',
            ]];

            foreach ($transactions as $transaction) {
                $debit = $transaction->entries->firstWhere('entry_type', EntryType::Debit);
                $rows[] = [
                    optional($transaction->transaction_date)?->format('Y-m-d'),
                    $transaction->reference_number,
                    $transaction->vendor?->name,
                    $transaction->description,
                    $transaction->subtotal,
                    $transaction->discount_amount,
                    $transaction->tax_amount,
                    $transaction->total_amount,
                    $transaction->payment_method,
                    $debit?->account?->name,
                    $transaction->status->value,
                    $workspace->name,
                ];
            }

            return $rows;
        }

        $rows = [[
            'Date', 'Reference', 'Account Code', 'Account Name', 'Description', 'Debit', 'Credit', 'Vendor', 'Document ID',
        ]];

        foreach ($transactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                $rows[] = [
                    optional($transaction->transaction_date)?->format('Y-m-d'),
                    $transaction->reference_number,
                    $entry->account?->code,
                    $entry->account?->name,
                    $entry->description ?: $transaction->description,
                    $entry->entry_type === EntryType::Debit ? $entry->amount : 0,
                    $entry->entry_type === EntryType::Credit ? $entry->amount : 0,
                    $transaction->vendor?->name,
                    $transaction->document_id,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredTransactions(Workspace $workspace, ExportType $type, array $filters)
    {
        $query = Transaction::query()->forWorkspace($workspace);

        $allowed = [TransactionStatus::Approved->value];
        if ($workspace->settings?->allow_export_unapproved) {
            $allowed[] = TransactionStatus::WaitingApproval->value;
            $allowed[] = TransactionStatus::Reviewed->value;
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', $allowed);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('transaction_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('transaction_date', '<=', $filters['to']);
        }

        return $query;
    }
}
