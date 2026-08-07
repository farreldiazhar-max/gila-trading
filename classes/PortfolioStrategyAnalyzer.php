<?php
/**
 * Analyze imported CSV transactions and provide AI-style strategy feedback.
 */
class PortfolioStrategyAnalyzer {
    public static function analyzeManualPortfolio($entries) {
        require_once __DIR__ . '/AiRecommendationService.php';
        require_once __DIR__ . '/StockData.php';

        $normalizedEntries = [];
        foreach ($entries as $entry) {
            $symbol = strtoupper(trim((string)($entry['symbol'] ?? '')));
            $qty = (float)($entry['qty'] ?? 0);
            $avgPrice = (float)($entry['avg_price'] ?? 0);
            $currentPrice = (float)($entry['current_price'] ?? 0);

            if ($symbol === '' || $qty <= 0 || $avgPrice <= 0) {
                continue;
            }

            $normalizedEntries[] = [
                'symbol' => $symbol,
                'qty' => $qty,
                'avg_price' => $avgPrice,
                'current_price' => $currentPrice > 0 ? $currentPrice : null,
            ];
        }

        if (empty($normalizedEntries)) {
            return [
                'status' => 'empty',
                'message' => 'Belum ada data portofolio. Isi form manual untuk memulai evaluasi AI.',
                'summary' => [
                    'total_invested' => 0,
                    'current_value' => 0,
                    'unrealized_pnl' => 0,
                    'pnl_pct' => 0,
                    'position_count' => 0,
                ],
                'holdings' => [],
                'ai_summary' => 'Belum ada posisi untuk dianalisis.'
            ];
        }

        $holdings = [];
        $totalInvested = 0;
        $currentValue = 0;
        $positionCount = 0;

        foreach ($normalizedEntries as $entry) {
            $symbol = $entry['symbol'];
            $qty = $entry['qty'];
            $avgPrice = $entry['avg_price'];
            $currentPrice = $entry['current_price'];

            $quote = StockData::getQuote($symbol);
            $livePrice = $currentPrice > 0 ? $currentPrice : (float)($quote['price'] ?? 0);
            $marketValue = $qty * $livePrice;
            $investedValue = $qty * $avgPrice;
            $pnlValue = $marketValue - $investedValue;
            $pnlPct = $avgPrice > 0 ? (($livePrice / $avgPrice) - 1) * 100 : 0;
            $ai = AiRecommendationService::buildRecommendation($symbol, $quote);

            $holdings[] = [
                'symbol' => $symbol,
                'qty' => $qty,
                'avg_price' => $avgPrice,
                'current_price' => $livePrice,
                'market_value' => round($marketValue, 0),
                'invested_value' => round($investedValue, 0),
                'pnl_value' => round($pnlValue, 0),
                'pnl_pct' => round($pnlPct, 2),
                'ai_signal' => $ai['signal'] ?? 'HOLD',
                'ai_confidence' => $ai['confidence'] ?? 0,
                'ai_summary' => $ai['deep_analysis'] ?? $ai['reasoning'] ?? 'Analisis AI belum tersedia.',
            ];

            $totalInvested += $investedValue;
            $currentValue += $marketValue;
            $positionCount++;
        }

        $unrealizedPnl = $currentValue - $totalInvested;
        $pnlPct = $totalInvested > 0 ? (($currentValue / $totalInvested) - 1) * 100 : 0;

        $buyCount = count(array_filter($holdings, function ($item) {
            return strpos(strtoupper((string)$item['ai_signal']), 'BUY') !== false;
        }));
        $sellCount = count(array_filter($holdings, function ($item) {
            return strpos(strtoupper((string)$item['ai_signal']), 'SELL') !== false;
        }));

        $aiSummary = 'Portofolio Anda terdiri dari ' . $positionCount . ' saham dengan total modal ' . number_format($totalInvested, 0, ',', '.') . ' dan nilai saat ini ' . number_format($currentValue, 0, ',', '.') . '. AI melihat ' . $buyCount . ' saham potensial bullish, ' . $sellCount . ' saham cenderung lemah, dan sisanya berada di fase netral.';

        return [
            'status' => 'ok',
            'message' => 'Ringkasan AI portofolio berhasil disusun.',
            'summary' => [
                'total_invested' => round($totalInvested, 0),
                'current_value' => round($currentValue, 0),
                'unrealized_pnl' => round($unrealizedPnl, 0),
                'pnl_pct' => round($pnlPct, 2),
                'position_count' => $positionCount,
            ],
            'holdings' => $holdings,
            'ai_summary' => $aiSummary,
        ];
    }

