<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';
require_once __DIR__ . '/../classes/AiRecommendationService.php';

$symbols = ['BBCA','TLKM','GOTO'];
$results = [];
// remove existing AI recommendation cache to force fresh generation (for testing only)
$cacheDir = __DIR__ . '/../cache/ai_recommendation/';
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . 'rec_*.json') as $f) {
        @unlink($f);
    }
}
foreach ($symbols as $s) {
    $s = strtoupper(trim($s));
    $quote = StockData::getQuote($s);
    $rec = AiRecommendationService::buildRecommendation($s, $quote);
    $results[$s] = [
        'price' => $quote['price'] ?? null,
        'rsi' => $rec['rsi'] ?? null,
        'signal' => $rec['signal'] ?? null,
        'confidence' => $rec['confidence'] ?? null,
        'entry_min' => $rec['entry_min'] ?? null,
        'entry_max' => $rec['entry_max'] ?? null,
        'target_1' => $rec['target_1'] ?? null,
        'target_2' => $rec['target_2'] ?? null,
        'stop_loss' => $rec['stop_loss'] ?? null
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
