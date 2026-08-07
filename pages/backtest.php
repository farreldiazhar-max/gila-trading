<?php
session_start();

$pageTitle = 'Backtesting Engine';
$activePage = 'backtest';
require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/TechnicalAnalysis.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$availableInstruments = [
  '^JKSE' => 'IHSG / JKSE',
  'BBCA' => 'BBCA (Bank Central Asia)',
  'BBRI' => 'BBRI (Bank BRI)',
  'BMRI' => 'BMRI (Bank Mandiri)',
  'BBNI' => 'BBNI (Bank BNI)',
  'TLKM' => 'TLKM (Telkom Indonesia)',
  'ASII' => 'ASII (Astra International)',
  'UNVR' => 'UNVR (Unilever Indonesia)',
  'ADRO' => 'ADRO (Adaro Energy)',
  'ARTO' => 'ARTO (Trimegah Sekuritas)',
  'BRPT' => 'BRPT (Barito Pacific)',
  'GOTO' => 'GOTO (GoTo Group)',
  'EMTK' => 'EMTK (Elang Mahkota Teknologi)',
  'PGAS' => 'PGAS (Perusahaan Gas Negara)',
  'SMGR' => 'SMGR (Semen Indonesia)',
  'KLBF' => 'KLBF (Kalbe Farma)',
  'INDF' => 'INDF (Indofood Sukses Makmur)',
  'ANTM' => 'ANTM (Aneka Tambang)',
  'AMMN' => 'AMMN (Alamtri Resources)',
  'MDKA' => 'MDKA (Merdeka Copper Gold)',
  'PNBN' => 'PNBN (Bank Pan Indonesia)',
  'PNLF' => 'PNLF (Panin Financial)',
  'BREN' => 'BREN (Baramulti Suksessarana)',
  'CPIN' => 'CPIN (Charoen Pokphand Indonesia)',
  'TPIA' => 'TPIA (Chandra Asri Pacific)',
  'ACES' => 'ACES (Ace Hardware Indonesia)',
  'ITMG' => 'ITMG (Indo Tambangraya Megah)',
  'AALI' => 'AALI (Astra Agro Lestari)',
  'PLN' => 'PLN (Perusahaan Listrik Negara)',
  'EXCL' => 'EXCL (XL Axiata)',
  'MNCN' => 'MNCN (Media Nusantara Citra)',
  'ELSA' => 'ELSA (Elnusa)',
  'HRUM' => 'HRUM (Harum Energy)',
  'KAEF' => 'KAEF (Kimia Farma)',
  'INDY' => 'INDY (Indika Energy)',
  'JPFA' => 'JPFA (Japfa Comfeed)',
  'WSKT' => 'WSKT (Waskita Karya)',
  'DOID' => 'DOID (Delta Dunia Makmur)',
  'INKP' => 'INKP (Indah Kiat Pulp & Paper)',
  'PTBA' => 'PTBA (Bukit Asam)',
  'HMSP' => 'HMSP (HM Sampoerna)',
  'ICBP' => 'ICBP (Indofood CBP Sukses Makmur)',
  'INCO' => 'INCO (Vale Indonesia)',
  'INTP' => 'INTP (Indocement Tunggal Prakarsa)',
  'KBLF' => 'KBLF (Kabelindo Murni Industrial)',
  'LPPF' => 'LPPF (Matahari Department Store)',
  'MAPI' => 'MAPI (Mitra Adiperkasa)',
  'PTPP' => 'PTPP (PP)',
  'SCMA' => 'SCMA (Surya Citra Media)',
  'SIDO' => 'SIDO (Sido Muncul)',
  'TBIG' => 'TBIG (Tower Bersama Infrastructure)',
  'TBLA' => 'TBLA (Garudafood Putra Putri Jaya)',
  'TINS' => 'TINS (Timah)',
  'UNTR' => 'UNTR (United Tractors)',
  'WIKA' => 'WIKA (Wijaya Karya)',
  'WOWS' => 'WOWS (Wide Orbit Western)',
  'BBTN' => 'BBTN (Bank Tabungan Negara)',
  'BIRD' => 'BIRD (Bersama Indoneisa)',
  'BRIS' => 'BRIS (Bank Syariah Indonesia)',
  'EIDO' => 'EIDO (ETFs)',
  'JSMR' => 'JSMR (Jasa Marga)',
  'SMRA' => 'SMRA (Summarecon Agung)',
  'SRIL' => 'SRIL (Sri Rejeki Isman)',
  'TBUP' => 'TBUP (Tunas Baru Lampung)',
  'TKIM' => 'TKIM (Pabrik Kertas Tjiwi Kimia)',
  'ADHI' => 'ADHI (Adhi Karya)',
  'AKRA' => 'AKRA (AKR Corporindo)',
  'BSDE' => 'BSDE (Bumi Serpong Damai)',
  'CMPP' => 'CMPP (Ciputra Development)',
  'CTRA' => 'CTRA (Ciputra Development)',
  'EWON' => 'EWON (Erajaya Swasembada)',
  'GEMA' => 'GEMA (Gema Grahasarana)',
  'MEDC' => 'MEDC (Medco Energi Internasional)',
  'MIRA' => 'MIRA (Mirae Asset Sekuritas?)',
  'PWON' => 'PWON (Pakuwon Jati)',
  'TOWR' => 'TOWR (Sarana Menara Nusantara)',
  'WAL' => 'WAL (Wahana Auto Ekamarga)',
  'WOCK' => 'WOCK (Wockhardt Indonesia)',
  'YOUT' => 'YOUT (Youtap Indonesia)',
  'ZBRA' => 'ZBRA (Zebra Nusantara)',
];

