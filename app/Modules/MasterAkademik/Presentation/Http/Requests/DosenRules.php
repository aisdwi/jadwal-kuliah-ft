<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Requests;

final class DosenRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'nip' => 'required|string',
            'nama_lengkap' => 'required|string',
            'inisial' => 'required|string|max:10',
            'jurusan_id' => 'required|integer|exists:jurusan,id',
        ];
    }
}
