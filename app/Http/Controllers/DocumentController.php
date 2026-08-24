<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Enums\DuplicateResolution;
use App\Enums\Permission;
use App\Jobs\Documents\RunDocumentPipelineJob;
use App\Models\Document;
use App\Models\UploadBatch;
use App\Models\Workspace;
use App\Services\DocumentStatusService;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request, Workspace $workspace): Response
    {
        $user = $request->user();
        $query = Document::query()->forWorkspace($workspace)->with(['vendor', 'uploader']);

        if (! $user->canIn($workspace, Permission::DocumentViewAll)) {
            $query->where('uploaded_by', $user->id);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($request->boolean('duplicates')) {
            $query->where(function ($q) {
                $q->whereIn('status', [DocumentStatus::DuplicateExact, DocumentStatus::DuplicatePossible])
                    ->orWhereJsonContains('flags', 'POSSIBLE_DUPLICATE');
            });
        }
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'ilike', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'ilike', "%{$search}%"));
            });
        }
        if ($from = $request->date('from')) {
            $query->whereDate('transaction_date', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('transaction_date', '<=', $to);
        }

        $hideAmounts = $user->canIn($workspace, Permission::DocumentViewOwn)
            && ! $user->canIn($workspace, Permission::DocumentViewAll)
            && ! $workspace->settings?->uploader_can_view_amounts;

        $documents = $query->latest()->paginate(20)->withQueryString();
        if ($hideAmounts) {
            $documents->getCollection()->transform(function (Document $document) {
                $document->grand_total = null;

                return $document;
            });
        }

        return Inertia::render('documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['status', 'q', 'from', 'to', 'duplicates']),
            'canUpload' => $user->canIn($workspace, Permission::DocumentUpload),
            'statuses' => collect(DocumentStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function store(Request $request, Workspace $workspace, DocumentUploadService $uploads): JsonResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::DocumentUpload), 403);

        $maxKb = (int) config('ainota.documents.max_size_kb');
        $request->validate([
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
            'batch_id' => ['nullable', 'uuid'],
        ]);

        $file = $request->file('file');
        $batchModel = $request->filled('batch_id')
            ? UploadBatch::query()->forWorkspace($workspace)->whereKey($request->string('batch_id'))->first()
            : null;

        $document = $uploads->store($workspace, $file, $request->user(), DocumentSource::App, $batchModel);

        return response()->json([
            'document' => $document->only(['id', 'status', 'original_filename', 'progress']),
        ], 201);
    }

    public function createBatch(Request $request, Workspace $workspace, DocumentUploadService $uploads): JsonResponse
    {
        abort_unless($request->user()->canIn($workspace, Permission::DocumentUpload), 403);
        $data = $request->validate([
            'total' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $batch = $uploads->createBatch($workspace, $request->user(), (int) $data['total']);

        return response()->json(['batch' => $batch]);
    }

    public function show(Request $request, Workspace $workspace, Document $document): Response
    {
        abort_unless($document->workspace_id === $workspace->id, 404);
        $this->authorize('view', $document);

        $document->load([
            'vendor', 'uploader', 'items', 'latestExtraction', 'transaction.entries.account',
            'duplicateCandidates.matchedDocument', 'pages', 'possibleDuplicate',
        ]);

        $accounts = $workspace->accounts()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type']);
        $vendors = $workspace->vendors()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('documents/Show', [
            'document' => $document,
            'accounts' => $accounts,
            'vendors' => $vendors,
            'canReview' => $request->user()->canIn($workspace, Permission::TransactionReview),
            'canApprove' => $request->user()->canIn($workspace, Permission::TransactionApprove),
        ]);
    }

    public function preview(Request $request, Workspace $workspace, Document $document): StreamedResponse
    {
        abort_unless($document->workspace_id === $workspace->id, 404);
        $this->authorize('view', $document);

        $path = $document->currentVersionRecord?->preview_path ?? $document->storage_path;
        abort_unless(Storage::disk($document->storage_disk)->exists($path), 404);

        return Storage::disk($document->storage_disk)->response($path, $document->original_filename, [
            'Content-Type' => str_ends_with($path, '.jpg') ? 'image/jpeg' : $document->mime_type,
        ]);
    }

    public function reprocess(Request $request, Workspace $workspace, Document $document, DocumentStatusService $statuses): RedirectResponse
    {
        abort_unless($document->workspace_id === $workspace->id, 404);
        $this->authorize('update', $document);

        $document->increment('current_version');
        $statuses->transition($document, DocumentStatus::Queued, $request->user(), [
            'failure_code' => null,
            'failure_message' => null,
            'progress' => 5,
        ]);
        RunDocumentPipelineJob::dispatch($document->id)->onQueue('uploads');

        return back()->with('success', 'Dokumen masuk antrean proses ulang.');
    }

    public function resolveDuplicate(Request $request, Workspace $workspace, Document $document, DocumentStatusService $statuses): RedirectResponse
    {
        abort_unless($document->workspace_id === $workspace->id, 404);
        $this->authorize('update', $document);

        $data = $request->validate([
            'resolution' => ['required', Rule::enum(DuplicateResolution::class)],
        ]);
        $resolution = DuplicateResolution::from($data['resolution']);

        $document->duplicateCandidates()->latest()->first()?->update([
            'resolution' => $resolution,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        if ($resolution === DuplicateResolution::ProcessAnyway || $resolution === DuplicateResolution::KeepBoth) {
            $statuses->transition($document, DocumentStatus::Queued, $request->user());
            RunDocumentPipelineJob::dispatch($document->id)->onQueue('uploads');
        }

        if ($resolution === DuplicateResolution::MarkDuplicate) {
            $statuses->transition($document, DocumentStatus::DuplicateExact, $request->user(), progress: 100);
        }

        return back()->with('success', 'Keputusan duplikasi disimpan.');
    }
}
