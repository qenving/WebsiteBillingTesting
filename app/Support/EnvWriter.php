<?php

namespace App\Support;

use RuntimeException;

class EnvWriter
{
    protected static ?string $customPath = null;

    public static function usePath(?string $path): void
    {
        static::$customPath = $path;
    }

    protected static function path(): string
    {
        return static::$customPath ?? base_path('.env');
    }

    public static function set(array $values): void
    {
        $path = static::path();
        if (! file_exists($path)) {
            $example = base_path('.env.example');
            if (file_exists($example)) {
                copy($example, $path);
            } else {
                file_put_contents($path, '');
            }
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read environment file.');
        }

        foreach ($values as $key => $value) {
            $formatted = static::formatValue($value);
            $pattern = "/^".preg_quote($key, '/')."=.*$/m";
            $replacement = $key.'='.$formatted;
            $updated = preg_replace($pattern, $replacement, $content);
            if ($updated === null) {
                throw new RuntimeException('Unable to update environment file.');
            }
            if ($updated === $content) {
                $content = rtrim($content).PHP_EOL.$replacement;
            } else {
                $content = $updated;
            }
        }

        file_put_contents($path, $content.PHP_EOL);
    }

    protected static function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        $string = (string) $value;
        $needsQuotes = strpbrk($string, " #\n\r\t") !== false;
        if ($needsQuotes) {
            return '"'.addslashes($string).'"';
        }

        return $string;
    }
}
