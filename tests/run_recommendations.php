<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';
require_once __DIR__ . '/../classes/AiRecommendationService.php';

$symbols = ['BBCA','TLKM','GOTO'];
$results = [];
foreach ($symbols as $s) {
    $s = strtoupper(trim($s));
    $quote = StockData::getQuote($s);
    $rec = AiRecommendationService::buildRecommendation($s, $quote);
    $results[$s] = [
        'quote' => $quote,
        'recommendation' => $rec
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
