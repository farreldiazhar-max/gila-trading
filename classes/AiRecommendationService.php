<?php
/**
 * AI recommendation service combining technical signals, fundamental snapshot,
 * and free news sentiment signals for the stock analysis experience.
 */
class AiRecommendationService {
    private static $cacheDir = __DIR__ . '/../cache/ai_recommendation/';
    private static $cacheTTL = 30; // Cache recommendation results for 30 seconds

    private static function ensureCacheDir() {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }
    }

    private static function getCacheFilePath($symbol) {
        $key = strtoupper(trim($symbol));
        return self::$cacheDir . 'rec_' . md5($key) . '.json';
    }

    private static function loadRecommendationCache($symbol) {
        $filePath = self::getCacheFilePath($symbol);
        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        if (time() - filemtime($filePath) > self::$cacheTTL) {
            return null;
        }

        return $data;
    }

    private static function saveRecommendationCache($symbol, $recommendation) {
        $filePath = self::getCacheFilePath($symbol);
        @file_put_contents($filePath, json_encode($recommendation, JSON_UNESCAPED_UNICODE));
    }

    public static function buildRecommendation($symbol, $quote = null) {
        require_once __DIR__ . '/StockData.php';
        require_once __DIR__ . '/SignalGenerator.php';

        self::ensureCacheDir();
        $cached = self::loadRecommendationCache($symbol);
        if ($cached !== null) {
            return $cached;
        }

        if ($quote === null) {
            $quote = StockData::getQuote($symbol);
        }

        $technical = SignalGenerator::generateSignal($quote);
        $fundamental = self::getFundamentalSnapshot($symbol, $quote);
        $sentiment = self::getNewsSentiment($symbol, $quote, $technical);

        $technicalScore = (int)($technical['confidence'] ?? 60);
        $fundamentalScore = (int)($fundamental['score'] ?? 50);
        $sentimentScore = (int)($sentiment['score'] ?? 50);

        $combinedScore = round((($technicalScore * 0.45) + ($fundamentalScore * 0.3) + ($sentimentScore * 0.25)), 0);
        $signal = self::mapScoreToSignal($combinedScore, $technical['signal'] ?? 'HOLD');
        $confidence = self::clamp($combinedScore, 55, 95);

        $reasoning = self::buildReasoning($quote, $technical, $fundamental, $sentiment, $signal);
        $deepAnalysis = self::getDeepAnalysis($symbol, $quote, $technical, $fundamental, $sentiment, $signal);
        $technicalNarrative = self::buildTechnicalNarrative($quote, $technical, $signal);

        $recommendation = [
            'signal' => $signal,
            'confidence' => $confidence,
            'rsi' => $technical['rsi'] ?? 50,
            'macd' => $technical['macd'] ?? ['trend' => 'NEUTRAL'],
            'bandarmology' => $technical['bandarmology'] ?? ['status' => 'NEUTRAL', 'score' => 50, 'buy_power' => 0, 'sell_power' => 0, 'comment' => 'Bandarmology belum tersedia.'],
            'entry_min' => $technical['entry_min'] ?? ($quote['price'] ?? 0),
            'entry_max' => $technical['entry_max'] ?? ($quote['price'] ?? 0),
            'target_1' => $technical['target_1'] ?? (($quote['price'] ?? 0) * 1.03),
            'target_2' => $technical['target_2'] ?? (($quote['price'] ?? 0) * 1.06),
            'stop_loss' => $technical['stop_loss'] ?? (($quote['price'] ?? 0) * 0.97),
            'risk_reward' => $technical['risk_reward'] ?? '1 : 2.0',
            'reasoning' => $reasoning,
            'technical_narrative' => $technicalNarrative,
            'deep_analysis' => $deepAnalysis['summary'] ?? $reasoning,
            'strengths' => $deepAnalysis['strengths'] ?? [],
            'risks' => $deepAnalysis['risks'] ?? [],
            'next_step' => $deepAnalysis['next_step'] ?? 'Pantau area support dan resistance.',
            'ai_provider' => $deepAnalysis['provider'] ?? 'heuristic',
            'technical_signal' => $technical['signal'] ?? 'HOLD',
            'technical_confidence' => $technical['confidence'] ?? 60,
            'fundamental' => $fundamental,
            'sentiment' => $sentiment,
            'bandarmology' => $technical['bandarmology'] ?? ['status' => 'NEUTRAL', 'score' => 50, 'buy_power' => 0, 'sell_power' => 0, 'comment' => 'Bandarmology belum tersedia.'],
            'sources' => [
                'price' => 'Yahoo Finance',
                'fundamental' => 'Yahoo Finance quote summary / fallback profile',
                'sentiment' => 'Alpha Vantage NEWS_SENTIMENT (demo/free) / fallback heuristic',
                'bandarmology' => 'Heuristic volume/order-flow estimate dari sejarah harga dan volume'
            ]
        ];

        self::saveRecommendationCache($symbol, $recommendation);
        // Ensure recommendation numeric targets are consistent before returning
        self::ensureRecommendationConsistency($recommendation);
        self::saveRecommendationCache($symbol, $recommendation);
        return $recommendation;
    }

    private static function ensureRecommendationConsistency(array &$rec) {
        $price = (float)($rec['entry_min'] ?? $rec['entry_max'] ?? ($rec['price'] ?? 0));
        $t1 = isset($rec['target_1']) ? (float)$rec['target_1'] : 0.0;
        $t2 = isset($rec['target_2']) ? (float)$rec['target_2'] : 0.0;
        $sl = isset($rec['stop_loss']) ? (float)$rec['stop_loss'] : null;
        $sig = strtoupper((string)($rec['signal'] ?? 'HOLD'));

        // Normalize entry range
        if (isset($rec['entry_min'], $rec['entry_max'])) {
            if ((float)$rec['entry_min'] > (float)$rec['entry_max']) {
                $tmp = $rec['entry_min'];
                $rec['entry_min'] = $rec['entry_max'];
                $rec['entry_max'] = $tmp;
            }
        }

        // BUY-like: targets should be above price and target_2 >= target_1
        if (strpos($sig, 'BUY') !== false) {
            if ($t1 <= 0) $t1 = $price * 1.02;
            if ($t2 <= 0) $t2 = $t1 * 1.03;
            if ($t1 < $price) $t1 = max($t1, $price * 1.01);
            // enforce minimal separation and ensure t2 >= t1
            $minSep = max(0.01, $t1 * 0.001);
            if ($t2 < $t1 + $minSep) $t2 = $t1 + $minSep;
            if ($sl !== null && $sl >= $t1) $sl = $price * 0.98;
        }

        // SELL-like: targets should be below price and target_2 <= target_1
        if (strpos($sig, 'SELL') !== false) {
            if ($t1 <= 0) $t1 = $price * 0.98;
            if ($t2 <= 0) $t2 = $t1 * 0.97;
            if ($t1 > $price) $t1 = min($t1, $price * 0.99);
            // enforce minimal separation and ensure t2 <= t1
            $minSep = max(0.01, $t1 * 0.001);
            if ($t2 > $t1 - $minSep) $t2 = $t1 - $minSep;
            if ($sl !== null && $sl <= $t1) $sl = $price * 1.02;
        }

        // For non-buy/sell keep a sensible order
        if (strpos($sig, 'BUY') === false && strpos($sig, 'SELL') === false) {
            if ($t1 > 0 && $t2 > 0 && $t2 < $t1) {
                $t2 = $t1 * 1.03;
            }
        }

        $rec['target_1'] = round($t1, 2);
        $rec['target_2'] = round($t2, 2);
        if ($sl !== null) $rec['stop_loss'] = round($sl, 2);
    }

    private static function getFundamentalSnapshot($symbol, $quote) {
        $formattedSymbol = StockData::formatSymbol($symbol);
        $url = 'https://query1.finance.yahoo.com/v10/finance/quoteSummary/' . urlencode($formattedSymbol) . '?modules=financialData,defaultKeyStatistics,summaryProfile';
        $response = self::fetchUrl($url);

        $profile = self::getFallbackFundamentalProfile($symbol);
        if ($response === null) {
            return $profile;
        }

        $json = json_decode($response, true);
        $quoteSummary = $json['quoteSummary']['result'][0] ?? [];
        $financialData = $quoteSummary['financialData'] ?? [];
        $stats = $quoteSummary['defaultKeyStatistics'] ?? [];
        $summary = $quoteSummary['summaryProfile'] ?? [];

        $per = self::parseNumber($stats['forwardPE']['raw'] ?? null);
        $pbv = self::parseNumber($stats['priceToBook']['raw'] ?? null);
        $roe = self::parseNumber($financialData['returnOnEquity']['raw'] ?? null);
        $growth = self::parseNumber($financialData['earningsGrowth']['raw'] ?? null);
        $margin = self::parseNumber($financialData['profitMargins']['raw'] ?? null);
        $debt = self::parseNumber($financialData['debtToEquity']['raw'] ?? null);

        if ($per === null) $per = $profile['per'];
        if ($pbv === null) $pbv = $profile['pbv'];
        if ($roe === null) $roe = $profile['roe'];
        if ($growth === null) $growth = $profile['growth'];
        if ($margin === null) $margin = $profile['margin'];
        if ($debt === null) $debt = $profile['debt'];

        $score = 50;
        if ($roe !== null && $roe > 12) $score += 15;
        if ($per !== null && $per < 20) $score += 10;
        if ($pbv !== null && $pbv < 3) $score += 8;
        if ($growth !== null && $growth > 8) $score += 8;
        if ($margin !== null && $margin > 10) $score += 7;

        return [
            'per' => round($per, 1),
            'pbv' => round($pbv, 1),
            'roe' => round($roe, 1),
            'growth' => round($growth, 1),
            'margin' => round($margin, 1),
            'debt' => round($debt, 2),
            'summary' => $summary['sector'] ? 'Sektor ' . $summary['sector'] . ' dengan profil fundamental yang dipantau melalui Yahoo Finance.' : $profile['summary'],
            'score' => self::clamp($score, 35, 95),
            'label' => $score >= 65 ? 'POSITIF' : ($score <= 45 ? 'NEGATIF' : 'NETRAL')
        ];
    }

    private static function getNewsSentiment($symbol, $quote, $technical) {
        $url = 'https://www.alphavantage.co/query?function=NEWS_SENTIMENT&tickers=' . urlencode($symbol) . '&apikey=demo';
        $response = self::fetchUrl($url);

        $score = 50;
        $label = 'NETRAL';
        $summary = 'Sentimen berita belum tersedia, sehingga sistem menggunakan sinyal teknikal dan pergerakan harga sebagai referensi.';

        if ($response !== null) {
            $json = json_decode($response, true);
            $items = $json['feed'] ?? [];
            if (!empty($items)) {
                $sentimentValues = [];
                foreach ($items as $item) {
                    $tickerSentiment = $item['ticker_sentiment'] ?? [];
                    foreach ($tickerSentiment as $entry) {
                        if (($entry['ticker'] ?? '') === strtoupper($symbol)) {
                            $sentimentValues[] = (float)($entry['ticker_sentiment_score'] ?? 0);
                        }
                    }
                }
                if (!empty($sentimentValues)) {
                    $avg = array_sum($sentimentValues) / count($sentimentValues);
                    $score = self::clamp((int)round(50 + ($avg * 50)), 20, 80);
                    $label = $score >= 60 ? 'POSITIF' : ($score <= 40 ? 'NEGATIF' : 'NETRAL');
                    $summary = 'Sentimen berita dari Alpha Vantage menunjukkan bias ' . strtolower($label) . ' berdasarkan headline yang terdeteksi.';
                }
            }
        }

        $changePercent = (float)($quote['changePercent'] ?? 0);
        if ($response === null) {
            if ($changePercent > 2) $score += 5;
            elseif ($changePercent < -2) $score -= 5;
            if (($technical['rsi'] ?? 50) > 60) $score += 3;
            elseif (($technical['rsi'] ?? 50) < 40) $score -= 3;
            $label = $score >= 60 ? 'POSITIF' : ($score <= 40 ? 'NEGATIF' : 'NETRAL');
            $summary = 'Sentimen diperkirakan dari pergerakan harga dan indikator teknikal karena sumber berita tidak tersedia.';
        }

        return [
            'score' => self::clamp($score, 20, 80),
            'label' => $label,
            'summary' => $summary
        ];
    }

    private static function getDeepAnalysis($symbol, $quote, $technical, $fundamental, $sentiment, $signal) {
        $provider = getenv('AI_PROVIDER') ?: 'heuristic';
        $apiKey = getenv('AI_API_KEY') ?: '';
        $model = getenv('AI_MODEL') ?: ($provider === 'gemini' ? 'gemini-2.0-flash' : 'gpt-4o-mini');

        if (($provider === 'gemini' || $provider === 'openai' || $provider === 'openrouter') && $apiKey !== '') {
            $prompt = 'Buat analisis singkat namun membantu untuk saham ' . strtoupper($symbol) . '. Berikan hasil dalam 4 bagian: ringkasan, poin_kekuatan, poin_risiko, next_step. Data: harga ' . ($quote['price'] ?? 0) . ', perubahan ' . ($quote['changePercent'] ?? 0) . '%, RSI ' . ($technical['rsi'] ?? 50) . ', MACD ' . ($technical['macd']['trend'] ?? 'NEUTRAL') . ', fundamental ' . strtolower($fundamental['label']) . ', sentimen ' . strtolower($sentiment['label']) . ', sinyal ' . $signal . '. Jawab singkat dalam bahasa Indonesia.';

            if ($provider === 'gemini') {
                $response = self::callGemini($prompt, $apiKey, $model);
                if ($response !== null) {
                    return ['summary' => $response, 'strengths' => [], 'risks' => [], 'next_step' => 'Pantau breakout dan area support.', 'provider' => $provider];
                }
            }

            if ($provider === 'openai' || $provider === 'openrouter') {
                $response = self::callOpenAiCompatible($prompt, $apiKey, $model, $provider);
                if ($response !== null) {
                    return ['summary' => $response, 'strengths' => [], 'risks' => [], 'next_step' => 'Pantau breakout dan area support.', 'provider' => $provider];
                }
            }
        }

        return self::getFallbackDeepAnalysis($symbol, $quote, $technical, $fundamental, $sentiment, $signal);
    }

    private static function getFallbackDeepAnalysis($symbol, $quote, $technical, $fundamental, $sentiment, $signal) {
        $changePercent = (float)($quote['changePercent'] ?? 0);
        $rsi = (float)($technical['rsi'] ?? 50);
        $strengths = [];
        $risks = [];

        if (strpos($signal, 'BUY') !== false) {
            $strengths[] = 'Momentum teknikal cenderung menguat dan sinyal mengarah ke beli.';
        } elseif (strpos($signal, 'SELL') !== false) {
            $risks[] = 'Momentum teknikal terlihat lemah dan potensi koreksi masih ada.';
        } else {
            $strengths[] = 'Pasar sedang dalam fase konsolidasi sehingga peluang entry harus lebih selektif.';
        }

        if ($fundamental['label'] === 'POSITIF') {
            $strengths[] = 'Fundamental menunjukkan profil yang cukup kuat.';
        } elseif ($fundamental['label'] === 'NEGATIF') {
            $risks[] = 'Fundamental masih belum ideal untuk posisi agresif.';
        }

        if ($sentiment['label'] === 'POSITIF') {
            $strengths[] = 'Sentimen berita dan pergerakan harga sedang mendukung bias positif.';
        } elseif ($sentiment['label'] === 'NEGATIF') {
            $risks[] = 'Sentimen pasar sedang berat dan bisa menekan momentum.';
        }

        if ($changePercent > 2) {
            $strengths[] = 'Pergerakan harga hari ini menguat, sehingga bias jangka pendek lebih positif.';
        } elseif ($changePercent < -2) {
            $risks[] = 'Harga sedang melemah, jadi tunggu konfirmasi sebelum masuk posisi.';
        }

        if ($rsi > 70) {
            $risks[] = 'RSI sudah menegaskan area overbought, sehingga potensi pullback meningkat.';
        } elseif ($rsi < 30) {
            $strengths[] = 'RSI ada di area oversold, memberi peluang rebound.';
        }

        $nextStep = strpos($signal, 'BUY') !== false ? 'Pantau breakout dan volume konfirmasi sebelum entry.' : 'Tunggu validasi harga dan konfirmasi sinyal sebelum mengambil posisi.';

        return [
            'summary' => 'Analisis AI sederhana menunjukkan bahwa ' . strtolower($signal) . ' untuk ' . strtoupper($symbol) . ' didukung oleh kombinasi teknikal, fundamental, dan sentimen pasar yang sedang dipantau.',
            'strengths' => $strengths,
            'risks' => $risks,
            'next_step' => $nextStep,
            'provider' => 'heuristic'
        ];
    }

    private static function callGemini($prompt, $apiKey, $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);
        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => ['temperature' => 0.3]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text) {
                return trim($text);
            }
        }
        return null;
    }

    private static function callOpenAiCompatible($prompt, $apiKey, $model, $provider) {
        $url = $provider === 'openrouter'
            ? 'https://openrouter.ai/api/v1/chat/completions'
            : 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.3
        ];

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($provider === 'openrouter') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
            $headers[] = 'HTTP-Referer: http://127.0.0.1:8000';
            $headers[] = 'X-Title: Gila Trading';
        } else {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($httpCode === 200 && $response) {
            $json = json_decode($response, true);
            $text = $json['choices'][0]['message']['content'] ?? null;
            if ($text) {
                return trim($text);
            }
        }
        return null;
    }

    private static function buildTechnicalNarrative($quote, $technical, $signal) {
        $changePercent = (float)($quote['changePercent'] ?? 0);
        $rsi = (float)($technical['rsi'] ?? 50);
        $macdTrend = strtoupper((string)($technical['macd']['trend'] ?? 'NEUTRAL'));
        $bollinger = $technical['bollinger'] ?? ['status' => 'NEUTRAL'];
        $stochastic = $technical['stochastic'] ?? ['k' => 50.0];
        $supportResistance = $technical['support_resistance'] ?? ['support' => 0.0, 'resistance' => 0.0];
        $breakout = $technical['breakout'] ?? ['label' => 'No breakout'];
        $pattern = $technical['pattern'] ?? ['label' => 'No pattern'];
        $volumeProfile = $technical['volume_profile'] ?? ['status' => 'NORMAL'];
        $multiTimeframe = $technical['multi_timeframe'] ?? ['bias' => 'NEUTRAL'];
        $ema20 = (float)($technical['ema20'] ?? 0);
        $changeText = $changePercent >= 0 ? 'menguat' : 'melemah';

        $bollingerText = $bollinger['status'] === 'BELOW_LOWER'
            ? 'harga berada di bawah lower band, menandakan potensi rebound'
            : ($bollinger['status'] === 'ABOVE_UPPER'
                ? 'harga berada di atas upper band, menandakan potensi pullback'
                : 'harga berada dalam kisaran Bollinger yang masih wajar');

        $stochasticText = $stochastic['k'] >= 80
            ? 'stochastic overbought'
            : ($stochastic['k'] <= 20 ? 'stochastic oversold' : 'stochastic netral');

        return 'Sinyal AI menggabungkan teknikal, fundamental, sentimen berita, dan bandarmology. Pergerakan harga saat ini ' . $changeText . ' (' . number_format($changePercent, 2) . '%), RSI ' . number_format($rsi, 1) . ', MACD ' . strtolower(str_replace('_', ' ', $macdTrend)) . ', EMA20 ' . number_format($ema20, 2) . ', support/resistance di ' . number_format($supportResistance['support'], 2) . ' / ' . number_format($supportResistance['resistance'], 2) . ', ' . strtolower($breakout['label']) . ', pola ' . strtolower($pattern['label']) . ', volume ' . strtolower($volumeProfile['status']) . ', bandarmology ' . strtolower($technical['bandarmology']['status'] ?? 'neutral') . ' (skor ' . ($technical['bandarmology']['score'] ?? 50) . '), dan multi-timeframe bias ' . strtolower($multiTimeframe['bias']) . '. Rekomendasi akhir: ' . $signal . '.';
    }

    private static function buildReasoning($quote, $technical, $fundamental, $sentiment, $signal) {
        return self::buildTechnicalNarrative($quote, $technical, $signal) . ' Fundamental ' . strtolower($fundamental['label']) . ' dan sentimen berita ' . strtolower($sentiment['label']) . '.';
    }

    private static function mapScoreToSignal($score, $fallbackSignal) {
        if ($score >= 78) return 'STRONG BUY';
        if ($score >= 65) return 'BUY';
        if ($score <= 28) return 'STRONG SELL';
        if ($score <= 42) return 'SELL';
        return $fallbackSignal;
    }

    private static function clamp($value, $min, $max) {
        return max($min, min($max, (int)$value));
    }

    private static function parseNumber($value) {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }

    private static function getFallbackFundamentalProfile($symbol) {
        $symbol = strtoupper(trim($symbol));
        $profiles = [
            'BBCA' => ['per' => 16.8, 'pbv' => 2.1, 'roe' => 12.8, 'growth' => 8.5, 'margin' => 31.2, 'debt' => 0.42, 'summary' => 'Bank besar dengan struktur modal kuat, pendapatan stabil, dan prospek pertumbuhan yang konsisten.', 'score' => 68, 'label' => 'POSITIF'],
            'BBRI' => ['per' => 13.4, 'pbv' => 1.9, 'roe' => 14.6, 'growth' => 7.3, 'margin' => 40.1, 'debt' => 0.61, 'summary' => 'Perbankan dengan kualitas kredit yang cukup baik dan marjin bunga bersih yang tetap sehat.', 'score' => 71, 'label' => 'POSITIF'],
            'BMRI' => ['per' => 14.2, 'pbv' => 2.3, 'roe' => 16.2, 'growth' => 9.1, 'margin' => 38.6, 'debt' => 0.56, 'summary' => 'Bank dengan aset besar dan ekspansi yang terjaga, cocok untuk investor yang mencari kualitas.', 'score' => 75, 'label' => 'POSITIF'],
            'BBNI' => ['per' => 12.7, 'pbv' => 2.0, 'roe' => 15.8, 'growth' => 10.2, 'margin' => 36.4, 'debt' => 0.53, 'summary' => 'Profil fundamental solid dengan pendapatan bunga yang terjaga dan efisiensi operasi yang baik.', 'score' => 73, 'label' => 'POSITIF'],
            'TLKM' => ['per' => 22.1, 'pbv' => 4.8, 'roe' => 21.7, 'growth' => 5.9, 'margin' => 24.8, 'debt' => 0.35, 'summary' => 'Infrastruktur digital yang kuat, namun valuasi cukup premium dibanding sektor rata-rata.', 'score' => 62, 'label' => 'NETRAL'],
            'UNVR' => ['per' => 31.6, 'pbv' => 10.2, 'roe' => 32.4, 'growth' => 6.2, 'margin' => 17.2, 'debt' => 0.12, 'summary' => 'Brand kuat dan margin stabil, tetapi valuasi sudah tinggi sehingga perlu kehati-hatian.', 'score' => 58, 'label' => 'NETRAL'],
            'GOTO' => ['per' => 48.3, 'pbv' => 6.1, 'roe' => 12.6, 'growth' => 18.7, 'margin' => -9.5, 'debt' => 0.84, 'summary' => 'Pertumbuhan cepat tetapi profitabilitas masih rentan; fundamentalnya bergantung pada efisiensi bisnis.', 'score' => 40, 'label' => 'NEGATIF'],
            'ARTO' => ['per' => 19.8, 'pbv' => 3.4, 'roe' => 17.3, 'growth' => 14.6, 'margin' => 12.4, 'debt' => 0.67, 'summary' => 'Ekspansi bisnis cukup menarik, dengan skala usaha yang berkembang dan profitabilitas menanjak.', 'score' => 69, 'label' => 'POSITIF'],
            'BRPT' => ['per' => 22.4, 'pbv' => 2.8, 'roe' => 12.7, 'growth' => 16.5, 'margin' => 5.4, 'debt' => 0.92, 'summary' => 'Potensi pertumbuhan industri yang kuat, walau leverage dan profitabilitas masih perlu dipantau.', 'score' => 60, 'label' => 'NETRAL'],
            'CUAN' => ['per' => 24.9, 'pbv' => 5.6, 'roe' => 22.8, 'growth' => 11.9, 'margin' => 9.1, 'debt' => 0.41, 'summary' => 'Bisnis yang tumbuh dengan baik dan marjin yang membaik, namun valuasi masih perlu diperhatikan.', 'score' => 66, 'label' => 'POSITIF'],
            'ADRO' => ['per' => 11.6, 'pbv' => 1.2, 'roe' => 10.4, 'growth' => 7.1, 'margin' => 18.7, 'debt' => 0.57, 'summary' => 'Sektor komoditas dengan daya tahan bisnis baik dan valuasi yang cukup masuk akal.', 'score' => 64, 'label' => 'NETRAL'],
            'ASII' => ['per' => 17.5, 'pbv' => 2.4, 'roe' => 13.8, 'growth' => 8.4, 'margin' => 8.6, 'debt' => 0.41, 'summary' => 'Konsolidasi bisnis yang stabil dengan fundamental industri otomotif yang masih berkelanjutan.', 'score' => 68, 'label' => 'POSITIF'],
            'IHSG' => ['per' => 18.1, 'pbv' => 1.8, 'roe' => 10.3, 'growth' => 6.7, 'margin' => 16.8, 'debt' => 0.64, 'summary' => 'Pasar domestik menunjukkan ketahanan dengan valuasi yang masih masuk akal untuk tren jangka menengah.', 'score' => 63, 'label' => 'NETRAL'],
        ];

        return $profiles[$symbol] ?? ['per' => 20.1, 'pbv' => 3.2, 'roe' => 15.6, 'growth' => 9.8, 'margin' => 16.3, 'debt' => 0.58, 'summary' => 'Profil fundamental secara umum seimbang dan cukup layak dipantau berdasarkan kondisi pasar saat ini.', 'score' => 60, 'label' => 'NETRAL'];
    }

    private static function fetchUrl($url) {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init();
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_LOW_SPEED_TIME => 5,
            CURLOPT_LOW_SPEED_LIMIT => 10,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json,text/plain,*/*',
                'Accept-Language: en-US,en;q=0.9'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);

        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($curlError !== 0 || $response === false) {
            return null;
        }

        if ($httpCode === 200 && $response) {
            return $response;
        }

        return null;
    }
}
