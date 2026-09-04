<?php

namespace App\Modules\Dashboard\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\Iam\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\DB;

final class DashboardActivityQuery
{
    public function getRecentActivity(array $userScope, int $limit = 10): array
    {
        $query = DB::table('activity_logs')->select('*');
        $this->applyScope($query, $userScope);

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($log): array => $this->activityPayload($log))
            ->toArray();
    }

    private function applyScope($query, array $userScope): void
    {
        $roleName = RoleName::normalize($userScope['role_name'] ?? null);

        if ($this->canSeeAllActivity($roleName)) {
            return;
        }

        if (RoleName::isJurusanScoped($roleName) && !empty($userScope['jurusan_id'])) {
            $query->whereIn('user_id', UserModel::where('jurusan_id', $userScope['jurusan_id'])->pluck('id'));
            return;
        }

        $query->where('user_id', $userScope['user_id'] ?? null);
    }

    private function canSeeAllActivity(?string $roleName): bool
    {
        return in_array($roleName, ['Super Admin', 'Admin Fakultas', 'Dekan', 'Wakil Dekan', 'Wakil Dekan I Bidang Akademik'], true);
    }

    private function activityPayload(object $log): array
    {
        $user = UserModel::find($log->user_id);
        $userName = $user ? $user->nama_user . ' - ' : '';

        return [
            'id' => $log->id,
            'title' => $log->description ?: trim(ucfirst((string) $log->action) . ' ' . (string) $log->subject_type),
            'detail' => trim($userName . ($log->subject_name ?: $log->subject_type ?: 'Aktivitas sistem')),
            'time' => now()->parse($log->created_at)->diffForHumans(),
        ];
    }
}
