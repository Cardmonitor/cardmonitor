<?php

namespace Tests\Support\Snapshots;

class JsonSnapshot
{
    public static function make(string $path, array $data): int|false
    {
        return file_put_contents(self::fullPath($path), json_encode($data, JSON_PRETTY_PRINT));
    }

    public static function get(string $path, callable $callback): array
    {
        $full_path = self::fullPath($path);

        if (!file_exists($full_path)) {
            $data = $callback();
            self::make($path, $data);
            return $data;
        }

        return json_decode(file_get_contents(base_path($path)), true);
    }

    private static function fullPath(string $path): string
    {
        return base_path($path);
    }
}