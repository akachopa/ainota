<?php

namespace App\Providers;

use App\Ai\Contracts\DocumentExtractorInterface;
use App\Ai\FakeDocumentExtractor;
use App\Ai\OpenAI\OpenAiDocumentExtractor;
use App\Models\Document;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\DocumentPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\WorkspacePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentExtractorInterface::class, function () {
            if (app()->environment('testing') || blank(config('openai.api_key'))) {
                return new FakeDocumentExtractor;
            }

            return new OpenAiDocumentExtractor;
        });

        $this->app->singleton(LoginResponse::class, fn () => new class implements LoginResponse
        {
            public function toResponse($request)
            {
                $user = $request->user();
                if ($user instanceof User && $user->workspaces()->count() === 0) {
                    return redirect()->route('workspaces.create');
                }

                return redirect()->intended(route('dashboard'));
            }
        });

        $this->app->singleton(RegisterResponse::class, fn () => new class implements RegisterResponse
        {
            public function toResponse($request)
            {
                return redirect()->route('workspaces.create');
            }
        });
    }

    public function boot(): void
    {
        $this->configureDefaults();

        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Workspace::class, WorkspacePolicy::class);

        Gate::define('viewHorizon', function (?User $user) {
            return $user?->is_platform_admin === true || app()->environment('local');
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by(($request->user()?->id ?: $request->ip()).'|upload');
        });

        RateLimiter::for('ai', function () {
            return Limit::perMinute((int) config('ainota.ai.rate_limit_per_minute'));
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
