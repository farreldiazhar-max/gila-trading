<?php
$pageTitle = 'Stock Analysis';
$activePage = 'analysis';

require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';
require_once __DIR__ . '/../classes/AiRecommendationService.php';

$stockCode = isset($_GET['stock']) ? strtoupper(trim($_GET['stock'])) : 'BBCA';
$range = isset($_GET['range']) ? $_GET['range'] : '1y';
$interval = isset($_GET['interval']) ? $_GET['interval'] : '1d';

$quote = StockData::getQuote($stockCode, $range, $interval);
$signal = AiRecommendationService::buildRecommendation($stockCode, $quote);

function getFundamentalProfile($stockCode, $quote, $signal) {
  $symbol = strtoupper(trim($stockCode));
  $profiles = [
    'BBCA' => ['per' => 16.8, 'pbv' => 2.1, 'roe' => 12.8, 'growth' => 8.5, 'margin' => 31.2, 'debt' => 0.42, 'summary' => 'Bank besar dengan struktur modal kuat, pendapatan stabil, dan prospek pertumbuhan yang konsisten.'],
    'BBRI' => ['per' => 13.4, 'pbv' => 1.9, 'roe' => 14.6, 'growth' => 7.3, 'margin' => 40.1, 'debt' => 0.61, 'summary' => 'Perbankan dengan kualitas kredit yang cukup baik dan marjin bunga bersih yang tetap sehat.'],
    'BMRI' => ['per' => 14.2, 'pbv' => 2.3, 'roe' => 16.2, 'growth' => 9.1, 'margin' => 38.6, 'debt' => 0.56, 'summary' => 'Bank dengan aset besar dan ekspansi yang terjaga, cocok untuk investor yang mencari kualitas.'],
    'BBNI' => ['per' => 12.7, 'pbv' => 2.0, 'roe' => 15.8, 'growth' => 10.2, 'margin' => 36.4, 'debt' => 0.53, 'summary' => 'Profil fundamental solid dengan pendapatan bunga yang terjaga dan efisiensi operasi yang baik.'],
    'TLKM' => ['per' => 22.1, 'pbv' => 4.8, 'roe' => 21.7, 'growth' => 5.9, 'margin' => 24.8, 'debt' => 0.35, 'summary' => 'Infrastruktur digital yang kuat, namun valuasi cukup premium dibanding sektor rata-rata.'],
    'UNVR' => ['per' => 31.6, 'pbv' => 10.2, 'roe' => 32.4, 'growth' => 6.2, 'margin' => 17.2, 'debt' => 0.12, 'summary' => 'Brand kuat dan margin stabil, tetapi valuasi sudah tinggi sehingga perlu kehati-hatian.'],
    'GOTO' => ['per' => 48.3, 'pbv' => 6.1, 'roe' => 12.6, 'growth' => 18.7, 'margin' => -9.5, 'debt' => 0.84, 'summary' => 'Pertumbuhan cepat tetapi profitabilitas masih rentan; fundamentalnya bergantung pada efisiensi bisnis.'],
    'ARTO' => ['per' => 19.8, 'pbv' => 3.4, 'roe' => 17.3, 'growth' => 14.6, 'margin' => 12.4, 'debt' => 0.67, 'summary' => 'Ekspansi bisnis cukup menarik, dengan skala usaha yang berkembang dan profitabilitas menanjak.'],
    'BRPT' => ['per' => 22.4, 'pbv' => 2.8, 'roe' => 12.7, 'growth' => 16.5, 'margin' => 5.4, 'debt' => 0.92, 'summary' => 'Potensi pertumbuhan industri yang kuat, walau leverage dan profitabilitas masih perlu dipantau.'],
    'CUAN' => ['per' => 24.9, 'pbv' => 5.6, 'roe' => 22.8, 'growth' => 11.9, 'margin' => 9.1, 'debt' => 0.41, 'summary' => 'Bisnis yang tumbuh dengan baik dan marjin yang membaik, namun valuasi masih perlu diperhatikan.'],
    'ADRO' => ['per' => 11.6, 'pbv' => 1.2, 'roe' => 10.4, 'growth' => 7.1, 'margin' => 18.7, 'debt' => 0.57, 'summary' => 'Sektor komoditas dengan daya tahan bisnis baik dan valuasi yang cukup masuk akal.'],
    'ASII' => ['per' => 17.5, 'pbv' => 2.4, 'roe' => 13.8, 'growth' => 8.4, 'margin' => 8.6, 'debt' => 0.41, 'summary' => 'Konsolidasi bisnis yang stabil dengan fundamental industri otomotif yang masih berkelanjutan.'],
    'IHSG' => ['per' => 18.1, 'pbv' => 1.8, 'roe' => 10.3, 'growth' => 6.7, 'margin' => 16.8, 'debt' => 0.64, 'summary' => 'Pasar domestik menunjukkan ketahanan dengan valuasi yang masih masuk akal untuk tren jangka menengah.'],
  ];

  $profile = $profiles[$symbol] ?? ['per' => 20.1, 'pbv' => 3.2, 'roe' => 15.6, 'growth' => 9.8, 'margin' => 16.3, 'debt' => 0.58, 'summary' => 'Profil fundamental secara umum seimbang dan cukup layak dipantau berdasarkan kondisi pasar saat ini.'];

  $changePercent = (float)($quote['changePercent'] ?? 0);
  $rsi = (float)($signal['rsi'] ?? 50);
  $sentimentScore = 0;
  if ($changePercent > 2) $sentimentScore += 2;
  elseif ($changePercent > 0) $sentimentScore += 1;
  elseif ($changePercent < -2) $sentimentScore -= 2;
  elseif ($changePercent < 0) $sentimentScore -= 1;

  if (strpos($signal['signal'], 'BUY') !== false) $sentimentScore += 1;
  elseif (strpos($signal['signal'], 'SELL') !== false) $sentimentScore -= 1;

  if ($rsi > 60) $sentimentScore += 1;
  elseif ($rsi < 40) $sentimentScore -= 1;

  $sentimentLabel = $sentimentScore >= 2 ? 'POSITIF' : ($sentimentScore <= -2 ? 'NEGATIF' : 'NETRAL');
  $sentimentText = $sentimentLabel === 'POSITIF'
    ? 'Sentimen pasar saat ini cenderung positif karena momentum harga menguat, sinyal teknikal mengarah ke beli, dan RSI belum berada di area overbought.'
    : ($sentimentLabel === 'NEGATIF'
      ? 'Sentimen pasar cenderung negatif karena pergerakan harga melemah, sinyal teknikal condong jual, dan RSI mendekati area jenuh jual.'
      : 'Sentimen pasar sedang netral karena pergerakan harga dan sinyal teknikal belum menunjukkan bias yang kuat ke satu arah.');

  return [
    'profile' => $profile,
    'sentimentLabel' => $sentimentLabel,
    'sentimentText' => $sentimentText,
    'changePercent' => $changePercent,
    'rsi' => $rsi,
    'signal' => $signal['signal'] ?? 'HOLD',
  ];
}

