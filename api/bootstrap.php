<?php
// Спільний бутстрап API: .env, JSON-відповіді, сховище.

define('HYDRO_ROOT', dirname(__DIR__));
define('HYDRO_STORAGE', __DIR__ . '/storage');

function hydro_env(string $key, string $default = ''): string
{
    static $env = null;
    if ($env === null) {
        $env = [];
        $file = HYDRO_ROOT . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $v = trim($v);
                if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
                    $v = trim($v, $v[0]);
                }
                $env[trim($k)] = $v;
            }
        }
    }
    return $env[$key] ?? $default;
}

function hydro_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function hydro_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function hydro_storage_dir(string $sub): string
{
    $dir = HYDRO_STORAGE . '/' . $sub;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}
