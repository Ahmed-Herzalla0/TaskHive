<?php
/**
 * Global bootstrap for TaskHive.
 * - Loads environment variables from .env if available
 * - Configures secure session defaults
 */

if (!defined('TASKHIVE_BASE_PATH')) {
    define('TASKHIVE_BASE_PATH', realpath(__DIR__ . '/..'));
}

if (!defined('TASKHIVE_ENV_LOADED')) {
    $envFile = TASKHIVE_BASE_PATH . '/.env';
    if (file_exists($envFile) && is_readable($envFile)) {
        $envData = parse_ini_file($envFile, false, INI_SCANNER_TYPED);
        if (is_array($envData)) {
            foreach ($envData as $key => $value) {
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    if (!function_exists('env')) {
        function env(string $key, $default = null) {
            return $_ENV[$key] ?? $default;
        }
    }

    define('TASKHIVE_ENV_LOADED', true);
}

if (php_sapi_name() !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (env('APP_ENV') === 'production');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => env('APP_COOKIE_DOMAIN', ''),
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_name(env('APP_SESSION_NAME', 'taskhive_session'));
    session_start();
}
?>