$fundamental = getFundamentalProfile($stockCode, $quote, $signal);

$institutionalBuy = $signal['bandarmology']['buy_power'] ?? 0;
$institutionalSell = $signal['bandarmology']['sell_power'] ?? 0;
$institutionalNet = $institutionalBuy + $institutionalSell > 0 ? round((($institutionalBuy - $institutionalSell) / max(1, $institutionalBuy + $institutionalSell)) * 100) : 0;
$institutionalLabel = $institutionalNet >= 0 ? '+' . $institutionalNet . '%' : $institutionalNet . '%';
$institutionalText = $institutionalNet >= 10 ? 'Volume institusi kuat mendukung bias pasar.' : ($institutionalNet <= -10 ? 'Tekanan jual institusi meningkat.' : 'Volume institusi relatif seimbang.');

$newsLabel = htmlspecialchars($signal['sentiment']['label'] ?? 'NETRAL');
$newsScore = isset($signal['sentiment']['score']) ? round($signal['sentiment']['score']) : null;
$newsText = $newsScore !== null ? $newsScore . '/80' : 'Data sentimen berita tidak tersedia.';

$fundScore = $signal['fundamental']['score'] ?? 60;
if ($fundScore >= 88) {
    $sectorOutperformanceLabel = 'Top 1%';
    $sectorOutperformanceText = 'Fundamental sangat kuat dibanding sektor.';
    $sectorOutperformanceClass = 'bg-bullish/10 border-bullish text-bullish';
} elseif ($fundScore >= 78) {
    $sectorOutperformanceLabel = 'Top 5%';
    $sectorOutperformanceText = 'Fundamental berada di atas mayoritas saham sejenis.';
    $sectorOutperformanceClass = 'bg-bullish/10 border-bullish text-bullish';
} elseif ($fundScore >= 68) {
    $sectorOutperformanceLabel = 'Top 15%';
    $sectorOutperformanceText = 'Fundamental relatif baik dibandingkan peer sektoral.';
    $sectorOutperformanceClass = 'bg-warning/10 border-warning text-warning';
} elseif ($fundScore >= 58) {
    $sectorOutperformanceLabel = 'Top 30%';
    $sectorOutperformanceText = 'Fundamental moderat dengan potensi outperformance.';
    $sectorOutperformanceClass = 'bg-warning/10 border-warning text-warning';
} else {
    $sectorOutperformanceLabel = 'Below 50%';
    $sectorOutperformanceText = 'Fundamental masih di bawah rata-rata sektor.';
    $sectorOutperformanceClass = 'bg-bearish/10 border-bearish text-bearish';
}

