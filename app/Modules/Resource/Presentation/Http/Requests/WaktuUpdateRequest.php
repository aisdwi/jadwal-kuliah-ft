<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaktuUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return WaktuRules::rules();
    }
}
