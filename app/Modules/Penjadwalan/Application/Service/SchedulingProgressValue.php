<?php

namespace App\Modules\Penjadwalan\Application\Service;

final class SchedulingProgressValue
{
    public static function firstNested(array $primary, array $fallback, string $primaryPath, string $fallbackKey, mixed $default): mixed
    {
        $value = data_get($primary, $primaryPath);

        return $value === null ? self::value($fallback, $fallbackKey, $default) : $value;
    }

    /**
     * @param array<int, array<string, mixed>> $sources
     * @param string|array<int, string> $keys
     */
    public static function first(array $sources, string|array $keys, mixed $default): mixed
    {
        foreach ($sources as $index => $source) {
            $key = is_array($keys) ? $keys[$index] : $keys;
            $value = self::value($source, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    public static function value(array $source, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $source) ? $source[$key] : $default;
    }
}
