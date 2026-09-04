<?php

namespace App\Modules\Laporan\Presentation\Http\Controllers;

use App\Modules\Laporan\Application\Service\LaporanAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaporanController
{
    public function __construct(protected LaporanAppService $service) {}

    public function exportJadwal(Request $request, int $programStudiId): Response
    {
        $request->validate([
            'semester' => 'nullable|integer|min:1|max:8',
        ]);

        $result = $this->service->exportJadwal(
            $programStudiId,
            $request->integer('semester') ?: null,
        );

        return response($result['fileContent'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $result['fileName'] . '"',
            'X-Jumlah-Jadwal' => $result['jumlahJadwal'],
        ]);
    }
}
