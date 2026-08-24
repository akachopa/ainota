<?php

namespace App\Http\Middleware;

use App\Enums\MemberStatus;
use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspace = $request->route('workspace');
        $user = $request->user();

        if (! $workspace || ! $user) {
            abort(403);
        }

        $member = $workspace->memberFor($user);
        if (! $member || $member->status !== MemberStatus::Active) {
            abort(403, 'Anda bukan anggota workspace ini.');
        }

        $workspace->loadMissing('settings');
        CurrentWorkspace::set($workspace, $member);
        $request->session()->put('current_workspace_id', $workspace->id);

        return $next($request);
    }
}
