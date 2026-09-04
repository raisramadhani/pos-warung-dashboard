<?php

namespace App\Providers;

use App\Enums\RoleType;
use App\Filament\Merchant\Responses\LoginResponse;
use App\Filament\Merchant\Responses\LogoutResponse;
use App\Models\Activity as ModelsActivity;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Activitylog\Facades\Activity;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LogoutResponse::class, LogoutResponse::class);
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(app()->isLocal());

        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }

        /**
         * Ensure a user can only hit ten 404 responses in a minute before they are
         * rate limited to ensure user's cannot enumerate resource IDs.
         */
        RateLimiter::for('resource-not-found', function (Request $request) {
            $id = method_exists($request->user(), 'getKey') ? $request->user()->getKey() : $request->ip();

            return Limit::perMinute(10)
                ->by("user:{$id}")
                ->after(fn (Response $response): bool => $response->getStatusCode() === 404);
        });

        $batchUuid = (string) Str::orderedUuid();

        Gate::define('access-pos', function (User $user): bool {
            return $user->role === RoleType::Merchant;
        });

        Activity::beforeLogging(function (ModelsActivity $activity) use ($batchUuid) {
            $tenantId = Filament::getTenant()?->getKey();
            $activity->properties = $activity->properties->put('ip', request()->ip());
            $activity->properties = $activity->properties->put('tenant_id', $tenantId);
            $activity->batch_uuid = $batchUuid;
            $activity->tenant_id = $tenantId;
        });
    }
}
