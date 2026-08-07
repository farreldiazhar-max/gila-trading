<?php
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? ($_SERVER['REQUEST_METHOD']=='GET' ? ($_GET['action'] ?? null) : null);

$baseDir = __DIR__ . '/../';
$configFile = $baseDir . 'config/gaspol_trade.json';
$runtimeDir = $baseDir . 'runtime';
if (!is_dir($runtimeDir)) @mkdir($runtimeDir, 0755, true);

// Security: optional internal API key (set INTERNAL_API_KEY env var to enable)
$INTERNAL_API_KEY = getenv('INTERNAL_API_KEY') ?: null;

function require_internal_key() {
    global $INTERNAL_API_KEY;
    if (empty($INTERNAL_API_KEY)) return true; // not enforced
    $hdr = $_SERVER['HTTP_X_INTERNAL_KEY'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    return hash_equals($INTERNAL_API_KEY, $hdr ?? '');
}

function readConfig($path) {
    if (!file_exists($path)) return [];
    $json = @file_get_contents($path);
    return $json ? json_decode($json, true) : [];
}

function writeConfig($path, $data) {
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

if ($action === 'get_config') {
    $cfg = readConfig($configFile);
    $envKeyPresent = !empty(getenv('STOCKBIT_API_KEY'));
    $internalRequired = !empty($INTERNAL_API_KEY);
    // Do not leak stored API key in response
    if (isset($cfg['stockbit_api_key'])) unset($cfg['stockbit_api_key']);
    echo json_encode(['ok' => true, 'config' => $cfg, 'env_key_present' => $envKeyPresent, 'internal_key_required' => $internalRequired]);
    exit;
}

if ($action === 'save_config') {
    // If INTERNAL_API_KEY is set, require it to save sensitive keys
    if (!require_internal_key()) { echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { echo json_encode(['ok'=>false,'error'=>'invalid_payload']); exit; }
    // Prevent accidental storing of broker API keys unless explicitly allowed and authorized
    $allowSaveKey = isset($body['allow_save_key']) ? (bool)$body['allow_save_key'] : false;
    if (!$allowSaveKey && isset($body['stockbit_api_key'])) {
        unset($body['stockbit_api_key']);
    }
    $ok = writeConfig($configFile, $body);
    echo json_encode(['ok' => $ok ? true : false]);
    exit;
}

if ($action === 'start') {
    if (!require_internal_key()) { echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
    $statusFile = $runtimeDir . '/gaspol_status.json';
    $status = ['running' => true, 'started_at' => time()];
    file_put_contents($statusFile, json_encode($status));
    echo json_encode(['ok' => true]); exit;
}

if ($action === 'stop') {
    if (!require_internal_key()) { echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
    $statusFile = $runtimeDir . '/gaspol_status.json';
    $status = ['running' => false, 'stopped_at' => time()];
    file_put_contents($statusFile, json_encode($status));
    echo json_encode(['ok' => true]); exit;
}

if ($action === 'preflight') {
    // preflight should be allowed without internal key (UI needs it), but will use env key if present
    $cfg = readConfig($configFile);
    $envKey = getenv('STOCKBIT_API_KEY');
    $apiKey = $envKey ?: ($cfg['stockbit_api_key'] ?? null);
    if (empty($cfg['stockbit_api_url']) || empty($apiKey)) {
        echo json_encode(['ok' => false, 'message' => 'Broker configuration missing (URL or API key)']);
        exit;
    }
    $url = rtrim($cfg['stockbit_api_url'], '/');
    // Try a simple GET to the base url to see if reachable
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $reachable = ($res !== false && $code >= 200 && $code < 400);
    echo json_encode(['ok' => true, 'reachable' => $reachable, 'http_code' => $code, 'message' => $err ?: '']);
    exit;
}

if ($action === 'trade') {
    if (!require_internal_key()) { echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) { echo json_encode(['ok'=>false,'error'=>'invalid_payload']); exit; }
    $cfg = readConfig($configFile);
    $symbol = $body['symbol'] ?? null;
    $side = $body['side'] ?? 'BUY';
    $size = $body['size'] ?? 1;

    // Prefer env key if present
    $envKey = getenv('STOCKBIT_API_KEY');
    $apiKey = $envKey ?: ($cfg['stockbit_api_key'] ?? null);

    // If Stockbit config exists, attempt to call their REST endpoint (user must configure)
    if (!empty($cfg['stockbit_api_url']) && !empty($apiKey)) {
        $url = rtrim($cfg['stockbit_api_url'], '/') . '/orders';
        $payload = ['symbol' => $symbol, 'side' => $side, 'size' => $size];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false || $code >= 400) {
            echo json_encode(['ok'=>false,'error'=>'broker_error','detail'=>$err ?: $res]);
            exit;
        }
        $decoded = json_decode($res, true);
        echo json_encode(['ok' => true, 'result' => $decoded]);
        exit;
    }

    // No broker configured — return simulated execution
    $sim = ['symbol'=>$symbol,'side'=>$side,'size'=>$size,'price'=>rand(1000,20000),'simulated'=>true];
    echo json_encode(['ok'=>true,'result'=>$sim]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'unsupported_action']);
