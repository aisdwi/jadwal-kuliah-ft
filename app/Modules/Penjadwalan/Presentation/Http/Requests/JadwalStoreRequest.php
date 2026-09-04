<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Requests;

use App\Modules\Iam\Application\Service\PermissionPolicy;
use Illuminate\Foundation\Http\FormRequest;

class JadwalStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PermissionPolicy::JADWAL_MANUAL);
    }

    public function rules(): array
    {
        return JadwalRules::rules();
    }

    protected function prepareForValidation(): void
    {
        $path = $this->path();
        if (
            !$this->filled('kelas_kuliah_id')
            && is_string($path)
            && preg_match('#kelas-kuliah/\d+/jadwal$#', $path)
        ) {
            $this->merge([
                'kelas_kuliah_id' => $this->route('id'),
            ]);
        }
    }

    public function messages(): array
    {
        return JadwalRules::messages();
    }
}
