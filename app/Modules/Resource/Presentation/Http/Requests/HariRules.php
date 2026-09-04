<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

final class HariRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'nama_hari' => 'required|string|max:255',
        ];
    }
}
