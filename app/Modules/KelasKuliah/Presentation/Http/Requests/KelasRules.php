<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Requests;

final class KelasRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'nama_kelas' => 'required|string|max:255',
            'semester' => 'required|integer|min:1|max:8',
            'program_studi_id' => 'required|integer|min:1',
            'jurusan_id' => 'nullable|integer|min:1',
        ];
    }
}
