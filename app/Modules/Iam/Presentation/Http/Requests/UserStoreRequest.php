<?php

namespace App\Modules\Iam\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UserStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama_user'        => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|string|min:8',
            'role_id'          => 'nullable|integer|exists:role,id',
            'jurusan_id'       => 'nullable|integer|exists:jurusan,id',
            'program_studi_id' => 'nullable|integer|exists:program_studi,id',
            'dosen_id'         => 'nullable|integer|exists:dosen,id',
        ];
    }
}
