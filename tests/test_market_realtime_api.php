<?php
$baseUrl = 'http://127.0.0.1:8000/api/market_realtime.php?symbols=BBCA,BBRI,TLKM';
$payload = @file_get_contents($baseUrl);

if ($payload === false) {
    fwrite(STDERR, "TEST_FAILED: cannot reach market realtime API\n");
    exit(1);
}

$data = json_decode($payload, true);
if (!is_array($data) || ($data['status'] ?? '') !== 'success' || empty($data['quotes'])) {
    fwrite(STDERR, "TEST_FAILED: invalid response\n");
    fwrite(STDERR, $payload . PHP_EOL);
    exit(1);
}

echo "TEST_OK\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
