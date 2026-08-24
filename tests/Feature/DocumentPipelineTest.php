<?php

use App\Enums\DocumentStatus;
use App\Enums\TransactionStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('mengubah unggahan menjadi draft transaksi siap review tanpa menunggu HTTP', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $workspace = createWorkspaceFor($user);
    $file = UploadedFile::fake()->image('nota-pertamina.jpg', 640, 480);

    $this->actingAs($user)
        ->post(route('workspaces.documents.store', $workspace), ['file' => $file])
        ->assertCreated();

    $document = Document::query()->with(['transaction.entries', 'latestExtraction'])->first();

    expect($document)->not->toBeNull()
        ->and($document->status)->toBe(DocumentStatus::NeedsReview)
        ->and($document->latestExtraction)->not->toBeNull()
        ->and($document->transaction)->not->toBeNull()
        ->and($document->transaction->status)->toBe(TransactionStatus::NeedsReview)
        ->and($document->transaction->entries)->not->toBeEmpty()
        ->and($document->transaction->isBalanced())->toBeTrue();
});
