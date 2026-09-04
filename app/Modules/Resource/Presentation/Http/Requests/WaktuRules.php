<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

final class WaktuRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'pukul' => 'required|string|max:255',
            'sks' => 'required|integer|min:1',
            'jam_index' => 'nullable|integer|min:0',
        ];
    }
}