$idxDb = getDBConnection();
if ($idxDb) {
    try {
        $stmt = $idxDb->query("SELECT code, name FROM idx_stocks ORDER BY code ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $availableInstruments = ['^JKSE' => 'IHSG / JKSE'];
            foreach ($rows as $row) {
                $symbol = strtoupper(trim($row['code'] ?? ''));
                if ($symbol === '') {
                    continue;
                }
                $name = trim($row['name'] ?? '');
                $availableInstruments[$symbol] = $name !== '' ? sprintf('%s (%s)', $symbol, $name) : $symbol;
            }
        }
    } catch (PDOException $e) {
        // If DB is not usable, keep the fallback list.
    }
}

$selectedInstrument = $_POST['instrument'] ?? ($_SESSION['backtest_instrument'] ?? 'BBCA');
$selectedTimeframe = $_POST['timeframe'] ?? ($_SESSION['backtest_timeframe'] ?? '1D');
$startDate = $_POST['start_date'] ?? ($_SESSION['backtest_start_date'] ?? '2023-01-01');
$endDate = $_POST['end_date'] ?? ($_SESSION['backtest_end_date'] ?? '2023-12-31');
$fastPeriod = max(2, (int)($_POST['fast_period'] ?? 10));
$slowPeriod = max($fastPeriod + 1, (int)($_POST['slow_period'] ?? 50));
$rsiPeriod = max(2, (int)($_POST['rsi_period'] ?? 14));
$overbought = max(50, (float)($_POST['overbought'] ?? 70));
$oversold = min(50, (float)($_POST['oversold'] ?? 30));
$capital = max(1000000, (float)str_replace([',', ' '], '', $_POST['capital'] ?? '100000000'));
$action = $_POST['action'] ?? null;

$timeframeConfig = [
  '15m' => ['range' => '1mo', 'interval' => '15m'],
  '1H' => ['range' => '1mo', 'interval' => '1h'],
  '1D' => ['range' => '2y', 'interval' => '1d'],
  '1W' => ['range' => '5y', 'interval' => '1wk'],
];
$selectedConfig = $timeframeConfig[$selectedTimeframe] ?? $timeframeConfig['1D'];

$quote = StockData::getQuote($selectedInstrument, $selectedConfig['range'], $selectedConfig['interval']);
$history = $quote['history'] ?? [];
$filteredHistory = [];
foreach ($history as $entry) {
  $entryDate = $entry['time'] ?? '';
  if ($entryDate === '') continue;
  if (strtotime($entryDate) < strtotime($startDate)) continue;
  if (strtotime($entryDate) > strtotime($endDate)) continue;
  $filteredHistory[] = $entry;
}

if (empty($filteredHistory)) {
  $filteredHistory = $history;
}

$metrics = [
  'total_return' => 0,
  'max_drawdown' => 0,
  'win_rate' => 0,
  'sharpe_ratio' => 0,
  'final_equity' => $capital,
  'benchmark_equity' => $capital,
  'trades' => [],
  'status' => 'No data',
];

