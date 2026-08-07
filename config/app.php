<?php
// Configuration File for Gila Trading App

define('APP_NAME', 'Gila Trading');
define('APP_VERSION', '1.0.0');
// Dynamic Base URL detection
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost:8000';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (substr($scriptDir, -6) === '/pages') {
        $scriptDir = substr($scriptDir, 0, -6);
    }
    $baseUrl = $protocol . $host . ($scriptDir ? $scriptDir : '') . '/';
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