    public static function analyzeCsv($filePath) {
        require_once __DIR__ . '/StockData.php';

        $rows = self::readCsvRows($filePath);
        if (empty($rows)) {
            return [
                'rows_imported' => 0,
                'status' => 'empty',
                'message' => 'File CSV kosong atau format tidak terbaca.',
                'score' => 0,
                'grade' => 'N/A',
                'strengths' => [],
                'risks' => [],
                'recommendation' => 'Impor data transaksi agar AI dapat menilai strategi Anda.'
            ];
        }

        $transactions = self::normalizeTransactions($rows);
        if (empty($transactions)) {
            return [
                'rows_imported' => count($rows),
                'status' => 'invalid',
                'message' => 'Format CSV tidak sesuai. Harap gunakan kolom simbol, sisi, qty, harga, dan tanggal.',
                'score' => 0,
                'grade' => 'N/A',
                'strengths' => [],
                'risks' => [],
                'recommendation' => 'Pastikan header CSV mengandung simbol, side, qty, price, dan date.'
            ];
        }

        $symbols = array_values(array_unique(array_filter(array_map(function ($t) {
            return strtoupper(trim((string)($t['symbol'] ?? '')));
        }, $transactions))));

        $quotes = [];
        foreach ($symbols as $symbol) {
            $quotes[$symbol] = StockData::getQuote($symbol);
        }

        $stats = self::computeStats($transactions, $quotes);
        $score = self::scoreStrategy($stats);
        $grade = self::gradeScore($score);
        $strengths = self::buildStrengths($stats, $score);
        $risks = self::buildRisks($stats, $score);
        $recommendation = self::buildRecommendation($stats, $score);

        return [
            'rows_imported' => count($transactions),
            'status' => 'ok',
            'message' => 'Analisis strategi berhasil dibuat dari file CSV.',
            'score' => $score,
            'grade' => $grade,
            'strengths' => $strengths,
            'risks' => $risks,
            'recommendation' => $recommendation,
            'metrics' => $stats,
            'symbols' => $symbols,
        ];
    }

    private static function readCsvRows($filePath) {
        if (!is_file($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || count(array_filter($row, fn($value) => trim((string)$value) !== '')) === 0) {
                continue;
            }
            if ($header === null) {
                $header = array_map(function ($value) {
                    return strtolower(trim((string)$value));
                }, $row);
                continue;
            }
            $rows[] = array_combine($header, $row);
        }
        fclose($handle);
        return $rows;
    }

    private static function normalizeTransactions($rows) {
        $transactions = [];
        foreach ($rows as $row) {
            $symbol = self::findValue($row, ['symbol', 'ticker', 'code', 'stock', 'security']);
            $side = self::findValue($row, ['side', 'action', 'type', 'transaction_type', 'buy_sell']);
            $qty = self::findValue($row, ['qty', 'quantity', 'shares', 'lots', 'volume']);
            $price = self::findValue($row, ['price', 'avg_price', 'cost', 'price_per_lot']);
            $date = self::findValue($row, ['date', 'datetime', 'trade_date', 'timestamp']);

            if ($symbol === null || $qty === null || $price === null) {
                continue;
            }

            $normalizedSide = self::normalizeSide($side);
            if ($normalizedSide === null) {
                continue;
            }

            $transactions[] = [
                'symbol' => strtoupper(trim((string)$symbol)),
                'side' => $normalizedSide,
                'qty' => (float)$qty,
                'price' => (float)$price,
                'date' => $date ?: null,
            ];
        }
        return $transactions;
    }

    private static function findValue($row, $keys) {
        foreach ($keys as $key) {
            if (isset($row[$key])) {
                return trim((string)$row[$key]);
            }
        }
        return null;
    }

    private static function normalizeSide($side) {
        $value = strtolower(trim((string)$side));
        if (in_array($value, ['b', 'buy', 'long', 'bid', 'buying'], true)) {
            return 'BUY';
        }
        if (in_array($value, ['s', 'sell', 'short', 'ask', 'selling'], true)) {
            return 'SELL';
        }
        return null;
    }

