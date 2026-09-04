<?php

namespace App\Modules\Resource\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RuanganStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'jurusan_ids' => $this->normalizeJurusanIds($this->input('jurusan_ids')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return RuanganRules::rules();
    }

    private function normalizeJurusanIds(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($id) => $id !== null && $id !== ''));
        }

        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($id) => $id !== ''));
        }

        return [];
    }
}
