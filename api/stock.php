<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';

$range = isset($_GET['range']) ? $_GET['range'] : '1mo';
$interval = isset($_GET['interval']) ? $_GET['interval'] : '1d';
$refresh = !empty($_GET['refresh']) || !empty($_GET['force_refresh']);

// Batch mode: return quotes for multiple comma-separated symbols
if (isset($_GET['batch'])) {
    $raw = (string)$_GET['batch'];
    $items = array_values(array_filter(array_map('trim', explode(',', $raw))));
    $result = [];
    foreach ($items as $sym) {
        if ($sym === '') continue;
        $quote = StockData::getQuote($sym, $range, $interval, $refresh);
        $result[strtoupper($sym)] = $quote;
    }
    echo json_encode(['status' => 'success', 'quotes' => $result]);
    exit;
}

// Single symbol mode (legacy)
$symbol = isset($_GET['stock']) ? $_GET['stock'] : 'BBCA';
$stockQuote = StockData::getQuote($symbol, $range, $interval, $refresh);
$signalInfo = SignalGenerator::generateSignal($stockQuote);

echo json_encode([
    'status' => 'success',
    'quote' => $stockQuote,
    'signal' => $signalInfo
]);
