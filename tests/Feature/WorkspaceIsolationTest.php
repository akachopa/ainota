<?php

use App\Enums\DocumentStatus;
use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Jobs\AI\ExtractDocumentWithAiJob;
use App\Jobs\Documents\ExactDuplicateCheckJob;
use App\Jobs\Documents\RunDocumentPipelineJob;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentStatusService;
use App\Services\DocumentUploadService;
use App\Services\DuplicateDetectionService;
use App\Services\WorkspaceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('allows one user to belong to many workspaces', function () {
    $user = User::factory()->create();
    $first = createWorkspaceFor($user);
    $second = app(WorkspaceService::class)->create($user, [
        'name' => 'CV Kedua',
        'currency' => 'IDR',
        'timezone' => 'Asia/Jakarta',
        'locale' => 'id',
        'template' => 'empty',
    ]);

    expect($user->workspaces()->count())->toBe(2)
        ->and($first->id)->not->toBe($second->id);
});

it('blocks tenant a from viewing tenant b documents', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $workspaceA = createWorkspaceFor($alice);
    $workspaceB = createWorkspaceFor($bob);

    $document = Document::factory()->create([
        'workspace_id' => $workspaceB->id,
        'uploaded_by' => $bob->id,
    ]);

    $this->actingAs($alice)
        ->get(route('workspaces.documents.show', [$workspaceA, $document]))
        ->assertNotFound();
});

it('forbids uploader from approving', function () {
    $uploader = User::factory()->create();
    $workspace = createWorkspaceFor($uploader, WorkspaceRole::Uploader);

    expect($uploader->canIn($workspace, Permission::TransactionApprove))->toBeFalse()
        ->and($uploader->canIn($workspace, Permission::DocumentUpload))->toBeTrue();

    $this->actingAs($uploader)
        ->get(route('workspaces.approval', $workspace))
        ->assertForbidden();
});

it('forbids reviewer from managing members', function () {
    $reviewer = User::factory()->create();
    $workspace = createWorkspaceFor($reviewer, WorkspaceRole::Reviewer);

    expect($reviewer->canIn($workspace, Permission::MemberManage))->toBeFalse();

    $this->actingAs($reviewer)
        ->post(route('workspaces.members.invite', $workspace), [
            'email' => 'x@example.test',
            'role' => 'uploader',
        ])
        ->assertForbidden();
});

it('queues document processing on upload and does not call AI in the http request', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();
    $workspace = createWorkspaceFor($user);

    $file = UploadedFile::fake()->create('nota.jpg', 100, 'image/jpeg');

    $this->actingAs($user)
        ->post(route('workspaces.documents.store', $workspace), ['file' => $file])
        ->assertCreated();

    expect(Document::query()->count())->toBe(1)
        ->and(Document::query()->first()->status)->toBe(DocumentStatus::Queued);

    Queue::assertPushed(RunDocumentPipelineJob::class);
    Queue::assertNotPushed(ExtractDocumentWithAiJob::class);
});

it('skips ai for exact duplicate files', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->create();
    $workspace = createWorkspaceFor($user);
    $contents = 'same-bytes';

    $first = app(DocumentUploadService::class)->store(
        $workspace,
        UploadedFile::fake()->createWithContent('a.jpg', $contents)->mimeType('image/jpeg'),
        $user,
    );
    $second = app(DocumentUploadService::class)->store(
        $workspace,
        UploadedFile::fake()->createWithContent('b.jpg', $contents)->mimeType('image/jpeg'),
        $user,
    );

    $job = new ExactDuplicateCheckJob($second->id);
    $job->handle(app(DuplicateDetectionService::class), app(DocumentStatusService::class));

    expect($second->fresh()->status)->toBe(DocumentStatus::DuplicateExact)
        ->and($second->fresh()->possible_duplicate_of)->toBe($first->id);
});