    private static function computeStats($transactions, $quotes) {
        $positions = [];
        $realizedPnL = 0;
        $winningTrades = 0;
        $totalTrades = 0;
        $buyValue = 0;
        $sellValue = 0;
        $buyQty = 0;
        $sellQty = 0;

        foreach ($transactions as $tx) {
            $symbol = $tx['symbol'];
            $qty = (float)$tx['qty'];
            $price = (float)$tx['price'];
            $side = $tx['side'];
            $totalTrades++;

            if (!isset($positions[$symbol])) {
                $positions[$symbol] = ['qty' => 0, 'cost' => 0];
            }

            if ($side === 'BUY') {
                $positions[$symbol]['qty'] += $qty;
                $positions[$symbol]['cost'] += $price * $qty;
                $buyValue += $price * $qty;
                $buyQty += $qty;
            } else {
                $positions[$symbol]['qty'] -= $qty;
                $positions[$symbol]['cost'] = max(0, $positions[$symbol]['cost'] - ($price * $qty));
                $sellValue += $price * $qty;
                $sellQty += $qty;

                $avgCost = $price > 0 ? ($price * $qty) / max($qty, 1) : 0;
                $realizedPnL += ($price - $avgCost) * $qty;
                $winningTrades += ($price > $avgCost) ? 1 : 0;
            }
        }

        $totalCost = 0;
        $currentValue = 0;
        $netQty = 0;
        foreach ($positions as $symbol => $position) {
            $quote = $quotes[$symbol]['price'] ?? 0;
            $currentValue += max(0, $position['qty']) * $quote;
            $totalCost += max(0, $position['cost']);
            $netQty += $position['qty'];
        }

        $winRate = $totalTrades > 0 ? round(($winningTrades / max(1, $totalTrades)) * 100, 1) : 0;
        $concentration = $totalTrades > 0 ? min(100, round((($buyValue > 0 ? $buyValue : 1) / max(1, $buyValue)) * 100, 1)) : 0;

        return [
            'trade_count' => $totalTrades,
            'win_rate' => $winRate,
            'buy_value' => round($buyValue, 0),
            'sell_value' => round($sellValue, 0),
            'realized_pnl' => round($realizedPnL, 0),
            'current_value' => round($currentValue, 0),
            'current_cost' => round($totalCost, 0),
            'unrealized_pnl' => round($currentValue - $totalCost, 0),
            'net_qty' => round($netQty, 0),
            'concentration' => $concentration,
        ];
    }

    private static function scoreStrategy($stats) {
        $score = 55;
        if ($stats['win_rate'] >= 60) $score += 15;
        elseif ($stats['win_rate'] >= 45) $score += 8;
        else $score -= 10;

        if ($stats['realized_pnl'] > 0) $score += 8;
        elseif ($stats['realized_pnl'] < 0) $score -= 12;

        if ($stats['unrealized_pnl'] > 0) $score += 8;
        elseif ($stats['unrealized_pnl'] < 0) $score -= 10;

        if ($stats['net_qty'] <= 0) $score -= 5;

        if ($stats['trade_count'] >= 10) $score -= 4;
        if ($stats['trade_count'] <= 3) $score += 4;

        return max(0, min(100, round($score, 0)));
    }

    private static function gradeScore($score) {
        if ($score >= 80) return 'A';
        if ($score >= 65) return 'B';
        if ($score >= 50) return 'C';
        return 'D';
    }

    private static function buildStrengths($stats, $score) {
        $strengths = [];
        if ($stats['win_rate'] >= 50) $strengths[] = 'Rasio win rate cukup baik untuk ukuran strategi agresif.';
        if ($stats['realized_pnl'] > 0) $strengths[] = 'Secara keseluruhan transaksi yang dicatat menghasilkan PnL positif.';
        if ($stats['trade_count'] >= 5) $strengths[] = 'Ada cukup banyak data historis untuk melakukan evaluasi yang lebih realistis.';
        return $strengths;
    }

    private static function buildRisks($stats, $score) {
        $risks = [];
        if ($stats['win_rate'] < 50) $risks[] = 'Win rate masih rendah, sehingga strategi perlu lebih selektif.';
        if ($stats['unrealized_pnl'] < 0) $risks[] = 'Posisi saat ini sedang merugi dan perlu penyesuaian risk management.';
        if ($stats['trade_count'] > 10) $risks[] = 'Frekuensi trading tinggi berpotensi menambah biaya dan overtrading.';
        return $risks;
    }

    private static function buildRecommendation($stats, $score) {
        if ($score >= 80) {
            return 'Strategi Anda cukup solid. Pertahankan disiplin entry, gunakan trailing stop, dan evaluasi ulang setiap 2 minggu.';
        }
        if ($score >= 65) {
            return 'Strategi Anda sudah cukup baik. Fokus pada manajemen risiko, diversifikasi, dan tunggu konfirmasi sebelum entry.';
        }
        if ($stats['win_rate'] < 50) {
            return 'AI merekomendasikan mengurangi frekuensi trading, memperketat kriteria entry, dan memilih saham dengan kualitas setup yang lebih kuat.';
        }
        return 'Gunakan pendekatan yang lebih konservatif dengan target profit yang jelas, stop loss disiplin, dan menghindari overtrading.';
    }
}
