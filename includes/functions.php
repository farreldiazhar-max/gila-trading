<?php
// Helper Functions for Gila Trading

/**
 * Format currency to IDR / Rupiah
 */
function formatRupiah($number, $withPrefix = true) {
    if ($number === null || $number === '') return '-';
    $formatted = number_format($number, 0, ',', '.');
    return $withPrefix ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format percentage change with color sign
 */
function formatPercent($number, $withSign = true) {
    if ($number === null || $number === '') return '-';
    $prefix = ($number > 0 && $withSign) ? '+' : '';
    return $prefix . number_format($number, 2, '.', '') . '%';
}

/**
 * Get CSS class based on percentage value (bullish / bearish)
 */
function getChangeColorClass($number) {
    if ($number > 0) return 'text-bullish';
    if ($number < 0) return 'text-bearish';
    return 'text-muted';
}

/**
 * Get signal badge HTML
 */
function getSignalBadgeHTML($signal) {
    $signal = strtoupper(trim($signal));
    switch ($signal) {
        case 'STRONG BUY':
        case 'BUY':
            return '<span class="badge badge-buy">' . htmlspecialchars($signal) . '</span>';
        case 'STRONG SELL':
        case 'SELL':
            return '<span class="badge badge-sell">' . htmlspecialchars($signal) . '</span>';
        case 'HOLD':
        default:
            return '<span class="badge badge-hold">' . htmlspecialchars($signal) . '</span>';
    }
}
