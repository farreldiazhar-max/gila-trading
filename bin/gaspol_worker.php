<?php
// Simple Gaspol worker CLI
// Usage: php bin/gaspol_worker.php

declare(ticks=1);

$root = dirname(__DIR__);
$configPath = $root . '/config/gaspol_trade.json';
$runtimePath = $root . '/runtime/gaspol_status.json';
$logPath = $root . '/runtime/gaspol_worker.log';

function load_json($path) {
    if (!file_exists($path)) return null;
    $c = @file_get_contents($path);
    return $c === false ? null : json_decode($c, true);
}

function save_json($path, $data) {
    @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function logline($msg) {
    global $logPath;
    $t = date('Y-m-d H:i:s');
    $line = "[$t] $msg\n";
    echo $line;
    @file_put_contents($logPath, $line, FILE_APPEND);
}

function post_json($url, $payload, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $hdrs = array_merge(['Content-Type: application/json'], $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'err' => $err, 'resp' => $resp];
}

if (!is_dir($root . '/runtime')) @mkdir($root . '/runtime', 0755, true);

$config = load_json($configPath);
if ($config === null) {
    logline('WARN: config/gaspol_trade.json not found or invalid. Create from example. Exiting.');
    exit(1);
}

$symbols = $config['symbols'] ?? [];
$simulate = isset($config['simulate']) ? (bool)$config['simulate'] : true;
$delay = isset($config['delay_seconds']) ? (int)$config['delay_seconds'] : 5;
$defaultSize = $config['default_size'] ?? 1;
// allow env override for broker API key
$envApiKey = getenv('STOCKBIT_API_KEY') ?: null;

if ($envApiKey) {
    logline('Using STOCKBIT_API_KEY from environment (config API key will be ignored)');
}

logline('Gaspol worker started. Simulate=' . ($simulate ? 'yes' : 'no'));

while (true) {
    $status = load_json($runtimePath) ?: ['state' => 'stopped'];
    if (isset($status['state']) && $status['state'] === 'running') {
        $mode = $status['mode'] ?? 'auto';
        if ($mode !== 'auto' && $mode !== 'semi-auto') {
            logline("Skipping unknown mode: $mode");
            sleep(2);
            continue;
        }

        foreach ($symbols as $sym) {
            // re-load status so user can stop the worker quickly
            $status = load_json($runtimePath) ?: ['state' => 'stopped'];
            if ($status['state'] !== 'running') break;

            $order = [
                'symbol' => $sym,
                'size' => $defaultSize,
                'side' => 'buy',
                'type' => 'market',
            ];

            if ($simulate) {
                logline("SIMULATED ORDER: " . json_encode($order));
                $status['last_trade'] = ['time' => time(), 'order' => $order, 'simulated' => true];
                save_json($runtimePath, $status);
            } else {
                // Try to send to broker (Stockbit-compatible) using config
                $brokerUrl = rtrim($config['stockbit_api_url'] ?? '', '/');
                $apiKey = $envApiKey ?: ($config['stockbit_api_key'] ?? '');
                if (empty($brokerUrl) || empty($apiKey)) {
                    logline('ERROR: broker url or api key missing (check config or STOCKBIT_API_KEY env); cannot place live order.');
                } else {
                    $url = $brokerUrl . '/orders';
                    logline('Placing order to broker: ' . $url);
                    $res = post_json($url, $order, ['X-API-KEY: ' . $apiKey]);
                    if ($res['err']) {
                        logline('BROKER ERR: ' . $res['err']);
                    } else {
                        logline('BROKER RESP (' . $res['code'] . '): ' . substr($res['resp'] ?? '', 0, 1000));
                        $status['last_trade'] = ['time' => time(), 'order' => $order, 'response' => $res];
                        save_json($runtimePath, $status);
                    }
                }
            }

            sleep($delay);
        }
    }

    // small sleep to avoid busy loop
    sleep(2);
}
