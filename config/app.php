<?php
// Configuration File for Gila Trading App

define('APP_NAME', 'Gila Trading');
define('APP_VERSION', '1.0.0');
// Dynamic Base URL detection (supports reverse proxies like Render/Fly)
if (!defined('BASE_URL')) {
    // Prefer forwarded proto when present (Render, Fly, proxies)
    $isHttps = false;
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $isHttps = true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        $isHttps = true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        $isHttps = true;
    }
    $protocol = $isHttps ? 'https://' : 'http://';

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost:8000');

    // Determine script directory and normalize path
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = rtrim(dirname($scriptName), '/\\');
    // If running from inside /pages folder, remove that segment
    if (substr($scriptDir, -6) === '/pages') {
        $scriptDir = substr($scriptDir, 0, -6);
    }

    $baseUrl = $protocol . $host . ($scriptDir ? $scriptDir : '') . '/';
    // Ensure no double-slashes
    $baseUrl = preg_replace('#([^:]/)/+#', '$1/', $baseUrl);
    define('BASE_URL', $baseUrl);
}

// Yahoo Finance API Endpoints
define('YAHOO_FINANCE_BASE_URL', 'https://query2.finance.yahoo.com/');
define('YAHOO_FINANCE_DOWNLOAD_URL', 'https://query1.finance.yahoo.com/v7/finance/download/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// AI configuration defaults
if (!defined('AI_PROVIDER_DEFAULT')) {
    define('AI_PROVIDER_DEFAULT', getenv('AI_PROVIDER') ?: 'heuristic');
}
if (!defined('AI_MODEL_DEFAULT')) {
    define('AI_MODEL_DEFAULT', getenv('AI_MODEL') ?: 'gemini-2.0-flash');
}
