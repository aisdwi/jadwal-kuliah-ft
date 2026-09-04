<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Requests;

final class MataKuliahRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'kode_mk' => 'required|string|max:20',
            'nama_mk' => 'required|string',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'jurusan_id' => 'required|integer|exists:jurusan,id',
            'program_studi_id' => 'required|integer|exists:program_studi,id',
        ];
    }
}
