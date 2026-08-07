<?php
/**
 * Modular connector layer for broker/securities integration.
 * Supports provider-agnostic configuration and a fallback simulation mode.
 */
class BrokerConnector {
    public static function getProviders() {
        return [
            'ajaib' => 'Ajaib',
            'stockbit' => 'Stockbit',
            'mirae' => 'Mirae Asset',
            'manual' => 'Manual / CSV'
        ];
    }

    public static function getConnectionStatus($provider) {
        $provider = strtolower(trim((string)$provider));
        $envKey = strtoupper($provider) . '_API_KEY';
        $apiKey = getenv($envKey) ?: '';
        return [
            'provider' => $provider,
            'connected' => $apiKey !== '',
            'mode' => $apiKey !== '' ? 'live' : 'simulation',
            'message' => $apiKey !== ''
                ? 'Koneksi siap dengan API key provider.'
                : 'Mode simulasi aktif. Anda dapat menghubungkan akun nanti.'
        ];
    }

    public static function buildDecisionContext($portfolioData = []) {
        $providers = self::getProviders();
        $status = [];
        foreach (array_keys($providers) as $provider) {
            $status[$provider] = self::getConnectionStatus($provider);
        }

        return [
            'providers' => $providers,
            'status' => $status,
            'portfolio' => $portfolioData,
            'assistant_note' => 'Website dapat berfungsi sebagai asisten keputusan trading dengan data pasar realtime ditambah koneksi broker yang aman dan modular.'
        ];
    }

    public static function getPortfolioSnapshot($provider = 'manual') {
        $provider = strtolower(trim((string)$provider));
        if ($provider === 'manual') {
            return [
                'source' => 'manual',
                'summary' => 'Data portofolio dikontrol secara manual atau impor dari CSV.',
                'holdings' => [
                    ['symbol' => 'BBCA', 'qty' => 5000, 'avg_price' => 9150, 'last_price' => 9850],
                    ['symbol' => 'BMRI', 'qty' => 4000, 'avg_price' => 6800, 'last_price' => 7250],
                ]
            ];
        }

        return [
            'source' => $provider,
            'summary' => 'Koneksi broker siap dihubungkan melalui provider ' . ucfirst($provider) . '.',
            'holdings' => []
        ];
    }
}
