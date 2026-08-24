<?php

namespace App\Http\Controllers;

use App\Enums\DocumentSource;
use App\Models\UploadLink;
use App\Models\WorkspaceInvitation;
use App\Services\DocumentUploadService;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function invitation(string $token): Response
    {
        $invitation = WorkspaceInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->with('workspace')
            ->first();

        return Inertia::render('invitations/Accept', [
            'token' => $token,
            'workspace' => $invitation?->workspace?->name,
            'email' => $invitation?->email,
            'valid' => $invitation?->isAcceptable() ?? false,
        ]);
    }

    public function acceptInvitation(Request $request, string $token, InvitationService $invitations): RedirectResponse
    {
        try {
            $workspace = $invitations->accept($token, $request->user());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('workspaces.dashboard', $workspace)
            ->with('success', 'Undangan diterima.');
    }

    public function uploadLink(string $token): Response
    {
        $link = UploadLink::query()->where('public_token', $token)->with('workspace')->firstOrFail();
        abort_unless($link->isUsable(), 403, 'Link unggah tidak berlaku.');

        return Inertia::render('public/UploadLink', [
            'token' => $token,
            'link' => [
                'name' => $link->name,
                'workspace' => $link->workspace?->name,
                'require_uploader_name' => $link->require_uploader_name,
                'require_pin' => filled($link->pin_hash),
                'remaining' => $link->max_uploads ? max(0, $link->max_uploads - $link->upload_count) : null,
            ],
        ]);
    }

    public function storeUploadLink(Request $request, string $token, DocumentUploadService $uploads): JsonResponse
    {
        $link = UploadLink::query()->where('public_token', $token)->with('workspace')->firstOrFail();
        abort_unless($link->isUsable(), 403, 'Link unggah tidak berlaku.');

        $request->validate([
            'file' => ['required', 'file', 'max:'.config('ainota.documents.max_size_kb'), 'mimetypes:image/jpeg,image/png,image/webp,application/pdf'],
            'uploader_name' => [$link->require_uploader_name ? 'required' : 'nullable', 'string', 'max:80'],
            'pin' => [$link->pin_hash ? 'required' : 'nullable', 'string'],
        ]);

        if ($link->pin_hash && ! Hash::check((string) $request->string('pin'), $link->pin_hash)) {
            abort(403, 'PIN tidak valid.');
        }

        $document = $uploads->store(
            $link->workspace,
            $request->file('file'),
            null,
            DocumentSource::UploadLink,
            null,
            $link,
            $request->string('uploader_name')->toString() ?: null,
        );

        return response()->json([
            'ok' => true,
            'document_id' => $document->id,
            'status' => $document->status->value,
        ], 201);
    }
}
