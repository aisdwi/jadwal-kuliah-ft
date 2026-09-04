<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Requests;

final class KelasKuliahRules
{
    private const REQUIRED_ID = 'required|integer|min:1';

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'dosen_id' => self::REQUIRED_ID,
            'matakuliah_id' => self::REQUIRED_ID,
            'kelas_id' => self::REQUIRED_ID,
            'jumlah_mahasiswa' => 'nullable|integer|min:0',
            'semester_tipe' => 'nullable|string|in:ganjil,genap',
            'dosen_ids' => 'nullable|array|min:1',
            'dosen_ids.*' => 'integer|exists:dosen,id',
            'preferred_slot_ids' => 'nullable|array',
            'preferred_slot_ids.*' => 'nullable|integer|exists:slot,id',
            'is_externals' => 'nullable|array',
            'is_externals.*' => 'boolean',
            'dosen_team' => 'nullable|array|min:1',
            'dosen_team.*.dosen_id' => 'required_with:dosen_team|integer|exists:dosen,id',
            'dosen_team.*.preferred_slot_id' => 'nullable|integer|exists:slot,id',
            'dosen_team.*.is_external' => 'nullable|boolean',
        ];
    }
}
