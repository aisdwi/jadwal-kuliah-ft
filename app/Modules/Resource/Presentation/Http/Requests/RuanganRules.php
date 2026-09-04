<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

final class RuanganRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'gedung_id' => 'required|integer|min:1',
            'ruangan' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:1',
            'jurusan_ids' => 'nullable|array',
            'jurusan_ids.*' => 'integer|exists:jurusan,id',
        ];
    }
}
