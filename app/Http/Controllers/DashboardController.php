<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\Permission;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function account(Request $request): Response
    {
        $user = $request->user();
        $memberships = $user->memberships()->with('workspace')->get();

        $cards = $memberships->map(function ($member) use ($user) {
            $workspace = $member->workspace;
            $query = Document::query()->forWorkspace($workspace);
            if (! $user->canIn($workspace, Permission::DocumentViewAll)) {
                $query->where('uploaded_by', $user->id);
            }

            return [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'role' => $member->role->value,
                'documents' => (clone $query)->count(),
                'need_review' => (clone $query)->where('status', DocumentStatus::NeedsReview)->count(),
                'waiting_approval' => (clone $query)->where('status', DocumentStatus::WaitingApproval)->count(),
                'processing' => (clone $query)->whereIn('status', [
                    DocumentStatus::Queued,
                    DocumentStatus::DuplicateChecking,
                    DocumentStatus::AiProcessing,
                ])->count(),
            ];
        });

        return Inertia::render('Dashboard', [
            'cards' => $cards,
            'stats' => [
                'workspaces' => $cards->count(),
                'documents_today' => Document::query()
                    ->whereIn('workspace_id', $memberships->pluck('workspace_id'))
                    ->whereDate('created_at', now()->toDateString())
                    ->count(),
                'need_review' => $cards->sum('need_review'),
                'waiting_approval' => $cards->sum('waiting_approval'),
                'processing' => $cards->sum('processing'),
            ],
        ]);
    }

    public function workspace(Request $request, Workspace $workspace): Response
    {
        $user = $request->user();
        $query = Document::query()->forWorkspace($workspace);
        if (! $user->canIn($workspace, Permission::DocumentViewAll)) {
            $query->where('uploaded_by', $user->id);
        }

        $counts = [
            'uploads_today' => (clone $query)->whereDate('created_at', now()->toDateString())->count(),
            'processing' => (clone $query)->whereIn('status', [
                DocumentStatus::Queued, DocumentStatus::DuplicateChecking, DocumentStatus::AiProcessing,
            ])->count(),
            'duplicates' => (clone $query)->whereIn('status', [
                DocumentStatus::DuplicateExact, DocumentStatus::DuplicatePossible,
            ])->count(),
            'need_review' => (clone $query)->where('status', DocumentStatus::NeedsReview)->count(),
            'waiting_approval' => (clone $query)->where('status', DocumentStatus::WaitingApproval)->count(),
            'approved_month' => (clone $query)->where('status', DocumentStatus::Approved)
                ->whereMonth('updated_at', now()->month)->count(),
            'failed' => (clone $query)->where('status', DocumentStatus::Failed)->count(),
        ];

        $recent = (clone $query)->with('vendor')->latest()->limit(8)->get();

        return Inertia::render('workspaces/Dashboard', [
            'counts' => $counts,
            'recent' => $recent,
            'canUpload' => $user->canIn($workspace, Permission::DocumentUpload),
        ]);
    }
}
