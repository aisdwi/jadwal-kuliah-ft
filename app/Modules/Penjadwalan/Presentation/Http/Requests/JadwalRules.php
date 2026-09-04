<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Requests;

final class JadwalRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'kelas_kuliah_id' => 'required|integer|exists:kelas_kuliah,id',
            'slot_id' => 'nullable|integer|exists:slot,id|required_with:ruangan_id',
            'ruangan_id' => 'nullable|integer|exists:ruangan,id|required_with:slot_id',
            'origin' => 'sometimes|string|in:manual,generated',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'kelas_kuliah_id.required' => 'Kelas kuliah harus dipilih',
            'kelas_kuliah_id.exists' => 'Kelas kuliah tidak ditemukan',
            'slot_id.required_with' => 'Slot waktu harus dipilih',
            'slot_id.exists' => 'Slot waktu tidak ditemukan',
            'ruangan_id.required_with' => 'Ruangan harus dipilih',
            'ruangan_id.exists' => 'Ruangan tidak ditemukan',
        ];
    }
}
