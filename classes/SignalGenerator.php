<?php
/**
 * Class SignalGenerator
 * Generates trading signals, confidence score, entry/exit targets, and risk-reward ratio.
 */

require_once __DIR__ . '/TechnicalAnalysis.php';

class SignalGenerator {

    public static function generateSignal($stockData) {
        $price = $stockData['price'] ?? 0;
        $history = $stockData['history'] ?? [];

        if (empty($history) || $price <= 0) {
            return [
                'signal' => 'HOLD',
                'confidence' => 50,
                'entry_min' => $price,
                'entry_max' => $price,
                'target_1' => round($price * 1.03, 2),
                'target_2' => round($price * 1.06, 2),
                'stop_loss' => round($price * 0.97, 2),
                'risk_reward' => '1 : 2.0',
                'reasoning' => 'Data tidak cukup untuk kalkulasi teknikal lengkap.'
            ];
        }

        // Extract close prices
        $closePrices = array_column($history, 'close');
        
        $rsi = TechnicalAnalysis::calculateRSI($closePrices);
        $macd = TechnicalAnalysis::calculateMACD($closePrices);
        $ema20 = TechnicalAnalysis::calculateEMA($closePrices, 20);
        $bollinger = TechnicalAnalysis::calculateBollingerBands($closePrices, 20);
        $stochastic = TechnicalAnalysis::calculateStochastic($closePrices, 14);
        $supportResistance = TechnicalAnalysis::calculateSupportResistanceFromHistory($history);
        $breakout = TechnicalAnalysis::detectBreakout($closePrices, $supportResistance['support'], $supportResistance['resistance']);
        $pattern = TechnicalAnalysis::detectPattern($history);
        $volumeProfile = TechnicalAnalysis::calculateVolumeProfile($history);
        $bandarmology = TechnicalAnalysis::calculateBandarmology($history);
        $multiTimeframe = TechnicalAnalysis::calculateMultiTimeframeBias($closePrices);
        $sma50List = TechnicalAnalysis::calculateSMA($closePrices, min(50, count($closePrices)));
        $sma50 = !empty($sma50List) ? end($sma50List) : $price;
        $atr = TechnicalAnalysis::calculateATR($history);

        // Bullish / Bearish scoring
        $score = 50; // Neutral baseline

        // RSI scoring
        if ($rsi < 30) $score += 25;
        elseif ($rsi < 45) $score += 15;
        elseif ($rsi > 70) $score -= 25;
        elseif ($rsi > 60) $score -= 10;

        // MACD scoring
        if ($macd['trend'] === 'BULLISH_CROSS') $score += 20;
        elseif ($macd['trend'] === 'BULLISH') $score += 10;
        elseif ($macd['trend'] === 'BEARISH') $score -= 15;

        // EMA / Price confirmation
        if ($price > $ema20) $score += 8;
        else $score -= 8;

        // Bollinger Bands scoring
        if ($bollinger['status'] === 'BELOW_LOWER') $score += 10;
        elseif ($bollinger['status'] === 'ABOVE_UPPER') $score -= 10;

        // Stochastic scoring
        if ($stochastic['status'] === 'OVERSOLD') $score += 8;
        elseif ($stochastic['status'] === 'OVERBOUGHT') $score -= 8;

        // Breakout and support/resistance scoring
        if ($breakout['status'] === 'BREAKOUT_ABOVE_RESISTANCE') $score += 12;
        elseif ($breakout['status'] === 'BREAKDOWN_BELOW_SUPPORT') $score -= 12;

        // Pattern scoring — give strong weight to detected chart patterns
        if ($pattern['signal'] === 'BULLISH') $score += 25;
        elseif ($pattern['signal'] === 'BEARISH') $score -= 25;

        // Volume confirmation
        if ($volumeProfile['spike']) $score += 5;

        // Multi-timeframe bias
        if ($multiTimeframe['bias'] === 'BULLISH') $score += 8;
        elseif ($multiTimeframe['bias'] === 'BEARISH') $score -= 8;

        // Bandarmology / order-flow scoring
        if ($bandarmology['status'] === 'ACCUMULATION') $score += 10;
        elseif ($bandarmology['status'] === 'DISTRIBUTION') $score -= 10;
        else {
            $score += ($bandarmology['score'] - 50) * 0.08;
        }

        // Price vs SMA50 scoring
        if ($price > $sma50) $score += 10;
        else $score -= 10;

        // Signal categorization
        if ($score >= 75) {
            $signal = 'STRONG BUY';
            $confidence = min(95, $score);
        } elseif ($score >= 60) {
            $signal = 'BUY';
            $confidence = min(85, $score);
        } elseif ($score <= 30) {
            $signal = 'STRONG SELL';
            $confidence = min(95, 100 - $score);
        } elseif ($score <= 42) {
            $signal = 'SELL';
            $confidence = min(80, 100 - $score);
        } else {
            $signal = 'HOLD';
            $confidence = 60;
        }

        // Calculate ATR-based entry, targets, and stop loss for better trader alignment
        $volatility = max(0.01 * $price, $atr ?: max(1, $price * 0.01));
        $entryBuffer = round($volatility * 0.2, 2);
        $entryMin = round(max($supportResistance['support'], $price - $entryBuffer), 2);
        $entryMax = round(min($supportResistance['resistance'], $price + $entryBuffer), 2);

        if ($signal === 'STRONG BUY' || $signal === 'BUY') {
            $tp1 = round($price + max($volatility * 1.0, $price * 0.015), 2);
            $tp2 = round($price + max($volatility * 1.8, $price * 0.03), 2);
            $sl = round(max($supportResistance['support'], $price - max($volatility * 1.0, $price * 0.015)), 2);
        } elseif ($signal === 'SELL' || $signal === 'STRONG SELL') {
            $tp1 = round($price - max($volatility * 1.0, $price * 0.015), 2);
            $tp2 = round($price - max($volatility * 1.8, $price * 0.03), 2);
            $sl = round(min($supportResistance['resistance'], $price + max($volatility * 1.0, $price * 0.015)), 2);
        } else {
            $tp1 = round($price + max($volatility * 0.5, $price * 0.01), 2);
            $tp2 = round($price + max($volatility * 1.0, $price * 0.02), 2);
            $sl = round($price - max($volatility * 0.5, $price * 0.01), 2);
        }

        $risk = max(0.01, abs($price - $sl));
        $reward = max(0.01, abs($tp1 - $price));
        $rrRatio = '1 : ' . number_format($reward / $risk, 1);

        $riskDistance = abs($price - $sl);
        $rewardDistance1 = abs($tp1 - $price);
        $riskRewardText = $riskDistance > 0 ? number_format($rewardDistance1 / $riskDistance, 1) : 'N/A';
        $reason = "RSI berada pada level {$rsi} (" . ($rsi < 30 ? "Oversold" : ($rsi > 70 ? "Overbought" : "Neutral")) . "), MACD menunjukkan " . str_replace('_', ' ', $macd['trend']) . ", support/resistance saat ini berada di {$supportResistance['support']} / {$supportResistance['resistance']}, breakout " . strtolower($breakout['label']) . ", pola " . strtolower($pattern['label']) . ", volume " . strtolower($volumeProfile['status']) . ", bandarmology " . strtolower($bandarmology['status']) . " (skor " . $bandarmology['score'] . "), dan multi-timeframe bias " . strtolower($multiTimeframe['bias']) . ". Entry optimal berada di kisaran {$entryMin} - {$entryMax}, target 1 di {$tp1}, target 2 di {$tp2}, dan stop loss di {$sl} untuk risk/reward sekitar 1:{$riskRewardText}.";

        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'rsi' => $rsi,
            'macd' => $macd,
            'ema20' => $ema20,
            'bollinger' => $bollinger,
            'stochastic' => $stochastic,
            'support_resistance' => $supportResistance,
            'breakout' => $breakout,
            'pattern' => $pattern,
            'volume_profile' => $volumeProfile,
            'bandarmology' => $bandarmology,
            'multi_timeframe' => $multiTimeframe,
            'entry_min' => $entryMin,
            'entry_max' => $entryMax,
            'target_1' => $tp1,
            'target_2' => $tp2,
            'stop_loss' => $sl,
            'risk_reward' => $rrRatio,
            'reasoning' => $reason
        ];
    }
}
