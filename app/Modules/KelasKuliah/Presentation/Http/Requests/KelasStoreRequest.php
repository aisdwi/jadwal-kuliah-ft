<?php

namespace App\Modules\KelasKuliah\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KelasStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return KelasRules::rules();
    }
}
