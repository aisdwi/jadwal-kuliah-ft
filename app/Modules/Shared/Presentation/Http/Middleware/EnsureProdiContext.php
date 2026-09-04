<?php

namespace App\Modules\Shared\Presentation\Http\Middleware;

use App\Modules\Iam\Application\Support\RoleName;
use App\Modules\MasterAkademik\Infrastructure\Persistence\Eloquent\Models\ProgramStudiModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProdiContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $roleName  = $user->role?->role ?? '';
        $jurusanId = $user->jurusan_id ?: $user->programStudi?->jurusan_id;

        if (! $jurusanId && $user->program_studi_id) {
            $jurusanId = ProgramStudiModel::whereKey($user->program_studi_id)->value('jurusan_id');
        }

        $normalizedRole = RoleName::normalize($roleName);

        if (! RoleName::isUnrestricted($roleName) && $normalizedRole === 'Koordinator Program Studi' && $user->program_studi_id !== null) {
            $request->merge(['program_studi_id' => (int) $user->program_studi_id]);
        } elseif (! RoleName::isUnrestricted($roleName) && RoleName::isJurusanScoped($roleName) && $jurusanId !== null) {
            $request->merge(['jurusan_id' => $jurusanId]);
        }

        return $next($request);
    }
}
