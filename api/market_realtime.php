<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/app.php';

function fetchJsonWithTimeout($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: id-ID,id;q=0.9,en;q=0.8'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }

    return null;
}

function normalizeSymbol($symbol) {
    $clean = strtoupper(trim((string)$symbol));
    if ($clean === '' || $clean === '^JKSE' || $clean === 'IHSG') {
        return '^JKSE';
    }
    if (strpos($clean, '.') === false) {
        return $clean . '.JK';
    }
    return $clean;
}

function buildFallbackQuote($symbol) {
    $base = [
        'BBCA' => 9850, 'BBRI' => 5300, 'BMRI' => 7250, 'BBNI' => 5800,
        'TLKM' => 3850, 'GOTO' => 65, 'ASII' => 5100, 'UNVR' => 2650,
        'ARTO' => 2540, 'BRPT' => 1020, 'CUAN' => 7800, 'ADRO' => 2680,
        '^JKSE' => 7321.45
    ];
    $clean = strtoupper(str_replace('.JK', '', (string)$symbol));
    $price = $base[$clean] ?? 1000;
    $change = round($price * 0.0125, 2);
    return [
        'symbol' => $clean,
        'price' => $price,
        'change' => $change,
        'changePercent' => 1.25,
        'volume' => 45200000,
        'currency' => 'IDR',
        'source' => 'fallback',
        'lastUpdated' => date('Y-m-d H:i:s')
    ];
}

$symbolsParam = isset($_GET['symbols']) ? trim((string)$_GET['symbols']) : '';
$symbols = array_values(array_filter(array_map('trim', explode(',', $symbolsParam)), function ($v) {
    return $v !== '';
}));

if (empty($symbols)) {
    $symbols = ['BBCA', 'BBRI', 'TLKM', 'BMRI', 'BBNI', 'IHSG'];
}

$quotes = [];

foreach ($symbols as $symbol) {
    $normalized = normalizeSymbol($symbol);
    $url = 'https://query1.finance.yahoo.com/v8/finance/chart/' . urlencode($normalized) . '?interval=1d&range=1d';
    $data = fetchJsonWithTimeout($url);

    $quote = null;
    if (!empty($data['chart']['result'][0])) {
        $result = $data['chart']['result'][0];
        $meta = $result['meta'] ?? [];
        $quoteData = $result['indicators']['quote'][0] ?? [];
        $closes = $quoteData['close'] ?? [];
        $lastClose = end($closes);
        $currentPrice = $meta['regularMarketPrice'] ?? $lastClose ?? 0;
        $prevClose = $meta['chartPreviousClose'] ?? $meta['previousClose'] ?? $currentPrice;
        $change = $currentPrice - $prevClose;
        $changePercent = $prevClose > 0 ? ($change / $prevClose) * 100 : 0;

        $quote = [
            'symbol' => strtoupper(str_replace('.JK', '', $normalized)),
            'price' => round((float)$currentPrice, 2),
            'change' => round((float)$change, 2),
            'changePercent' => round((float)$changePercent, 2),
            'volume' => (int)($meta['regularMarketVolume'] ?? 0),
            'currency' => $meta['currency'] ?? 'IDR',
            'source' => 'yahoo',
            'lastUpdated' => date('Y-m-d H:i:s')
        ];
    }

    if ($quote === null) {
        $quote = buildFallbackQuote($normalized);
        $quote['source'] = 'fallback';
    }

    $quotes[strtoupper($symbol)] = $quote;
}

echo json_encode([
    'status' => 'success',
    'quotes' => $quotes,
    'generatedAt' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
