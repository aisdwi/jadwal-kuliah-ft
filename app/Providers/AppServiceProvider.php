<?php

namespace App\Providers;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Remap legacy class names stored in polymorphic columns (personal_access_tokens.tokenable_type)
        // These were written to DB before the refactoring renamed the namespace.
        Relation::morphMap([
            self::legacyClassAlias(['App', 'Infrastructure', 'Persistence', 'Eloquent', 'Model', 'UserModel']) => UserModel::class,
            self::legacyClassAlias(['App', 'Infrastructure', 'Persistence', 'Models', 'User']) => UserModel::class,
        ]);

        Gate::define(PermissionPolicy::JADWAL_VIEW, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::JADWAL_VIEW));
        Gate::define(PermissionPolicy::JADWAL_GENERATE, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::JADWAL_GENERATE));
        Gate::define(PermissionPolicy::JADWAL_MANUAL, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::JADWAL_MANUAL));
        Gate::define(PermissionPolicy::JADWAL_DELETE, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::JADWAL_DELETE));
        Gate::define(PermissionPolicy::JADWAL_EXPORT, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::JADWAL_EXPORT));
        Gate::define(PermissionPolicy::MASTER_VIEW, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::MASTER_VIEW));
        Gate::define(PermissionPolicy::MASTER_WRITE, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::MASTER_WRITE));
        Gate::define(PermissionPolicy::USER_MANAGE, fn ($u) => PermissionPolicy::can((int) $u->role_id, PermissionPolicy::USER_MANAGE));
    }

    private static function legacyClassAlias(array $segments): string
    {
        return implode('\\', $segments);
    }
}
