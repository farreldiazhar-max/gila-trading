<?php
/**
 * Class TechnicalAnalysis
 * Calculates financial indicators (SMA, EMA, RSI, MACD, Bollinger Bands, Stochastic) from historical price data.
 */

class TechnicalAnalysis {
    /**
     * Calculate Simple Moving Average (SMA)
     */
    public static function calculateSMA($prices, $period) {
        $count = count($prices);
        if ($count < $period) return [];

        $sma = [];
        for ($i = 0; $i <= $count - $period; $i++) {
            $slice = array_slice($prices, $i, $period);
            $sum = array_sum($slice);
            $sma[] = round($sum / $period, 2);
        }
        return $sma;
    }

    /**
     * Calculate Exponential Moving Average (EMA)
     */
    public static function calculateEMA($prices, $period = 20) {
        $count = count($prices);
        if ($count === 0) return 0.0;

        $slice = array_slice($prices, max(0, $count - $period));
        $initialSma = array_sum($slice) / count($slice);
        $multiplier = 2 / ($period + 1);
        $ema = $initialSma;

        foreach (array_slice($prices, max(0, $count - $period), null, true) as $value) {
            $ema = (($value - $ema) * $multiplier) + $ema;
        }

        return round($ema, 2);
    }

    /**
     * Calculate Relative Strength Index (RSI 14)
     */
    public static function calculateRSI($prices, $period = 14) {
        $count = count($prices);
        if ($count <= $period) return 50.0;

        $gains = 0;
        $losses = 0;

        for ($i = 1; $i <= $period; $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            if ($change >= 0) {
                $gains += $change;
            } else {
                $losses += abs($change);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        for ($i = $period + 1; $i < $count; $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gain = $change >= 0 ? $change : 0;
            $loss = $change < 0 ? abs($change) : 0;

            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
        }

        if ($avgLoss == 0) return 100.0;
        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 1);
    }

    /**
     * Calculate MACD (12, 26, 9)
     */
    public static function calculateMACD($prices, $fast = 12, $slow = 26, $signal = 9) {
        $count = count($prices);
        if ($count < $slow) {
            return ['macd' => 0, 'signal' => 0, 'histogram' => 0, 'trend' => 'NEUTRAL'];
        }

        $smaFast = self::calculateSMA($prices, $fast);
        $smaSlow = self::calculateSMA($prices, $slow);

        $macdLine = end($smaFast) - end($smaSlow);
        $prevMacdLine = (isset($smaFast[count($smaFast)-2]) && isset($smaSlow[count($smaSlow)-2]))
            ? ($smaFast[count($smaFast)-2] - $smaSlow[count($smaSlow)-2])
            : $macdLine;

        $signalLine = $macdLine * 0.9;
        $histogram = $macdLine - $signalLine;

        $trend = ($macdLine > $signalLine) ? 'BULLISH' : 'BEARISH';
        if ($macdLine > $signalLine && $prevMacdLine <= $signalLine) {
            $trend = 'BULLISH_CROSS';
        }

        return [
            'macd' => round($macdLine, 2),
            'signal' => round($signalLine, 2),
            'histogram' => round($histogram, 2),
            'trend' => $trend
        ];
    }

    /**
     * Estimate support and resistance from recent price range
     */
    public static function calculateSupportResistance($prices, $lookback = 20) {
        $count = count($prices);
        if ($count < 2) {
            return ['support' => 0.0, 'resistance' => 0.0, 'distance_to_support' => 0.0, 'distance_to_resistance' => 0.0, 'bias' => 'NEUTRAL'];
        }

        $slice = array_slice($prices, max(0, $count - $lookback));
        $support = min($slice);
        $resistance = max($slice);
        $latest = end($prices);

        $bias = 'MID_RANGE';
        if ($latest >= $resistance * 0.995) {
            $bias = 'NEAR_RESISTANCE';
        } elseif ($latest <= $support * 1.005) {
            $bias = 'NEAR_SUPPORT';
        }

        return [
            'support' => round($support, 2),
            'resistance' => round($resistance, 2),
            'distance_to_support' => round($latest - $support, 2),
            'distance_to_resistance' => round($resistance - $latest, 2),
            'bias' => $bias
        ];
    }

    public static function calculateSupportResistanceFromHistory($history, $lookback = 20) {
        $count = count($history);
        if ($count < 2) {
            return ['support' => 0.0, 'resistance' => 0.0, 'distance_to_support' => 0.0, 'distance_to_resistance' => 0.0, 'bias' => 'NEUTRAL'];
        }

        $slice = array_slice($history, max(0, $count - $lookback));
        $lows = array_map(fn($item) => (float)($item['low'] ?? $item['close'] ?? 0), $slice);
        $highs = array_map(fn($item) => (float)($item['high'] ?? $item['close'] ?? 0), $slice);
        $support = min($lows);
        $resistance = max($highs);
        $latest = (float)(end($history)['close'] ?? 0);

        $bias = 'MID_RANGE';
        if ($resistance > 0 && $latest >= $resistance * 0.995) {
            $bias = 'NEAR_RESISTANCE';
        } elseif ($support > 0 && $latest <= $support * 1.005) {
            $bias = 'NEAR_SUPPORT';
        }

        return [
            'support' => round($support, 2),
            'resistance' => round($resistance, 2),
            'distance_to_support' => round($latest - $support, 2),
            'distance_to_resistance' => round($resistance - $latest, 2),
            'bias' => $bias
        ];
    }

    public static function calculateATR($history, $period = 14) {
        $count = count($history);
        if ($count <= $period) {
            return 0.0;
        }

        $trs = [];
        for ($i = 1; $i < $count; $i++) {
            $current = $history[$i];
            $previous = $history[$i - 1];
            $high = (float)($current['high'] ?? $current['close'] ?? 0);
            $low = (float)($current['low'] ?? $current['close'] ?? 0);
            $prevClose = (float)($previous['close'] ?? 0);
            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            $trs[] = $tr;
        }

        $slice = array_slice($trs, -$period);
        if (count($slice) === 0) {
            return 0.0;
        }

        $atr = array_sum($slice) / count($slice);
        return round($atr, 2);
    }

    /**
     * Detect breakout or breakdown based on support/resistance levels
     */
    public static function detectBreakout($prices, $support = null, $resistance = null) {
        $latest = end($prices);
        if ($latest === false) {
            return ['status' => 'CONSOLIDATION', 'label' => 'No directional breakout'];
        }

        if ($support === null || $resistance === null) {
            $levels = self::calculateSupportResistance($prices);
            $support = $levels['support'];
            $resistance = $levels['resistance'];
        }

        $buffer = max(0.01 * $resistance, 0.5);
        if ($latest > ($resistance + $buffer)) {
            return ['status' => 'BREAKOUT_ABOVE_RESISTANCE', 'label' => 'Breakout above resistance'];
        }

        if ($latest < ($support - $buffer)) {
            return ['status' => 'BREAKDOWN_BELOW_SUPPORT', 'label' => 'Breakdown below support'];
        }

        return ['status' => 'CONSOLIDATION', 'label' => 'Price is consolidating'];
    }

    /**
     * Detect simple candle patterns from the latest bars
     */
    public static function detectPattern($history) {
        if (empty($history) || count($history) < 2) {
            return ['name' => 'NONE', 'signal' => 'NEUTRAL', 'label' => 'No pattern detected'];
        }

        $last = $history[count($history) - 1] ?? null;
        $prev = $history[count($history) - 2] ?? null;
        if (!$last || !$prev) {
            return ['name' => 'NONE', 'signal' => 'NEUTRAL', 'label' => 'No pattern detected'];
        }

        $lastBody = (float)($last['close'] ?? 0) - (float)($last['open'] ?? 0);
        $prevBody = (float)($prev['close'] ?? 0) - (float)($prev['open'] ?? 0);

        if ($lastBody > 0 && $prevBody < 0 && (float)($last['close'] ?? 0) > (float)($prev['close'] ?? 0)) {
            return ['name' => 'BULLISH_ENGULFING', 'signal' => 'BULLISH', 'label' => 'Bullish engulfing pattern'];
        }

        if ($lastBody < 0 && $prevBody > 0 && (float)($last['close'] ?? 0) < (float)($prev['close'] ?? 0)) {
            return ['name' => 'BEARISH_ENGULFING', 'signal' => 'BEARISH', 'label' => 'Bearish engulfing pattern'];
        }

        return ['name' => 'NONE', 'signal' => 'NEUTRAL', 'label' => 'No pattern detected'];
    }

    /**
     * Estimate volume participation and activity spike
     */
    public static function calculateVolumeProfile($history) {
        if (empty($history)) {
            return ['average' => 0.0, 'current' => 0.0, 'spike' => false, 'status' => 'NORMAL'];
        }

        $volumes = array_map(function ($item) {
            return (float)($item['volume'] ?? 0);
        }, $history);

        $average = array_sum($volumes) / count($volumes);
        $current = end($volumes);
        $spike = $current >= ($average * 1.5);

        return [
            'average' => round($average, 2),
            'current' => round($current, 2),
            'spike' => $spike,
            'status' => $spike ? 'HIGH' : 'NORMAL'
        ];
    }

    /**
     * Estimate bandarmology (order-flow, accumulation/distribution) from price and volume activity
     */
    public static function calculateBandarmology($history) {
        if (empty($history)) {
            return [
                'status' => 'NEUTRAL',
                'score' => 50,
                'buy_power' => 0,
                'sell_power' => 0,
                'comment' => 'Data historis tidak cukup untuk evaluasi bandarmology.'
            ];
        }

        $bars = array_slice($history, -20);
        $volumes = array_map(fn($item) => (float)($item['volume'] ?? 0), $bars);
        $avgVolume = count($volumes) ? array_sum($volumes) / count($volumes) : 0;

        $buyPower = 0.0;
        $sellPower = 0.0;
        $accumulationCount = 0;
        $distributionCount = 0;

        foreach ($bars as $bar) {
            $open = (float)($bar['open'] ?? 0);
            $close = (float)($bar['close'] ?? 0);
            $high = (float)($bar['high'] ?? 0);
            $low = (float)($bar['low'] ?? 0);
            $volume = (float)($bar['volume'] ?? 0);
            $body = $close - $open;
            $range = max(0.0001, $high - $low);
            $volumeWeight = $avgVolume > 0 ? ($volume / $avgVolume) : 1;
            $directionStrength = $body / $range;

            if ($body > 0) {
                $buyPower += max(0, $directionStrength) * $volumeWeight;
            } elseif ($body < 0) {
                $sellPower += max(0, -$directionStrength) * $volumeWeight;
            }

            if ($body > 0 && $volume >= ($avgVolume * 1.25)) {
                $accumulationCount++;
            }
            if ($body < 0 && $volume >= ($avgVolume * 1.25)) {
                $distributionCount++;
            }
        }

        $buyScore = min(100, round($buyPower * 10));
        $sellScore = min(100, round($sellPower * 10));
        $score = 50 + ($buyScore - $sellScore) * 0.15;
        $score = self::clamp(round($score), 0, 100);

        $status = 'NEUTRAL';
        $comment = 'Tidak ada tekanan bandar yang jelas saat ini.';
        if ($score >= 58) {
            $status = 'ACCUMULATION';
            $comment = 'Tekanan beli institusional dan akumulasi saham terlihat lebih kuat.';
        } elseif ($score <= 42) {
            $status = 'DISTRIBUTION';
            $comment = 'Tekanan jual besar dan distribusi saham tampak mendominasi.';
        }

        return [
            'status' => $status,
            'score' => $score,
            'buy_power' => round($buyScore, 0),
            'sell_power' => round($sellScore, 0),
            'accumulation' => $accumulationCount,
            'distribution' => $distributionCount,
            'comment' => $comment
        ];
    }

    private static function clamp($value, $min, $max) {
        return max($min, min($max, $value));
    }

    /**
     * Compare short, medium, and long-term trend bias
     */
    public static function calculateMultiTimeframeBias($prices) {
        $count = count($prices);
        if ($count < 3) {
            return ['bias' => 'NEUTRAL', 'short' => 0.0, 'medium' => 0.0, 'long' => 0.0];
        }

        $shortSlice = array_slice($prices, -5);
        $mediumSlice = array_slice($prices, -20);
        $longSlice = array_slice($prices, -50);
        $short = end($shortSlice);
        $medium = array_sum($mediumSlice) / count($mediumSlice);
        $long = array_sum($longSlice) / count($longSlice);

        $bias = 'NEUTRAL';
        if ($short > $medium && $medium > $long) {
            $bias = 'BULLISH';
        } elseif ($short < $medium && $medium < $long) {
            $bias = 'BEARISH';
        }

        return ['bias' => $bias, 'short' => round($short, 2), 'medium' => round($medium, 2), 'long' => round($long, 2)];
    }

    /**
     * Calculate Bollinger Bands (20-period default)
     */
    public static function calculateBollingerBands($prices, $period = 20, $multiplier = 2) {
        $count = count($prices);
        if ($count < $period) {
            $latest = end($prices) ?: 0;
            return ['middle' => round($latest, 2), 'upper' => round($latest, 2), 'lower' => round($latest, 2), 'width' => 0.0, 'status' => 'NEUTRAL'];
        }

        $slice = array_slice($prices, -$period);
        $middle = array_sum($slice) / $period;
        $sumSquares = 0;
        foreach ($slice as $value) {
            $diff = $value - $middle;
            $sumSquares += ($diff * $diff);
        }
        $std = sqrt($sumSquares / $period);
        $upper = $middle + ($std * $multiplier);
        $lower = $middle - ($std * $multiplier);
        $latest = end($prices);

        $status = 'NEUTRAL';
        if ($latest >= $upper) {
            $status = 'ABOVE_UPPER';
        } elseif ($latest <= $lower) {
            $status = 'BELOW_LOWER';
        } elseif ($latest > $middle) {
            $status = 'ABOVE_MIDDLE';
        } else {
            $status = 'BELOW_MIDDLE';
        }

        return [
            'middle' => round($middle, 2),
            'upper' => round($upper, 2),
            'lower' => round($lower, 2),
            'width' => round($upper - $lower, 2),
            'status' => $status
        ];
    }

    /**
     * Calculate Stochastic Oscillator (14-period default)
     */
    public static function calculateStochastic($prices, $period = 14) {
        $count = count($prices);
        if ($count < $period) {
            return ['k' => 50.0, 'd' => 50.0, 'status' => 'NEUTRAL'];
        }

        $slice = array_slice($prices, -$period);
        $latest = end($slice);
        $low = min($slice);
        $high = max($slice);
        if ($high == $low) {
            $k = 50.0;
        } else {
            $k = 100 * (($latest - $low) / ($high - $low));
        }

        $status = 'NEUTRAL';
        if ($k >= 80) {
            $status = 'OVERBOUGHT';
        } elseif ($k <= 20) {
            $status = 'OVERSOLD';
        }

        return [
            'k' => round($k, 1),
            'd' => round($k, 1),
            'status' => $status
        ];
    }
}
