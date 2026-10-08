<?php
// Settings from the optional .env file (copy .env.dist to .env).

// Returns the value of $key, or $default when the key is missing.
// Parsing is deliberately minimal: KEY=value lines, '#' comments, optional quotes.
function env(string $key, ?string $default = null): ?string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        $file = __DIR__ . '/../.env';
        foreach (file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
            if ($line[0] !== '#' && str_contains($line, '=')) {
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim(trim($value), '"\'');
            }
        }
    }
    return $values[$key] ?? $default;
}
