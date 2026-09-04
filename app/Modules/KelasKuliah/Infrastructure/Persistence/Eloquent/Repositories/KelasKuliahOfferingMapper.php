<?php

namespace App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\KelasKuliah\Domain\Entities\KelasKuliahOffering;
use App\Modules\KelasKuliah\Infrastructure\Persistence\Eloquent\Models\KelasKuliahModel;

final class KelasKuliahOfferingMapper
{
    public function fromModel(KelasKuliahModel $model): KelasKuliahOffering
    {
        return new KelasKuliahOffering(
            kelasId: (int) $model->kelas_id,
            mataKuliahId: (int) $model->matakuliah_id,
            dosenId: (int) $model->dosen_id,
            jumlahMahasiswa: (int) ($model->jumlah_mahasiswa ?? 0),
        );
    }
}
