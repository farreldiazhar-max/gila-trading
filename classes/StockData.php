<?php
/**
 * Class StockData
 * Fetches real-time quotes and historical market data from Yahoo Finance API with caching layer.
 */

class StockData {
    private static $cacheDir = __DIR__ . '/../cache/';
    private static $cacheTTL = 15; // Real-time cache TTL (15 seconds)

    private static function ensureCacheDir() {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }
    }

    /**
     * Helper to perform HTTP GET request with Browser User-Agent to bypass Yahoo blocking
     */
    private static function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5'
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($httpCode === 200 && $response) {
            return $response;
        }
        return null;
    }

    /**
     * Format ticker for Yahoo Finance (adds .JK if missing and not index)
     */
    public static function formatSymbol($symbol) {
        $symbol = strtoupper(trim($symbol));
        if ($symbol === '^JKSE' || $symbol === 'IHSG') {
            return '^JKSE';
        }
        if (strpos($symbol, '.') === false) {
            return $symbol . '.JK';
        }
        return $symbol;
    }

    /**
     * Fetch real-time chart & price quote for a symbol
     */
    public static function getQuote($symbol, $range = '1mo', $interval = '1d', $bypassCache = false) {
        self::ensureCacheDir();
        $formattedSymbol = self::formatSymbol($symbol);
        $cacheFile = self::$cacheDir . 'quote_' . md5($formattedSymbol . '_' . $range . '_' . $interval) . '.json';

        // Check cache unless a live refresh is explicitly requested
        if (!$bypassCache && file_exists($cacheFile) && (time() - filemtime($cacheFile) < self::$cacheTTL)) {
            $cachedContent = file_get_contents($cacheFile);
            $data = json_decode($cachedContent, true);
            if ($data) return $data;
        }

        $url = "https://query2.finance.yahoo.com/v8/finance/chart/" . urlencode($formattedSymbol) . "?range=" . urlencode($range) . "&interval=" . urlencode($interval);
        $jsonStr = self::fetchUrl($url);

        if ($jsonStr) {
            $raw = json_decode($jsonStr, true);
            if (isset($raw['chart']['result'][0])) {
                $result = $raw['chart']['result'][0];
                $meta = $result['meta'] ?? [];
                $timestamp = $result['timestamp'] ?? [];
                $quote = $result['indicators']['quote'][0] ?? [];

                $currentPrice = $meta['regularMarketPrice'] ?? 0;
                $prevClose = $meta['chartPreviousClose'] ?? $meta['previousClose'] ?? $currentPrice;
                $change = $currentPrice - $prevClose;
                $changePercent = $prevClose > 0 ? ($change / $prevClose) * 100 : 0;

                // Process OHLCV data array
                $prices = [];
                $seenDates = [];
                $opens = $quote['open'] ?? [];
                $highs = $quote['high'] ?? [];
                $lows = $quote['low'] ?? [];
                $closes = $quote['close'] ?? [];
                $volumes = $quote['volume'] ?? [];

                // Determine if we should keep intraday timestamps (numeric) or daily date strings.
                $useNumericTime = false;
                $intervalLower = strtolower($interval);
                if (strpos($intervalLower, 'm') !== false || strpos($intervalLower, 'h') !== false || $range === '1d') {
                    $useNumericTime = true;
                }

                for ($i = 0; $i < count($timestamp); $i++) {
                    if (isset($closes[$i]) && $closes[$i] !== null) {
                        $timeVal = $useNumericTime ? (int)$timestamp[$i] : date('Y-m-d', $timestamp[$i]);
                        // prevent duplicate entries for daily views
                        $dedupeKey = is_int($timeVal) ? $timeVal : $timeVal;
                        if (!isset($seenDates[$dedupeKey])) {
                            $seenDates[$dedupeKey] = true;
                            $openVal = round($opens[$i] ?? $closes[$i], 2);
                            $closeVal = round($closes[$i], 2);
                            $prices[] = [
                                'time' => $timeVal,
                                'open' => $openVal,
                                'high' => round($highs[$i] ?? max($openVal, $closeVal), 2),
                                'low' => round($lows[$i] ?? min($openVal, $closeVal), 2),
                                'close' => $closeVal,
                                'volume' => $volumes[$i] ?? 0,
                                'timestamp' => (int)$timestamp[$i]
                            ];
                        }
                    }
                }

                // Sort ascending by date
                usort($prices, function($a, $b) {
                    return strcmp($a['time'], $b['time']);
                });

                $formattedData = [
                    'symbol' => str_replace('.JK', '', $formattedSymbol),
                    'yahoo_symbol' => $formattedSymbol,
                    'shortName' => $meta['shortName'] ?? $meta['symbol'] ?? $formattedSymbol,
                    'price' => $currentPrice,
                    'prevClose' => $prevClose,
                    'change' => round($change, 2),
                    'changePercent' => round($changePercent, 2),
                    'volume' => $meta['regularMarketVolume'] ?? (end($volumes) ?: 0),
                    'currency' => $meta['currency'] ?? 'IDR',
                    'history' => $prices,
                    'lastUpdated' => date('Y-m-d H:i:s')
                ];

                file_put_contents($cacheFile, json_encode($formattedData));
                return $formattedData;
            }
        }

        // Return fallback data if API request fails
        return self::getFallbackQuote($symbol);
    }

    /**
     * Fallback generator when Yahoo Finance is unreachable or rate limited
     */
    private static function getFallbackQuote($symbol) {
        $cleanSymbol = str_replace('.JK', '', strtoupper($symbol));
        $basePrices = [
            'BBCA' => 9850, 'BBRI' => 5300, 'BMRI' => 7250, 'BBNI' => 5800,
            'TLKM' => 3850, 'GOTO' => 65, 'ASII' => 5100, 'UNVR' => 2650,
            'ARTO' => 2540, 'BRPT' => 1020, 'CUAN' => 7800, 'ADRO' => 2680,
            '^JKSE' => 7321.45, 'IHSG' => 7321.45
        ];

        $basePrice = $basePrices[$cleanSymbol] ?? 1000;
        $change = round(($basePrice * 0.0125), 2);
        $changePercent = 1.25;

        // Generate 30 days dummy history
        $history = [];
        $today = time();
        for ($i = 30; $i >= 0; $i--) {
            $t = $today - ($i * 86400);
            $variation = (sin($i) * 0.02) + 1;
            $p = round($basePrice * $variation, 2);
            $history[] = [
                'time' => date('Y-m-d', $t),
                'timestamp' => $t,
                'open' => round($p * 0.995, 2),
                'high' => round($p * 1.01, 2),
                'low' => round($p * 0.99, 2),
                'close' => $p,
                'volume' => rand(1000000, 50000000)
            ];
        }

        return [
            'symbol' => $cleanSymbol,
            'yahoo_symbol' => self::formatSymbol($symbol),
            'shortName' => $cleanSymbol . ' Tbk.',
            'price' => $basePrice,
            'prevClose' => round($basePrice - $change, 2),
            'change' => $change,
            'changePercent' => $changePercent,
            'volume' => 45200000,
            'currency' => 'IDR',
            'history' => $history,
            'isFallback' => true,
            'lastUpdated' => date('Y-m-d H:i:s')
        ];
    }
}