if (!empty($filteredHistory)) {
  $closePrices = array_map(function ($row) {
    return (float)($row['close'] ?? 0);
  }, $filteredHistory);

  $smaFast = TechnicalAnalysis::calculateSMA($closePrices, $fastPeriod);
  $smaSlow = TechnicalAnalysis::calculateSMA($closePrices, $slowPeriod);
  $cash = $capital;
  $shares = 0;
  $entryPrice = 0;
  $entryDate = null;
  $equityCurve = [];
  $trades = [];
  $benchmarkValue = $capital;
  $benchmarkShares = 0;
  $benchmarkEntryPrice = 0;

  $benchmarkShares = (int)floor($capital / max($closePrices[0] ?? 1, 1));
  $benchmarkValue = $benchmarkShares * ($closePrices[count($closePrices) - 1] ?? $closePrices[0] ?? 1);

  for ($i = 0; $i < count($filteredHistory); $i++) {
    $close = $closePrices[$i];
    if ($close <= 0) continue;

    $currentFast = $smaFast[$i - $fastPeriod + 1] ?? null;
    $currentSlow = $smaSlow[$i - $slowPeriod + 1] ?? null;
    $prevFast = $smaFast[$i - $fastPeriod] ?? $currentFast;
    $prevSlow = $smaSlow[$i - $slowPeriod] ?? $currentSlow;

    $windowPrices = array_slice($closePrices, max(0, $i - $rsiPeriod + 1), min($rsiPeriod, $i + 1));
    $rsiValue = !empty($windowPrices) ? TechnicalAnalysis::calculateRSI($windowPrices, min($rsiPeriod, count($windowPrices))) : 50;

    $buySignal = $currentFast !== null && $currentSlow !== null && $prevFast !== null && $prevSlow !== null
      && $currentFast > $currentSlow && $prevFast <= $prevSlow && $rsiValue <= $oversold;
    $sellSignal = $currentFast !== null && $currentSlow !== null && $prevFast !== null && $prevSlow !== null
      && $currentFast < $currentSlow && $prevFast >= $prevSlow && $rsiValue >= $overbought;

    if ($shares === 0 && $buySignal) {
      $shares = (int)floor($cash / $close);
      if ($shares > 0) {
        $cash -= $shares * $close;
        $entryPrice = $close;
        $entryDate = $filteredHistory[$i]['time'];
      }
    } elseif ($shares > 0 && ($sellSignal || $i === count($filteredHistory) - 1)) {
      $exitPrice = $close;
      $pnlPct = $entryPrice > 0 ? (($exitPrice / $entryPrice) - 1) * 100 : 0;
      $cash += $shares * $exitPrice;
      $trades[] = [
        'type' => 'LONG',
        'entry_date' => $entryDate,
        'exit_date' => $filteredHistory[$i]['time'],
        'entry_price' => round($entryPrice, 2),
        'exit_price' => round($exitPrice, 2),
        'pnl_pct' => round($pnlPct, 2),
      ];
      $shares = 0;
      $entryPrice = 0;
      $entryDate = null;
    }

    $portfolioValue = $cash + ($shares * $close);
    $equityCurve[] = $portfolioValue;
  }

  $finalEquity = $equityCurve[count($equityCurve) - 1] ?? $capital;
  $peak = $capital;
  $maxDrawdown = 0;
  foreach ($equityCurve as $value) {
    if ($value > $peak) {
      $peak = $value;
    }
    $drawdown = $peak > 0 ? (($peak - $value) / $peak) * 100 : 0;
    if ($drawdown > $maxDrawdown) {
      $maxDrawdown = $drawdown;
    }
  }

  $winningTrades = count(array_filter($trades, function ($trade) {
    return $trade['pnl_pct'] > 0;
  }));
  $winRate = count($trades) > 0 ? ($winningTrades / count($trades)) * 100 : 0;

  $dailyReturns = [];
  for ($i = 1; $i < count($equityCurve); $i++) {
    $prev = $equityCurve[$i - 1] ?? $capital;
    $curr = $equityCurve[$i] ?? $capital;
    $dailyReturns[] = $prev > 0 ? (($curr / $prev) - 1) : 0;
  }
  $avgReturn = count($dailyReturns) > 0 ? array_sum($dailyReturns) / count($dailyReturns) : 0;
  $stdReturn = count($dailyReturns) > 0 ? sqrt(array_sum(array_map(function ($value) use ($avgReturn) {
    return pow($value - $avgReturn, 2);
  }, $dailyReturns)) / count($dailyReturns)) : 0;
  $sharpe = $stdReturn > 0 ? ($avgReturn / $stdReturn) * sqrt(252) : 0;

  $buyHoldValue = $capital;
  if (count($closePrices) > 0 && $closePrices[0] > 0) {
    $buyHoldValue = $capital * ($closePrices[count($closePrices) - 1] / $closePrices[0]);
  }

  $metrics = [
    'total_return' => round((($finalEquity / $capital) - 1) * 100, 2),
    'max_drawdown' => round($maxDrawdown, 2),
    'win_rate' => round($winRate, 2),
    'sharpe_ratio' => round($sharpe, 2),
    'final_equity' => round($finalEquity, 2),
    'benchmark_equity' => round($buyHoldValue, 2),
    'trades' => $trades,
    'status' => 'Simulation complete',
  ];
}

