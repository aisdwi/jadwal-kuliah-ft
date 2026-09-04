<?php

namespace App\Modules\Iam\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UserUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');

        return [
            'nama_user'        => 'required|string|max:255',
            'email'            => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password'         => 'nullable|string|min:8',
            'role_id'          => 'nullable|integer|exists:role,id',
            'jurusan_id'       => 'nullable|integer|exists:jurusan,id',
            'program_studi_id' => 'nullable|integer|exists:program_studi,id',
            'dosen_id'         => 'nullable|integer|exists:dosen,id',
        ];
    }
}
