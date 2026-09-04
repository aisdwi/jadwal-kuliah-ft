<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Requests;

final class ProgramStudiRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'nama_prodi' => 'required|string|max:255',
            'jurusan_id' => 'required|integer|exists:jurusan,id',
        ];
    }
}
