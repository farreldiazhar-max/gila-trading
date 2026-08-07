<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';
require_once __DIR__ . '/../classes/AiRecommendationService.php';

// Fetch IHSG real-time quote
$ihsg = StockData::getQuote('^JKSE');

// Fetch real-time quotes for Watchlist & Top Gainers/Losers
$watchedSymbols = ['BBCA', 'TLKM', 'GOTO', 'ASII', 'BBNI', 'BMRI', 'ADRO', 'ARTO', 'CUAN', 'BRPT', 'UNVR'];
$quotes = [];
foreach ($watchedSymbols as $sym) {
    $quotes[$sym] = StockData::getQuote($sym);
}

// Separate gainers and losers
$sortedQuotes = $quotes;
usort($sortedQuotes, function($a, $b) {
    return $b['changePercent'] <=> $a['changePercent'];
});

$topGainers = array_slice($sortedQuotes, 0, 4);
$topLosers = array_reverse(array_slice($sortedQuotes, -4));

$dashboardAiSummary = [];
foreach (['BBCA', 'TLKM', 'BBNI', 'BMRI'] as $symbol) {
    $quote = $quotes[$symbol] ?? StockData::getQuote($symbol);
    $signal = AiRecommendationService::buildRecommendation($symbol, $quote);
    $dashboardAiSummary[] = [
        'symbol' => $symbol,
        'signal' => $signal['signal'],
        'confidence' => $signal['confidence'],
        'reasoning' => $signal['reasoning'],
    ];
}

function buildTradingStyleRecommendations($symbols, $quotes) {
    $candidates = [];
    foreach ($symbols as $symbol) {
        $quote = $quotes[$symbol] ?? StockData::getQuote($symbol);
        $signal = AiRecommendationService::buildRecommendation($symbol, $quote);
        $signalText = strtoupper((string)($signal['signal'] ?? 'HOLD'));
        $changePercent = (float)($quote['changePercent'] ?? 0);
        $rsi = (float)($signal['rsi'] ?? 50);
        $isBullish = strpos($signalText, 'BUY') !== false;
        $isBearish = strpos($signalText, 'SELL') !== false;

        $isBsjp = ($isBullish && ($changePercent >= 0 || $rsi >= 55)) || ($signalText === 'BUY' && $changePercent >= 0);
        $isBpjs = ($isBearish || ($changePercent < 0 && $rsi <= 45)) || ($signalText === 'HOLD' && $changePercent < 0);

        $candidates[] = [
            'symbol' => $symbol,
            'signal' => $signalText,
            'confidence' => (int)($signal['confidence'] ?? 60),
            'reasoning' => $signal['reasoning'] ?? 'AI belum menyediakan narasi.',
            'isBsjp' => $isBsjp,
            'isBpjs' => $isBpjs,
            'changePercent' => $changePercent,
            'rsi' => $rsi,
        ];
    }

    usort($candidates, function ($a, $b) {
        return $b['confidence'] <=> $a['confidence'];
    });

    $results = ['BSJP' => [], 'BPJS' => []];
    foreach ($candidates as $candidate) {
        if ($candidate['isBsjp'] && count($results['BSJP']) < 3) {
            $results['BSJP'][] = $candidate;
        }
        if ($candidate['isBpjs'] && count($results['BPJS']) < 3) {
            $results['BPJS'][] = $candidate;
        }
    }

    if (empty($results['BSJP'])) {
        $results['BSJP'] = array_slice($candidates, 0, 3);
    }

    if (empty($results['BPJS'])) {
        $results['BPJS'] = array_slice(array_reverse($candidates), 0, 3);
    }

    return $results;
}

