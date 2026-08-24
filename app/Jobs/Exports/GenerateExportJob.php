<?php

namespace App\Jobs\Exports;

use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public string $exportId)
    {
        $this->onQueue('exports');
    }

    public function handle(ExportService $exports): void
    {
        $export = Export::query()->findOrFail($this->exportId);
        $exports->generate($export);
    }

    public function failed(?Throwable $exception): void
    {
        Export::query()->whereKey($this->exportId)->update([
            'status' => 'FAILED',
            'error_message' => $exception?->getMessage(),
        ]);
    }
}
