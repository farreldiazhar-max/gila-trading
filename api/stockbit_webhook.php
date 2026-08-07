<?php
header('Content-Type: application/json');

$baseDir = __DIR__ . '/../';
$runtimeDir = $baseDir . 'runtime';
if (!is_dir($runtimeDir)) @mkdir($runtimeDir, 0755, true);

// Optional internal key enforcement
$INTERNAL_API_KEY = getenv('INTERNAL_API_KEY') ?: null;
if (!empty($INTERNAL_API_KEY)) {
    $hdr = $_SERVER['HTTP_X_INTERNAL_KEY'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    if (!hash_equals($INTERNAL_API_KEY, $hdr ?? '')) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'error'=>'unauthorized']);
        exit;
    }
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: ['raw' => $raw];

$logFile = $runtimeDir . '/webhooks.log';
$entry = ['time' => time(), 'payload' => $data, 'headers' => getallheaders()];
@file_put_contents($logFile, json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);

// Also append to webhooks.json
$jsonFile = $runtimeDir . '/webhooks.json';
$arr = [];
if (file_exists($jsonFile)) {
    $existing = @file_get_contents($jsonFile);
    $arr = $existing ? json_decode($existing, true) ?? [] : [];
}
$arr[] = $entry;
@file_put_contents($jsonFile, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode(['ok'=>true,'received'=>true]);
