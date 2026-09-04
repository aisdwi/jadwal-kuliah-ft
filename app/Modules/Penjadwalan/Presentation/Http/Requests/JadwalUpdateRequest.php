<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Requests;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use Illuminate\Foundation\Http\FormRequest;

class JadwalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PermissionPolicy::JADWAL_MANUAL);
    }

    public function rules(): array
    {
        return JadwalRules::rules();
    }

    public function messages(): array
    {
        return JadwalRules::messages();
    }
}