$institutionalClass = $institutionalNet >= 10 ? 'bg-bullish/10 border-bullish text-bullish' : ($institutionalNet <= -10 ? 'bg-bearish/10 border-bearish text-bearish' : 'bg-surface-container border-border-subtle text-text-muted');
$newsClass = $newsLabel === 'POSITIF' ? 'bg-bullish/10 border-bullish text-bullish' : ($newsLabel === 'NEGATIF' ? 'bg-bearish/10 border-bearish text-bearish' : 'bg-warning/10 border-warning text-warning');
$sentimentBadgeClass = $newsLabel === 'POSITIF' ? 'text-bullish border-bullish bg-bullish/10' : ($newsLabel === 'NEGATIF' ? 'text-bearish border-bearish bg-bearish/10' : 'text-warning border-warning bg-warning/10');

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col w-full gap-6">
  <!-- Stock Header Bar -->
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
      <div class="flex items-center gap-3 mb-1">
        <h1 class="text-3xl font-bold text-primary tracking-tight" id="stockSymbolHeader"><?php echo htmlspecialchars($quote['symbol']); ?>.JK</h1>
        <span class="px-2 py-0.5 rounded bg-surface-container-high border border-border-subtle text-[10px] font-bold text-text-muted uppercase">IDX</span>
        <?php if (!empty($quote['isFallback'])): ?>
          <span class="px-2 py-0.5 rounded bg-warning/10 border border-warning/30 text-[10px] font-bold text-warning uppercase" title="Yahoo Finance rate limited, using cached data">SIMULATED REALTIME</span>
        <?php else: ?>
          <span class="px-2 py-0.5 rounded bg-bullish/10 border border-bullish/30 text-[10px] font-bold text-bullish uppercase">LIVE YAHOO API</span>
        <?php endif; ?>
      </div>
      <p class="text-sm text-text-muted" id="stockNameHeader"><?php echo htmlspecialchars($quote['shortName']); ?></p>
    </div>
    <div class="flex items-end gap-6">
      <div class="text-right">
        <div class="text-3xl font-bold text-primary tracking-tight" id="stockPriceHeader"><?php echo formatRupiah($quote['price']); ?></div>
        <div class="font-mono <?php echo getChangeColorClass($quote['change']); ?> flex items-center justify-end gap-1 mt-1 text-sm bg-surface-container-low px-2 py-0.5 rounded inline-flex border border-border-subtle" id="stockChangeHeader">
          <span class="material-symbols-outlined text-[14px]"><?php echo $quote['change'] >= 0 ? 'arrow_upward' : 'arrow_downward'; ?></span>
          <?php echo ($quote['change'] >= 0 ? '+' : '') . number_format($quote['change'], 2); ?> (<?php echo formatPercent($quote['changePercent']); ?>)
        </div>
      </div>
      <button class="btn btn-outline flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]">add</span> ADD TO WATCHLIST
      </button>
    </div>
  </div>

  <!-- Main Analysis Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column (Chart & Technical Indicators) -->
    <div class="lg:col-span-8 flex flex-col gap-6">
      <!-- Main Chart Container -->
      <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col shadow-sm relative">
        <div class="flex justify-between items-center mb-4 border-b border-border-subtle pb-4 flex-wrap gap-2">
          <!-- Timeframe Range Selector Buttons -->
          <div class="flex gap-1 bg-surface-container-high rounded p-1" id="timeframeSelector">
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors" data-range="1d" data-interval="5m">1D</button>
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors" data-range="5d" data-interval="15m">1W</button>
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors" data-range="1mo" data-interval="1d">1M</button>
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors" data-range="ytd" data-interval="1d">YTD</button>
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium bg-primary text-white" data-range="1y" data-interval="1d">1Y</button>
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors" data-range="3y" data-interval="1wk">3Y</button>
            <button class="timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors" data-range="5y" data-interval="1wk">5Y</button>
          </div>
          <div class="flex gap-2 items-center flex-wrap">
            <button id="ma5Toggle" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-border-subtle bg-surface-container text-sm font-medium text-text-muted hover:text-white transition-colors">
              <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
              MA 5
              <span id="ma5ToggleState" class="text-[10px] font-bold uppercase tracking-wider">ON</span>
            </button>
            <button id="ma10Toggle" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-border-subtle bg-surface-container text-sm font-medium text-text-muted hover:text-white transition-colors">
              <span class="w-2 h-2 rounded-full bg-violet-400"></span>
              MA 10
              <span id="ma10ToggleState" class="text-[10px] font-bold uppercase tracking-wider">ON</span>
            </button>
            <button id="ma50Toggle" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-border-subtle bg-surface-container text-sm font-medium text-text-muted hover:text-white transition-colors">
              <span class="w-2 h-2 rounded-full bg-blue-400"></span>
              MA 50
              <span id="ma50ToggleState" class="text-[10px] font-bold uppercase tracking-wider">ON</span>
            </button>
            <button id="bollingerToggle" type="button" class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-border-subtle bg-surface-container text-sm font-medium text-text-muted hover:text-white transition-colors">
              <span class="w-2 h-2 rounded-full bg-amber-400"></span>
              Bollinger Bands
              <span id="bollingerToggleState" class="text-[10px] font-bold uppercase tracking-wider">ON</span>
            </button>
          </div>
        </div>

        <!-- Lightweight Charts Mounting Container -->
        <div id="chartContainer" class="w-full h-[420px] rounded relative" data-stock="<?php echo htmlspecialchars($stockCode); ?>" data-history='<?php echo json_encode($quote['history'] ?? []); ?>'>
        </div>
      </div>

      <!-- Technical Indicators Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- RSI Card -->
        <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col justify-between hover:border-border-medium transition-colors">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">RSI (14)</span>
            <span class="font-mono text-sm text-primary bg-surface-container px-2 py-0.5 rounded border border-border-subtle" id="rsiValueDisplay"><?php echo number_format((float)$signal['rsi'], 1); ?></span>
          </div>
          <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden relative mb-2">
            <div class="h-full bg-primary rounded-full relative z-10" id="rsiBarDisplay" style="width: <?php echo min(100, max(0, $signal['rsi'])); ?>%;"></div>
          </div>
          <div class="flex justify-between text-[10px] text-text-muted font-bold uppercase tracking-wider">
            <span>Oversold (&lt;30)</span>
            <span>Overbought (&gt;70)</span>
          </div>
        </div>

        <!-- MACD Card -->
        <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col justify-between hover:border-border-medium transition-colors">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">MACD (12,26,9)</span>
            <span class="font-mono <?php echo strpos($signal['macd']['trend'], 'BULLISH') !== false ? 'text-bullish' : 'text-bearish'; ?> flex items-center gap-1 text-xs bg-surface-container px-2 py-0.5 rounded border border-border-subtle" id="macdTrendDisplay">
              <span class="material-symbols-outlined text-[14px]">trending_up</span> <?php echo str_replace('_', ' ', $signal['macd']['trend']); ?>
            </span>
          </div>
          <div class="h-10 w-full flex items-end gap-1">
            <div class="flex-1 bg-bullish/40 h-[20%]"></div>
            <div class="flex-1 bg-bullish/50 h-[40%]"></div>
            <div class="flex-1 bg-bullish/70 h-[65%]"></div>
            <div class="flex-1 bg-bullish/90 h-[85%]"></div>
            <div class="flex-1 bg-bullish h-[100%]"></div>
          </div>
        </div>

        <!-- Bollinger Card -->
        <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col justify-between hover:border-border-medium transition-colors">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Bollinger Bands</span>
            <span class="font-mono text-sm text-primary bg-surface-container px-2 py-0.5 rounded border border-border-subtle" id="bollingerStatusDisplay"><?php echo htmlspecialchars($signal['bollinger']['status'] ?? 'NEUTRAL'); ?></span>
          </div>
          <div class="text-xs text-text-muted space-y-1">
            <div>Upper: <span class="font-mono text-primary" id="bollingerUpperDisplay"><?php echo number_format((float)($signal['bollinger']['upper'] ?? 0), 2); ?></span></div>
            <div>Middle: <span class="font-mono text-primary" id="bollingerMiddleDisplay"><?php echo number_format((float)($signal['bollinger']['middle'] ?? 0), 2); ?></span></div>
            <div>Lower: <span class="font-mono text-primary" id="bollingerLowerDisplay"><?php echo number_format((float)($signal['bollinger']['lower'] ?? 0), 2); ?></span></div>
          </div>
        </div>

        <!-- Bandarmology Card -->
        <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col justify-between hover:border-border-medium transition-colors">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Bandarmology</span>
            <span class="font-mono text-sm text-primary bg-surface-container px-2 py-0.5 rounded border border-border-subtle" id="bandarmologyStatusDisplay"><?php echo htmlspecialchars($signal['bandarmology']['status'] ?? 'NEUTRAL'); ?></span>
          </div>
          <div class="text-xs text-text-muted space-y-1">
            <div>Skor: <span class="font-mono text-primary" id="bandarmologyScoreDisplay"><?php echo number_format((float)($signal['bandarmology']['score'] ?? 50), 0); ?></span></div>
            <div>Bull Power: <span class="font-mono text-primary" id="bandarmologyBuyDisplay"><?php echo number_format((float)($signal['bandarmology']['buy_power'] ?? 0), 0); ?></span></div>
            <div>Bear Power: <span class="font-mono text-primary" id="bandarmologySellDisplay"><?php echo number_format((float)($signal['bandarmology']['sell_power'] ?? 0), 0); ?></span></div>
          </div>
        </div>

        <!-- Stochastic + EMA Card -->
        <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col justify-between hover:border-border-medium transition-colors">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">EMA / Stochastic</span>
            <span class="font-mono text-sm text-primary bg-surface-container px-2 py-0.5 rounded border border-border-subtle" id="emaStochDisplay"><?php echo number_format((float)($signal['ema20'] ?? 0), 2); ?> / <?php echo number_format((float)($signal['stochastic']['k'] ?? 0), 1); ?></span>
          </div>
          <div class="text-xs text-text-muted space-y-1">
            <div>EMA20: <span class="font-mono text-primary" id="ema20Display"><?php echo number_format((float)($signal['ema20'] ?? 0), 2); ?></span></div>
            <div>Stochastic K: <span class="font-mono text-primary" id="stochKDisplay"><?php echo number_format((float)($signal['stochastic']['k'] ?? 0), 1); ?></span></div>
            <div>Status: <span class="font-mono text-primary" id="stochStatusDisplay"><?php echo htmlspecialchars($signal['stochastic']['status'] ?? 'NEUTRAL'); ?></span></div>
          </div>
        </div>

        <!-- Advanced Structure Card -->
        <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5 flex flex-col justify-between hover:border-border-medium transition-colors md:col-span-2">
          <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Support / Resistance & Structure</span>
            <span class="font-mono text-sm text-primary bg-surface-container px-2 py-0.5 rounded border border-border-subtle" id="structureStatusDisplay"><?php echo htmlspecialchars($signal['breakout']['status'] ?? 'CONSOLIDATION'); ?></span>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-text-muted">
            <div>Support: <span class="font-mono text-primary" id="supportDisplay"><?php echo number_format((float)($signal['support_resistance']['support'] ?? 0), 2); ?></span></div>
            <div>Resistance: <span class="font-mono text-primary" id="resistanceDisplay"><?php echo number_format((float)($signal['support_resistance']['resistance'] ?? 0), 2); ?></span></div>
            <div>Pattern: <span class="font-mono text-primary" id="patternDisplay"><?php echo htmlspecialchars($signal['pattern']['name'] ?? 'NONE'); ?></span></div>
            <div>Breakout: <span class="font-mono text-primary" id="breakoutDisplay"><?php echo htmlspecialchars($signal['breakout']['status'] ?? 'CONSOLIDATION'); ?></span></div>
            <div>Volume: <span class="font-mono text-primary" id="volumeDisplay"><?php echo htmlspecialchars($signal['volume_profile']['status'] ?? 'NORMAL'); ?></span></div>
            <div>MTF Bias: <span class="font-mono text-primary" id="mtfDisplay"><?php echo htmlspecialchars($signal['multi_timeframe']['bias'] ?? 'NEUTRAL'); ?></span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column (AI Recommendation & Fundamentals) -->
    <div class="lg:col-span-4 flex flex-col gap-6">
      <!-- AI Trading Recommendation -->
      <div class="bg-surface-container-low rounded-lg border border-bullish/30 p-5 relative overflow-hidden shadow-lg">
        <div class="relative z-10">
          <h2 class="text-xs font-bold text-text-muted mb-2 tracking-wider">AI RECOMMENDATION</h2>
          <div class="text-3xl font-bold <?php echo strpos($signal['signal'], 'BUY') !== false ? 'text-bullish' : (strpos($signal['signal'], 'SELL') !== false ? 'text-bearish' : 'text-warning'); ?> tracking-tight mb-4" id="aiSignalDisplay"><?php echo $signal['signal']; ?></div>
          
          <div class="flex flex-col gap-1.5 mb-5 bg-surface-container p-3 rounded border border-border-subtle">
            <div class="flex justify-between items-center text-sm">
              <span class="text-text-muted font-medium">Confidence Score</span>
              <span class="font-mono text-bullish font-bold" id="aiConfidenceDisplay"><?php echo $signal['confidence']; ?>%</span>
            </div>
            <div class="w-full bg-surface-container-highest h-2 rounded-full overflow-hidden mt-1">
              <div class="h-full bg-bullish rounded-full" id="aiConfidenceBar" style="width: <?php echo $signal['confidence']; ?>%;"></div>
            </div>
          </div>

          <div class="space-y-2.5 text-xs">
            <div class="flex justify-between items-center py-1.5 border-b border-border-subtle">
              <span class="font-medium text-text-muted">ENTRY RANGE</span>
              <span class="font-mono text-primary text-sm font-semibold" id="aiEntryDisplay"><?php echo formatRupiah($signal['entry_min'], false) . ' - ' . formatRupiah($signal['entry_max'], false); ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-border-subtle">
              <span class="font-medium text-text-muted">TARGET 1</span>
              <span class="font-mono text-primary text-sm font-semibold" id="aiTp1Display"><?php echo formatRupiah($signal['target_1'], false); ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-border-subtle">
              <span class="font-medium text-text-muted">TARGET 2</span>
              <span class="font-mono text-primary text-sm font-semibold" id="aiTp2Display"><?php echo formatRupiah($signal['target_2'], false); ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-border-subtle">
              <span class="font-medium text-text-muted">STOP LOSS</span>
              <span class="font-mono text-bearish text-sm font-semibold" id="aiSlDisplay"><?php echo formatRupiah($signal['stop_loss'], false); ?></span>
            </div>
            <div class="flex justify-between items-center pt-1.5">
              <span class="font-medium text-text-muted">RISK/REWARD</span>
              <span class="font-mono text-bullish font-bold border border-bullish/30 bg-bullish/10 px-2 py-0.5 rounded" id="aiRrDisplay"><?php echo $signal['risk_reward']; ?></span>
            </div>
          </div>

        </div>
      </div>

      <!-- Technical Narrative -->
      <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5">
        <h3 class="text-xs font-bold text-primary mb-3 tracking-wider uppercase">Technical Narrative</h3>
        <p class="text-xs text-text-muted leading-relaxed" id="aiReasoningDisplay">
          <?php echo htmlspecialchars($signal['technical_narrative'] ?? $signal['reasoning']); ?>
        </p>
      </div>

      <!-- AI Deep Analysis -->
      <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5">
        <div class="flex items-center justify-between gap-2 mb-3">
          <h3 class="text-xs font-bold text-primary tracking-wider uppercase">AI Deep Analysis</h3>
          <span class="px-2 py-0.5 rounded-full border border-border-subtle bg-surface-container text-[10px] font-bold uppercase tracking-wider text-text-muted">
            <?php echo htmlspecialchars(strtoupper($signal['ai_provider'])); ?>
          </span>
        </div>
        <p class="text-sm text-text-muted leading-relaxed mb-4">
          <?php echo htmlspecialchars($signal['deep_analysis']); ?>
        </p>
        <div class="space-y-3">
          <div>
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">Poin Kekuatan</div>
            <ul class="text-xs text-text-muted space-y-1">
              <?php foreach ($signal['strengths'] as $strength): ?>
                <li>• <?php echo htmlspecialchars($strength); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div>
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">Poin Risiko</div>
            <ul class="text-xs text-text-muted space-y-1">
              <?php foreach ($signal['risks'] as $risk): ?>
                <li>• <?php echo htmlspecialchars($risk); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="rounded border border-border-subtle bg-surface-container p-3">
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">Next Step</div>
            <p class="text-sm text-primary font-medium">
              <?php echo htmlspecialchars($signal['next_step']); ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Fundamental Summary -->
      <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5">
        <div class="flex items-center justify-between gap-2 mb-4">
          <h3 class="text-xs font-bold text-primary tracking-wider uppercase">Fundamental Analysis</h3>
          <span class="px-2 py-0.5 rounded-full border border-border-subtle bg-surface-container text-[10px] font-bold uppercase tracking-wider text-text-muted">
            <?php echo htmlspecialchars($signal['fundamental']['label']); ?>
          </span>
        </div>
        <p class="text-sm text-text-muted leading-relaxed mb-4">
          <?php echo htmlspecialchars($signal['fundamental']['summary']); ?>
        </p>
        <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-1 bg-surface-container p-3 rounded border border-border-subtle">
            <span class="text-[10px] font-bold text-text-muted uppercase">PER</span>
            <span class="font-mono text-primary text-sm"><?php echo number_format($signal['fundamental']['per'], 1); ?>x</span>
          </div>
          <div class="flex flex-col gap-1 bg-surface-container p-3 rounded border border-border-subtle">
            <span class="text-[10px] font-bold text-text-muted uppercase">PBV</span>
            <span class="font-mono text-primary text-sm"><?php echo number_format($signal['fundamental']['pbv'], 1); ?>x</span>
          </div>
          <div class="flex flex-col gap-1 bg-surface-container p-3 rounded border border-border-subtle">
            <span class="text-[10px] font-bold text-text-muted uppercase">ROE</span>
            <span class="font-mono text-bullish text-sm font-semibold"><?php echo number_format($signal['fundamental']['roe'], 1); ?>%</span>
          </div>
          <div class="flex flex-col gap-1 bg-surface-container p-3 rounded border border-border-subtle">
            <span class="text-[10px] font-bold text-text-muted uppercase">Growth</span>
            <span class="font-mono text-primary text-sm"><?php echo number_format($signal['fundamental']['growth'], 1); ?>%</span>
          </div>
        </div>
      </div>

      <!-- Market Sentiment -->
      <div class="bg-surface-container-low rounded-lg border border-border-subtle p-5">
        <div class="flex items-center justify-between gap-2 mb-4">
          <h3 class="text-xs font-bold text-primary tracking-wider uppercase">Sentimen Pasar</h3>
          <span class="px-2 py-0.5 rounded-full border border-border-subtle bg-surface-container text-[10px] font-bold uppercase tracking-wider text-text-muted">
            <?php echo htmlspecialchars($signal['sentiment']['label'] ?? 'NETRAL'); ?>
          </span>
        </div>
        <div class="grid grid-cols-1 gap-3">
          <div class="bg-surface-container p-4 rounded-lg border border-border-subtle <?php echo $institutionalClass; ?>">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg <?php echo $institutionalNet >= 10 ? 'text-bullish' : ($institutionalNet <= -10 ? 'text-bearish' : 'text-text-muted'); ?>">stacked_line_chart</span>
                <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Institutional Volume</span>
              </div>
              <span class="font-mono text-sm font-semibold"><?php echo htmlspecialchars($institutionalLabel); ?></span>
            </div>
            <p class="text-xs text-text-muted leading-relaxed"><?php echo htmlspecialchars($institutionalText); ?></p>
          </div>
          <div class="bg-surface-container p-4 rounded-lg border border-border-subtle <?php echo $newsClass; ?>">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg <?php echo $newsLabel === 'POSITIF' ? 'text-bullish' : ($newsLabel === 'NEGATIF' ? 'text-bearish' : 'text-warning'); ?>">newspaper</span>
                <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">News Sentiment</span>
              </div>
              <span class="font-mono text-sm font-semibold <?php echo $sentimentBadgeClass; ?> px-2 py-0.5 rounded-full"><?php echo $newsLabel; ?></span>
            </div>
            <p class="text-xs text-text-muted leading-relaxed"><?php echo htmlspecialchars($signal['sentiment']['summary']); ?></p>
            <div class="mt-2 text-[10px] text-text-muted">Score: <?php echo $newsText; ?></div>
          </div>
          <div class="bg-surface-container p-4 rounded-lg border border-border-subtle <?php echo $sectorOutperformanceClass; ?>">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-primary">trending_up</span>
                <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Sector Outperformance</span>
              </div>
              <span class="font-mono text-sm font-semibold"><?php echo htmlspecialchars($sectorOutperformanceLabel); ?></span>
            </div>
            <p class="text-xs text-text-muted leading-relaxed"><?php echo htmlspecialchars($sectorOutperformanceText); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
