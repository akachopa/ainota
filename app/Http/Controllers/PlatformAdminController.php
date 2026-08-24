<?php

namespace App\Http\Controllers;

use App\Models\AiExtraction;
use App\Models\Document;
use App\Models\Plan;
use App\Models\UsageLedger;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAdminController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->is_platform_admin, 403);

        return Inertia::render('platform/Index', [
            'users' => User::query()->latest()->limit(20)->get(['id', 'name', 'email', 'created_at', 'is_platform_admin']),
            'workspaces' => Workspace::query()->with('owner')->latest()->limit(20)->get(['id', 'name', 'slug', 'owner_user_id', 'created_at']),
            'plans' => Plan::query()->orderBy('sort_order')->get(),
            'ai_usage' => [
                'extractions' => AiExtraction::query()->count(),
                'success' => AiExtraction::query()->where('status', 'success')->count(),
                'failed' => AiExtraction::query()->where('status', 'failed')->count(),
                'cost' => UsageLedger::query()->sum('provider_cost'),
            ],
            'documents' => Document::query()->count(),
        ]);
    }
}
