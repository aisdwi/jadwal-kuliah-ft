<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

final class SlotRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'hari_id' => 'required|integer|min:1',
            'waktu_id' => 'required|integer|min:1',
            'jurusan_id' => 'required|integer|exists:jurusan,id',
        ];
    }
}
