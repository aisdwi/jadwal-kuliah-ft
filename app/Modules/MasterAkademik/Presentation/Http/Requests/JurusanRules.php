<?php

namespace App\Modules\MasterAkademik\Presentation\Http\Requests;

final class JurusanRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'nama_jurusan' => 'required|string|max:255',
        ];
    }
}