if ($action === 'save') {
  $_SESSION['backtest_instrument'] = $selectedInstrument;
  $_SESSION['backtest_timeframe'] = $selectedTimeframe;
  $_SESSION['backtest_start_date'] = $startDate;
  $_SESSION['backtest_end_date'] = $endDate;
  $saveMessage = 'Strategy saved for ' . $selectedInstrument . ' using ' . $selectedTimeframe . ' timeframe.';
}
?>

<div class="flex flex-col w-full gap-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-primary">Backtesting Engine</h1>
      <p class="text-xs text-text-muted mt-1">Simulate and optimize your trading strategies with historical data from the Indonesian market.</p>
    </div>
    <div class="flex gap-3">
      <button type="button" class="btn btn-outline text-xs" id="save-strategy-btn" data-action="save">
        <span class="material-symbols-outlined text-[16px]">save</span> Save Strategy
      </button>
      <button type="button" class="btn btn-bullish text-xs" id="run-simulation-btn" data-action="run">
        <span class="material-symbols-outlined text-[16px]">play_arrow</span> Run Simulation
      </button>
    </div>
  </div>

  <?php if (!empty($saveMessage)): ?>
    <div class="rounded border border-bullish/30 bg-surface-container-low p-3 text-xs text-bullish">
      <?php echo htmlspecialchars($saveMessage); ?>
    </div>
  <?php endif; ?>

  <div class="flex flex-col lg:flex-row gap-6">
    <div class="w-full lg:w-80 flex flex-col gap-4 shrink-0">
      <div class="bg-surface-container-low border border-border-subtle rounded-lg flex flex-col overflow-hidden">
        <div class="p-3 border-b border-border-subtle bg-surface-container-high/30">
          <h2 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-2">
            <span class="material-symbols-outlined text-bullish text-[16px]">account_tree</span>
            Strategy Builder
          </h2>
        </div>
        <div class="p-4 flex flex-col gap-4">
          <form method="post" class="flex flex-col gap-4">
            <input type="hidden" name="action" value="run" />
            <div class="form-group">
              <label class="form-label">Instrument</label>
              <input list="instrumentList" name="instrument" id="instrument-input" class="form-control" value="<?php echo htmlspecialchars($selectedInstrument); ?>" placeholder="Type symbol or choose from list" autocomplete="off" />
              <datalist id="instrumentList">
                <?php foreach ($availableInstruments as $value => $label): ?>
                  <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
              </datalist>
              <p class="text-[10px] text-text-muted mt-2">Masukkan nama saham JKSE/ IHSG pilihan Anda, misal BBCA atau TLKM.</p>
            </div>

            <div class="form-group">
              <label class="form-label">Timeframe</label>
              <div class="grid grid-cols-4 gap-1.5">
                <?php foreach ($timeframeConfig as $value => $config): ?>
                  <button type="button" data-value="<?php echo htmlspecialchars($value); ?>" class="timeframe-btn btn <?php echo $selectedTimeframe === $value ? 'btn-primary' : 'btn-outline'; ?> text-xs py-1 px-0 font-mono"><?php echo htmlspecialchars($value); ?></button>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="timeframe" id="timeframe-input" value="<?php echo htmlspecialchars($selectedTimeframe); ?>" />
            </div>

            <div class="form-group">
              <label class="form-label">Date Range</label>
              <div class="flex gap-2">
                <input type="date" name="start_date" class="form-control text-xs" value="<?php echo htmlspecialchars($startDate); ?>" />
                <input type="date" name="end_date" class="form-control text-xs" value="<?php echo htmlspecialchars($endDate); ?>" />
              </div>
            </div>

            <hr class="border-border-subtle"/>

            <div class="flex flex-col gap-2.5">
              <div class="flex items-center justify-between">
                <label class="form-label">Active Indicators</label>
              </div>

              <div class="p-3 bg-surface-container-lowest border border-border-subtle rounded">
                <div class="flex items-center justify-between mb-2">
                  <span class="font-mono text-xs font-semibold text-primary">MA Cross</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="text-[9px] font-bold text-text-muted uppercase">Fast</label>
                    <input class="form-control text-xs py-1" name="fast_period" type="number" min="2" value="<?php echo (int)$fastPeriod; ?>" />
                  </div>
                  <div>
                    <label class="text-[9px] font-bold text-text-muted uppercase">Slow</label>
                    <input class="form-control text-xs py-1" name="slow_period" type="number" min="2" value="<?php echo (int)$slowPeriod; ?>" />
                  </div>
                </div>
              </div>

              <div class="p-3 bg-surface-container-lowest border border-border-subtle rounded">
                <div class="flex items-center justify-between mb-2">
                  <span class="font-mono text-xs font-semibold text-primary">RSI</span>
                </div>
                <div class="grid grid-cols-3 gap-1.5">
                  <div>
                    <label class="text-[9px] font-bold text-text-muted uppercase">Period</label>
                    <input class="form-control text-xs py-1" name="rsi_period" type="number" min="2" value="<?php echo (int)$rsiPeriod; ?>" />
                  </div>
                  <div>
                    <label class="text-[9px] font-bold text-text-muted uppercase">Overbought</label>
                    <input class="form-control text-xs py-1" name="overbought" type="number" min="50" value="<?php echo htmlspecialchars((string)$overbought); ?>" />
                  </div>
                  <div>
                    <label class="text-[9px] font-bold text-text-muted uppercase">Oversold</label>
                    <input class="form-control text-xs py-1" name="oversold" type="number" max="50" value="<?php echo htmlspecialchars((string)$oversold); ?>" />
                  </div>
                </div>
              </div>
            </div>

            <hr class="border-border-subtle"/>

            <div class="form-group">
              <label class="form-label">Initial Capital (IDR)</label>
              <input type="text" name="capital" class="form-control text-xs" value="<?php echo number_format($capital, 0, ',', '.'); ?>" />
            </div>

            <button type="submit" class="btn btn-bullish text-xs">
              <span class="material-symbols-outlined text-[16px]">play_arrow</span> Run Simulation
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="flex-1 flex flex-col gap-6">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col gap-1">
          <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Total Return</span>
          <div class="text-xl font-bold <?php echo $metrics['total_return'] >= 0 ? 'text-bullish' : 'text-bearish'; ?> tracking-tight mt-1"><?php echo ($metrics['total_return'] >= 0 ? '+' : '') . number_format($metrics['total_return'], 2, '.', ',') . '%'; ?></div>
          <span class="font-mono text-text-muted text-xs">Rp <?php echo number_format($metrics['final_equity'], 0, ',', '.'); ?></span>
        </div>
        <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col gap-1">
          <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Max Drawdown</span>
          <div class="text-xl font-bold text-bearish tracking-tight mt-1">-<?php echo number_format($metrics['max_drawdown'], 2, '.', ','); ?>%</div>
        </div>
        <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col gap-1">
          <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Win Rate</span>
          <div class="text-xl font-bold text-primary tracking-tight mt-1"><?php echo number_format($metrics['win_rate'], 2, '.', ','); ?>%</div>
        </div>
        <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col gap-1">
          <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Sharpe Ratio</span>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="text-xl font-bold text-primary tracking-tight"><?php echo number_format($metrics['sharpe_ratio'], 2, '.', ','); ?></span>
            <span class="badge <?php echo $metrics['sharpe_ratio'] >= 1 ? 'badge-buy' : 'badge-hold'; ?>"><?php echo $metrics['sharpe_ratio'] >= 1 ? 'GOOD' : 'WATCH'; ?></span>
          </div>
        </div>
      </div>

      <div class="bg-surface-container-low border border-border-subtle rounded-lg flex flex-col min-h-[300px]">
        <div class="p-3 border-b border-border-subtle flex justify-between items-center bg-surface-container-high/30">
          <h3 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-2">
            <span class="material-symbols-outlined text-text-muted text-[16px]">monitoring</span>
            Equity Curve
          </h3>
          <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-wider">
            <div class="flex items-center gap-1.5 text-bullish">
              <div class="w-2 h-2 rounded-full bg-bullish"></div> Strategy
            </div>
            <div class="flex items-center gap-1.5 text-text-muted">
              <div class="w-2 h-2 rounded-full bg-text-muted"></div> Buy & Hold
            </div>
          </div>
        </div>
        <div class="flex-1 p-4 relative">
          <div class="rounded border border-border-subtle p-3 text-xs text-text-muted">
            <strong><?php echo htmlspecialchars($selectedInstrument); ?></strong> • <?php echo htmlspecialchars($selectedTimeframe); ?> • <?php echo htmlspecialchars($startDate); ?> to <?php echo htmlspecialchars($endDate); ?>
          </div>
          <div class="mt-3 text-sm text-text-muted">
            Strategy equity: Rp <?php echo number_format($metrics['final_equity'], 0, ',', '.'); ?><br />
            Buy & hold equity: Rp <?php echo number_format($metrics['benchmark_equity'], 0, ',', '.'); ?>
          </div>
        </div>
      </div>

      <div class="bg-surface-container-low border border-border-subtle rounded-lg overflow-hidden flex flex-col">
        <div class="p-3 border-b border-border-subtle flex justify-between items-center bg-surface-container-high/30">
          <h3 class="text-xs font-bold text-primary uppercase tracking-wider">Simulation Trades</h3>
          <span class="text-text-muted text-[10px] font-bold tracking-wider"><?php echo count($metrics['trades']); ?> trades</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface-container-high/20 border-b border-border-subtle text-[10px] font-bold text-text-muted uppercase">
                <th class="py-2 px-4 w-12">#</th>
                <th class="py-2 px-4">Type</th>
                <th class="py-2 px-4">Entry Date</th>
                <th class="py-2 px-4 text-right">Entry Price</th>
                <th class="py-2 px-4">Exit Date</th>
                <th class="py-2 px-4 text-right">Exit Price</th>
                <th class="py-2 px-4 text-right">P/L %</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle font-mono text-xs">
              <?php if (empty($metrics['trades'])): ?>
                <tr>
                  <td colspan="7" class="px-4 py-4 text-center text-text-muted">Belum ada trade yang terbentuk. Coba ubah parameter atau rentang data.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($metrics['trades'] as $index => $trade): ?>
                  <tr class="hover:bg-surface-container-high transition-colors">
                    <td class="px-4 py-2.5 text-text-muted"><?php echo $index + 1; ?></td>
                    <td class="px-4 py-2.5 text-bullish font-bold"><?php echo htmlspecialchars($trade['type']); ?></td>
                    <td class="px-4 py-2.5 text-primary"><?php echo htmlspecialchars($trade['entry_date'] ?? '-'); ?></td>
                    <td class="px-4 py-2.5 text-right"><?php echo number_format($trade['entry_price'] ?? 0, 2, '.', ','); ?></td>
                    <td class="px-4 py-2.5 text-primary"><?php echo htmlspecialchars($trade['exit_date'] ?? '-'); ?></td>
                    <td class="px-4 py-2.5 text-right"><?php echo number_format($trade['exit_price'] ?? 0, 2, '.', ','); ?></td>
                    <td class="px-4 py-2.5 text-right font-bold <?php echo ($trade['pnl_pct'] ?? 0) >= 0 ? 'text-bullish' : 'text-bearish'; ?>"><?php echo ($trade['pnl_pct'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($trade['pnl_pct'] ?? 0, 2, '.', ','); ?>%</td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const backtestForm = document.getElementById('backtest-form');
  const backtestAction = document.getElementById('backtest-action');
  const timeframeInput = document.getElementById('timeframe-input');

  document.querySelectorAll('.timeframe-btn').forEach(function (button) {
    button.addEventListener('click', function () {
      timeframeInput.value = this.dataset.value;
      document.querySelectorAll('.timeframe-btn').forEach(function (btn) {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline');
      });
      this.classList.remove('btn-outline');
      this.classList.add('btn-primary');
    });
  });

  document.getElementById('save-strategy-btn').addEventListener('click', function () {
    backtestAction.value = 'save';
    backtestForm.submit();
  });

  document.getElementById('run-simulation-btn').addEventListener('click', function () {
    backtestAction.value = 'run';
    backtestForm.submit();
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
