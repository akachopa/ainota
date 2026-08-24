<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Export;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::call(function () {
    Document::query()
        ->whereIn('status', [
            DocumentStatus::AiProcessing->value,
            DocumentStatus::DuplicateChecking->value,
            DocumentStatus::Queued->value,
        ])
        ->where('updated_at', '<', now()->subMinutes((int) config('ainota.documents.stale_processing_minutes')))
        ->update([
            'status' => DocumentStatus::Failed->value,
            'failure_code' => 'STALE_PROCESSING',
            'failure_message' => 'Proses terhenti. Silakan coba ulang.',
        ]);
})->everyMinute();

Schedule::call(function () {
    WorkspaceInvitation::query()
        ->where('status', 'pending')
        ->where('expires_at', '<', now())
        ->update(['status' => 'expired']);

    Export::query()
        ->where('status', 'READY')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->each(function (Export $export): void {
            if ($export->storage_path) {
                Storage::disk($export->storage_disk)->delete($export->storage_path);
            }
            $export->update(['status' => 'EXPIRED', 'storage_path' => null]);
        });
})->hourly();
