<?php
// Settings: a real environment variable wins (e.g. set by Docker), otherwise the optional
// .env file in the project root is used (copy .env.dist to .env).
// Parsing is deliberately minimal: KEY=value lines, '#' comments, optional quotes.
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = [];
        $file = __DIR__ . '/../../.env';
        foreach (file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
            if ($line[0] !== '#' && str_contains($line, '=')) {
                [$name, $val] = explode('=', $line, 2);
                $fileValues[trim($name)] = trim(trim($val), '"\'');
            }
        }
    }
    return $fileValues[$key] ?? $default;
}
