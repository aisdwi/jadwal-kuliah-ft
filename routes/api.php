<?php

use App\Modules\Iam\Presentation\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth API routes (session-based via Sanctum)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// V2 API surface used by the React SPA and mobile-friendly clients.
Route::prefix('v2')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'prodi.context'])->group(function () {
        Route::get('dashboard/stats', [\App\Modules\Dashboard\Presentation\Http\Controllers\DashboardController::class, 'stats']);
        Route::get('dashboard/chart', [\App\Modules\Dashboard\Presentation\Http\Controllers\DashboardController::class, 'chart']);
        Route::get('dashboard/activity', [\App\Modules\Dashboard\Presentation\Http\Controllers\DashboardController::class, 'activity']);
        Route::get('notifications', [\App\Modules\Shared\Presentation\Http\Controllers\NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [\App\Modules\Shared\Presentation\Http\Controllers\NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [\App\Modules\Shared\Presentation\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::patch('notifications/{notificationId}/read', [\App\Modules\Shared\Presentation\Http\Controllers\NotificationController::class, 'markAsRead']);

        Route::get('referensi/jurusan', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'jurusan']);
        Route::get('referensi/program-studi', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'programStudi']);
        Route::get('referensi/gedung', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'gedung']);
        Route::get('referensi/hari', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'hari']);
        Route::get('referensi/waktu', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'waktu']);
        Route::get('referensi/slot', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'slot']);
        Route::get('referensi/roles', [\App\Modules\Shared\Presentation\Http\Controllers\ReferensiController::class, 'roles']);

        Route::post('dosen/import', [\App\Modules\MasterAkademik\Presentation\Http\Controllers\DosenController::class, 'import']);
        Route::delete('dosen', [\App\Modules\MasterAkademik\Presentation\Http\Controllers\DosenController::class, 'destroyAll']);
        Route::apiResource('dosen', \App\Modules\MasterAkademik\Presentation\Http\Controllers\DosenController::class);

        Route::post('matakuliah/import', [\App\Modules\MasterAkademik\Presentation\Http\Controllers\MataKuliahController::class, 'import']);
        Route::delete('matakuliah', [\App\Modules\MasterAkademik\Presentation\Http\Controllers\MataKuliahController::class, 'destroyAll']);
        Route::apiResource('matakuliah', \App\Modules\MasterAkademik\Presentation\Http\Controllers\MataKuliahController::class);

        Route::delete('jurusan', [\App\Modules\MasterAkademik\Presentation\Http\Controllers\JurusanController::class, 'destroyAll']);
        Route::apiResource('jurusan', \App\Modules\MasterAkademik\Presentation\Http\Controllers\JurusanController::class);
        Route::delete('program-studi', [\App\Modules\MasterAkademik\Presentation\Http\Controllers\ProgramStudiController::class, 'destroyAll']);
        Route::apiResource('program-studi', \App\Modules\MasterAkademik\Presentation\Http\Controllers\ProgramStudiController::class);

        Route::delete('ruangan', [\App\Modules\Resource\Presentation\Http\Controllers\RuanganController::class, 'destroyAll']);
        Route::apiResource('ruangan', \App\Modules\Resource\Presentation\Http\Controllers\RuanganController::class);
        Route::delete('hari', [\App\Modules\Resource\Presentation\Http\Controllers\HariController::class, 'destroyAll']);
        Route::apiResource('hari', \App\Modules\Resource\Presentation\Http\Controllers\HariController::class);
        Route::delete('waktu', [\App\Modules\Resource\Presentation\Http\Controllers\WaktuController::class, 'destroyAll']);
        Route::apiResource('waktu', \App\Modules\Resource\Presentation\Http\Controllers\WaktuController::class);
        Route::delete('slot', [\App\Modules\Resource\Presentation\Http\Controllers\SlotController::class, 'destroyAll']);
        Route::apiResource('slot', \App\Modules\Resource\Presentation\Http\Controllers\SlotController::class);

        Route::get('users/roles', [\App\Modules\Iam\Presentation\Http\Controllers\UserController::class, 'roles']);
        Route::delete('users', [\App\Modules\Iam\Presentation\Http\Controllers\UserController::class, 'destroyAll']);
        Route::apiResource('users', \App\Modules\Iam\Presentation\Http\Controllers\UserController::class);

        Route::post('kelas/import', [\App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasController::class, 'import']);
        Route::delete('kelas', [\App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasController::class, 'destroyAll']);
        Route::apiResource('kelas', \App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasController::class);

        Route::get('kelas-kuliah/stats', [\App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasKuliahController::class, 'stats']);
        Route::post('kelas-kuliah/import', [\App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasKuliahController::class, 'import']);
        Route::delete('kelas-kuliah', [\App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasKuliahController::class, 'destroyAll']);
        Route::apiResource('kelas-kuliah', \App\Modules\KelasKuliah\Presentation\Http\Controllers\KelasKuliahController::class);

        Route::get('jadwal', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\JadwalController::class, 'index']);
        Route::get('jadwal/{id}', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\JadwalController::class, 'show']);
        Route::post('jadwal/generate', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\JadwalController::class, 'generate']);
        Route::put('jadwal/{id}/reassign', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\JadwalController::class, 'reassign']);
        Route::put('kelas-kuliah/{id}/jadwal', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\JadwalController::class, 'manualAssign']);
        Route::delete('jadwal/{id}', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\JadwalController::class, 'destroy']);

        Route::get('scheduling/preview', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'preview']);
        Route::post('scheduling/generate', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'generate']);
        Route::post('scheduling/cancel', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'cancel']);
        Route::post('scheduling/restore-last', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'restoreLast']);
        Route::post('scheduling/clear-all', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'clearAll']);
        Route::post('scheduling/reset-auto', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'resetAuto']);
        Route::get('scheduling/progress', [\App\Modules\Penjadwalan\Presentation\Http\Controllers\SchedulingController::class, 'progress']);

        Route::get('prodi/{programStudiId}/jadwal/export', [\App\Modules\Laporan\Presentation\Http\Controllers\LaporanController::class, 'exportJadwal']);
    });
});
