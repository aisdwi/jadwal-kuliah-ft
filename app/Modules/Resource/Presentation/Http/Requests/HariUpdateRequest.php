<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HariUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return HariRules::rules();
    }
}
