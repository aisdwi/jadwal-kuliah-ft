<?php

namespace App\Modules\Penjadwalan\Presentation\Http\Support;

final class SchedulingGenerationRequestData
{
    public static function params(array $validated): array
    {
        return [
            'num_kromosom' => $validated['num_kromosom'] ?? null,
            'max_generation' => $validated['max_generation'] ?? null,
            'crossover_rate' => $validated['crossover_rate'] ?? null,
            'mutation_rate' => $validated['mutation_rate'] ?? null,
        ];
    }

    public static function rules(): array
    {
        return [
            'num_kromosom' => 'nullable|integer|min:10|max:300',
            'max_generation' => 'nullable|integer|min:10|max:500',
            'crossover_rate' => 'nullable|integer|min:10|max:100',
            'mutation_rate' => 'nullable|integer|min:1|max:100',
            'semester_tipe' => 'nullable|string|in:ganjil,genap',
        ];
    }
}
