<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Support;

use Illuminate\Http\Request;

final class KelasKuliahScheduledFilter
{
    public static function fromRequest(Request $request): ?bool
    {
        $value = $request->input('is_scheduled');

        return $value !== null ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : null;
    }
}