$tradingClassifications = buildTradingStyleRecommendations($watchedSymbols, $quotes);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col w-full gap-4">
  <!-- Hero / Market Overview Section -->
  <div class="w-full flex flex-col md:flex-row gap-4 items-stretch">
    <!-- Main Market Summary Widget (IHSG) -->
    <div class="flex-1 bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col justify-between relative overflow-hidden group shadow-sm">
      <div class="flex justify-between items-start z-10 relative">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <h2 class="text-xs font-bold text-text-muted uppercase tracking-wider">MARKET OVERVIEW (IHSG)</h2>
            <?php if (!empty($ihsg['isFallback'])): ?>
              <span class="px-1.5 py-0.5 rounded bg-warning/10 border border-warning/30 text-[9px] font-bold text-warning uppercase">REALTIME CACHED</span>
            <?php else: ?>
              <span class="px-1.5 py-0.5 rounded bg-bullish/10 border border-bullish/30 text-[9px] font-bold text-bullish uppercase">LIVE YAHOO API</span>
            <?php endif; ?>
            <span data-live-status="true" class="px-1.5 py-0.5 rounded border border-bullish/30 bg-bullish/10 text-[9px] font-bold text-bullish uppercase">LIVE • syncing</span>
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold text-primary" data-realtime-symbol="^JKSE" data-realtime-field="price"><?php echo number_format($ihsg['price'], 2, '.', ','); ?></span>
            <span class="font-mono text-sm <?php echo getChangeColorClass($ihsg['change']); ?>" data-realtime-symbol="^JKSE" data-realtime-field="change">
              <?php echo ($ihsg['change'] >= 0 ? '+' : '') . number_format($ihsg['change'], 2); ?> (<?php echo formatPercent($ihsg['changePercent']); ?>)
            </span>
          </div>
        </div>
        <div class="text-right">
          <div class="flex gap-4">
            <div class="flex flex-col text-right">
              <span class="text-[10px] font-bold text-text-muted">VOL</span>
              <span class="font-mono text-xs text-primary"><?php echo number_format(($ihsg['volume'] / 1000000000), 1); ?>B</span>
            </div>
            <div class="flex flex-col text-right">
              <span class="text-[10px] font-bold text-text-muted">VAL</span>
              <span class="font-mono text-xs text-primary">9.2T</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Mini Line Chart Placeholder (SVG) -->
      <div class="h-24 w-full mt-4 z-10 relative">
        <svg class="w-full h-full preserve-3d" preserveAspectRatio="none" viewBox="0 0 400 100">
          <defs>
            <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="<?php echo $ihsg['change'] >= 0 ? '#22c55e' : '#ef4444'; ?>" stop-opacity="0.25"></stop>
              <stop offset="100%" stop-color="<?php echo $ihsg['change'] >= 0 ? '#22c55e' : '#ef4444'; ?>" stop-opacity="0"></stop>
            </linearGradient>
          </defs>
          <path class="chart-line-animate" d="M0,80 L40,75 L80,85 L120,60 L160,65 L200,40 L240,50 L280,30 L320,45 L360,20 L400,10" fill="none" stroke="<?php echo $ihsg['change'] >= 0 ? '#22c55e' : '#ef4444'; ?>" stroke-width="2"></path>
          <path d="M0,100 L0,80 L40,75 L80,85 L120,60 L160,65 L200,40 L240,50 L280,30 L320,45 L360,20 L400,10 L400,100 Z" fill="url(#chartGradient)"></path>
        </svg>
      </div>

      <div class="flex justify-between items-center mt-2 z-10 relative">
        <p class="text-[11px] <?php echo getChangeColorClass($ihsg['change']); ?> flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-bullish animate-pulse"></span>
          <?php echo $ihsg['change'] >= 0 ? 'Increased volume and price indicate bullish sentiment.' : 'Market consolidation in progress.'; ?>
        </p>
        <div class="flex gap-1">
          <button class="px-2.5 py-1 text-[11px] font-medium rounded bg-surface-container-high text-text-muted hover:text-white transition-colors">1D</button>
          <button class="px-2.5 py-1 text-[11px] font-medium rounded bg-primary text-white">1W</button>
          <button class="px-2.5 py-1 text-[11px] font-medium rounded bg-surface-container-high text-text-muted hover:text-white transition-colors">1M</button>
        </div>
      </div>
    </div>

    <!-- Mini Stats Cards -->
    <div class="w-full md:w-72 flex flex-col gap-4">
      <div class="flex-1 bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col justify-center">
        <div class="text-[10px] font-bold text-text-muted mb-1 uppercase tracking-wider">Foreign Flow</div>
        <div class="flex justify-between items-baseline mb-2">
          <div class="text-lg font-bold text-bullish">Net Buy</div>
          <div class="font-mono text-xs text-primary">+ Rp 452.1B</div>
        </div>
        <div class="text-[11px] text-text-muted mb-1">Volume: 1.2M saham</div>
        <div class="w-full bg-surface-container-highest h-1.5 rounded-full overflow-hidden flex">
          <div class="h-full bg-bullish w-[70%]"></div>
          <div class="h-full bg-bearish w-[30%]"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 w-full">
    <!-- Left Column (Gainers, Losers, Watchlist) -->
    <div class="lg:col-span-8 flex flex-col gap-4">
      <!-- Top Movers Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Top Gainers -->
        <div class="bg-surface-container-low border border-border-subtle rounded-lg shadow-sm overflow-hidden flex flex-col">
          <div class="px-4 py-3 border-b border-border-subtle flex items-center justify-between">
            <h3 class="text-xs font-bold text-primary uppercase tracking-wider">Top Gainers</h3>
            <button class="text-[10px] font-medium text-text-muted hover:text-white transition-colors">VIEW ALL</button>
          </div>
          <div class="flex-1 flex flex-col">
            <div class="grid grid-cols-4 px-4 py-2 bg-surface-container-high/50 border-b border-border-subtle text-[10px] font-bold text-text-muted uppercase">
              <div class="col-span-2">Ticker</div>
              <div class="text-right">Last</div>
              <div class="text-right">Chg (%)</div>
            </div>
            <div class="divide-y divide-border-subtle">
              <?php foreach ($topGainers as $g): ?>
                <a href="<?php echo BASE_URL; ?>pages/analysis.php?stock=<?php echo $g['symbol']; ?>" class="grid grid-cols-4 px-4 items-center h-9 hover:bg-surface-container-high transition-colors">
                  <div class="col-span-2 font-mono text-xs font-semibold text-primary"><?php echo $g['symbol']; ?></div>
                  <div class="text-right font-mono text-xs text-primary"><?php echo formatRupiah($g['price'], false); ?></div>
                  <div class="text-right font-mono text-xs text-bullish"><?php echo formatPercent($g['changePercent']); ?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Top Losers -->
        <div class="bg-surface-container-low border border-border-subtle rounded-lg shadow-sm overflow-hidden flex flex-col">
          <div class="px-4 py-3 border-b border-border-subtle flex items-center justify-between">
            <h3 class="text-xs font-bold text-primary uppercase tracking-wider">Top Losers</h3>
            <button class="text-[10px] font-medium text-text-muted hover:text-white transition-colors">VIEW ALL</button>
          </div>
          <div class="flex-1 flex flex-col">
            <div class="grid grid-cols-4 px-4 py-2 bg-surface-container-high/50 border-b border-border-subtle text-[10px] font-bold text-text-muted uppercase">
              <div class="col-span-2">Ticker</div>
              <div class="text-right">Last</div>
              <div class="text-right">Chg (%)</div>
            </div>
            <div class="divide-y divide-border-subtle">
              <?php foreach ($topLosers as $l): ?>
                <a href="<?php echo BASE_URL; ?>pages/analysis.php?stock=<?php echo $l['symbol']; ?>" class="grid grid-cols-4 px-4 items-center h-9 hover:bg-surface-container-high transition-colors">
                  <div class="col-span-2 font-mono text-xs font-semibold text-primary"><?php echo $l['symbol']; ?></div>
                  <div class="text-right font-mono text-xs text-primary"><?php echo formatRupiah($l['price'], false); ?></div>
                  <div class="text-right font-mono text-xs text-bearish"><?php echo formatPercent($l['changePercent']); ?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Watchlist Section -->
      <div class="bg-surface-container-low border border-border-subtle rounded-lg shadow-sm overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-border-subtle flex items-center justify-between">
          <h3 class="text-xs font-bold text-primary uppercase tracking-wider">Watchlist (Realtime)</h3>
        </div>
        <div class="w-full overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface-container-high/50 text-[10px] font-bold text-text-muted uppercase border-b border-border-subtle">
                <th class="px-4 py-2">Symbol</th>
                <th class="px-4 py-2 text-right">Price</th>
                <th class="px-4 py-2 text-right">Change</th>
                <th class="px-4 py-2 text-right">Vol (M)</th>
                <th class="px-4 py-2 text-center">Signal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle text-xs">
              <?php
              $watchlistStocks = ['BBNI', 'BMRI', 'ADRO', 'BBCA', 'TLKM'];
              foreach ($watchlistStocks as $wSym):
                $wQuote = $quotes[$wSym] ?? StockData::getQuote($wSym);
                $wSignal = SignalGenerator::generateSignal($wQuote);
              ?>
                <tr class="hover:bg-surface-container-high transition-colors cursor-pointer" onclick="window.location='<?php echo BASE_URL; ?>pages/analysis.php?stock=<?php echo $wSym; ?>'">
                  <td class="px-4 py-2.5 font-mono font-bold text-primary"><?php echo $wSym; ?></td>
                  <td class="px-4 py-2.5 text-right font-mono"><?php echo formatRupiah($wQuote['price'], false); ?></td>
                  <td class="px-4 py-2.5 text-right font-mono <?php echo getChangeColorClass($wQuote['change']); ?>"><?php echo formatPercent($wQuote['changePercent']); ?></td>
                  <td class="px-4 py-2.5 text-right font-mono text-text-muted"><?php echo number_format($wQuote['volume'] / 1000000, 1); ?></td>
                  <td class="px-4 py-2.5 text-center"><?php echo getSignalBadgeHTML($wSignal['signal']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right Column (Sector Heatmap & News) -->
    <div class="lg:col-span-4 flex flex-col gap-4">
      <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col shadow-sm">
        <div class="flex justify-between items-center mb-3 pb-2 border-b border-border-subtle">
          <h3 class="text-xs font-bold text-primary uppercase tracking-wider">SECTOR HEATMAP</h3>
          <div class="text-[10px] text-text-muted flex items-center gap-1">Performance % <span class="material-symbols-outlined text-xs">expand_more</span></div>
        </div>
        
        <!-- Heatmap Grid -->
        <div class="heatmap-grid">
          <div class="heatmap-box heatmap-bullish-high col-span-2">
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Financials</div>
            <div class="text-center my-auto">
              <div class="text-lg font-bold text-bullish">+1.20%</div>
            </div>
          </div>
          <div class="heatmap-box heatmap-bearish-high">
            <div class="text-[9px] font-bold text-text-muted uppercase truncate">Infrastructure</div>
            <div class="text-center my-auto">
              <div class="text-sm font-bold text-bearish">-0.80%</div>
            </div>
          </div>
          <div class="heatmap-box heatmap-bullish-low">
            <div class="text-[9px] font-bold text-text-muted uppercase truncate">Consumer</div>
            <div class="text-center my-auto">
              <div class="text-sm font-bold text-bullish">+0.10%</div>
            </div>
          </div>
          <div class="heatmap-box heatmap-bullish-high">
            <div class="text-[9px] font-bold text-text-muted uppercase truncate">Basic Materials</div>
            <div class="text-center my-auto">
              <div class="text-xs font-bold text-bullish">+0.50%</div>
            </div>
          </div>
          <div class="heatmap-box heatmap-bearish-high">
            <div class="text-[9px] font-bold text-text-muted uppercase truncate">Energy</div>
            <div class="text-center my-auto">
              <div class="text-xs font-bold text-bearish">-1.50%</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trading Style Recommendations -->
      <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col gap-3 relative overflow-hidden">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-primary"></span>
          <h4 class="text-xs font-bold text-primary uppercase tracking-wider">AI TRADING CLASSIFICATION</h4>
        </div>

        <div class="grid gap-3">
          <div class="rounded border border-border-subtle bg-surface-container p-3">
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-2">1. BSJP (Beli Sore Jual Pagi)</div>
            <ul class="space-y-2">
              <?php foreach ($tradingClassifications['BSJP'] as $item): ?>
                <li class="text-xs text-text-muted leading-tight">
                  <div class="flex items-center justify-between gap-2">
                    <strong class="text-primary"><?php echo htmlspecialchars($item['symbol']); ?></strong>
                    <span class="text-[10px] text-bullish font-semibold"><?php echo htmlspecialchars($item['signal']); ?></span>
                  </div>
                  <div class="text-[11px] text-text-muted mt-0.5">Conf. <?php echo (int)$item['confidence']; ?>% • RSI <?php echo number_format($item['rsi'], 1); ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="rounded border border-border-subtle bg-surface-container p-3">
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-2">2. BPJS (Beli Pagi Jual Sore)</div>
            <ul class="space-y-2">
              <?php foreach ($tradingClassifications['BPJS'] as $item): ?>
                <li class="text-xs text-text-muted leading-tight">
                  <div class="flex items-center justify-between gap-2">
                    <strong class="text-primary"><?php echo htmlspecialchars($item['symbol']); ?></strong>
                    <span class="text-[10px] text-bearish font-semibold"><?php echo htmlspecialchars($item['signal']); ?></span>
                  </div>
                  <div class="text-[11px] text-text-muted mt-0.5">Conf. <?php echo (int)$item['confidence']; ?>% • RSI <?php echo number_format($item['rsi'], 1); ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- Mini News Alert -->
      <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col gap-2 relative overflow-hidden">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-bullish"></span>
          <h4 class="text-xs font-bold text-primary uppercase tracking-wider">AI MARKET SNAPSHOT</h4>
        </div>
        <ul class="space-y-2 mt-1">
          <?php foreach ($dashboardAiSummary as $item): ?>
            <li class="text-xs text-text-muted leading-tight">
              <strong class="text-primary"><?php echo htmlspecialchars($item['symbol']); ?></strong> <?php echo htmlspecialchars($item['signal']); ?> • confidence <?php echo (int)$item['confidence']; ?>%
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
