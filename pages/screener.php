<?php
$pageTitle = 'Stock Screener';
$activePage = 'screener';

require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';
require_once __DIR__ . '/../config/database.php';

$screenedResults = [];

function getIhsgSymbols() {
    $fallbackSymbols = ['BBCA', 'TLKM', 'GOTO', 'ASII', 'BBNI', 'BMRI', 'ADRO', 'ARTO', 'CUAN', 'BRPT', 'UNVR', 'AMMN'];
    $db = getDBConnection();
    if (!$db) {
        return $fallbackSymbols;
    }

    try {
        $stmt = $db->query("SELECT code FROM idx_stocks ORDER BY code ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return $fallbackSymbols;
        }

        $symbols = [];
        foreach ($rows as $row) {
            $code = strtoupper(trim($row['code'] ?? ''));
            if ($code !== '') {
                $symbols[] = $code;
            }
        }

        return !empty($symbols) ? array_values(array_unique($symbols)) : $fallbackSymbols;
    } catch (PDOException $e) {
        return $fallbackSymbols;
    }
}

$runScreener = isset($_GET['run_screener']) && $_GET['run_screener'] === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

function buildPageUrl($pageNumber) {
    $params = $_GET;
    $params['page'] = $pageNumber;
    if (!isset($params['run_screener'])) {
        $params['run_screener'] = '1';
    }
    return BASE_URL . 'pages/screener.php?' . http_build_query($params);
}

function getDrawdownPercent($history, $price) {
    if (empty($history) || empty($price)) {
        return 0;
    }

    $high = 0;
    foreach ($history as $bar) {
        $close = (float)($bar['close'] ?? 0);
        if ($close > $high) {
            $high = $close;
        }
    }

    if ($high <= 0) {
        return 0;
    }

    return max(0, (($high - $price) / $high) * 100);
}

$filters = [
    'rsi' => isset($_GET['rsi']) ? trim($_GET['rsi']) : '',
    'macd' => isset($_GET['macd']) ? trim($_GET['macd']) : '',
    'momentum' => isset($_GET['momentum']) ? trim($_GET['momentum']) : '',
    'volume' => isset($_GET['volume']) ? trim($_GET['volume']) : '',
    'drawdown' => isset($_GET['drawdown']) ? trim($_GET['drawdown']) : '',
    'signal' => isset($_GET['signal']) ? trim($_GET['signal']) : ''
];

$screenerSymbols = $runScreener ? getIhsgSymbols() : [];
$totalSymbols = count($screenerSymbols);
$matchesAll = [];

foreach ($screenerSymbols as $sym) {
    $q = StockData::getQuote($sym);
    $sig = SignalGenerator::generateSignal($q);
    $changePercent = (float)($q['changePercent'] ?? 0);
    $rsi = (float)($sig['rsi'] ?? 50);
    $macdTrend = strtoupper((string)($sig['macd']['trend'] ?? 'NEUTRAL'));
    $volume = (float)($q['volume'] ?? 0);
    $drawdown = getDrawdownPercent($q['history'] ?? [], $q['price'] ?? 0);
    $signal = (string)($sig['signal'] ?? 'HOLD');

    $matches = true;

    if ($filters['rsi'] !== '') {
        if ($filters['rsi'] === 'oversold' && !($rsi < 30)) $matches = false;
        elseif ($filters['rsi'] === 'neutral' && !($rsi >= 30 && $rsi <= 70)) $matches = false;
        elseif ($filters['rsi'] === 'bullish' && !($rsi > 50)) $matches = false;
        elseif ($filters['rsi'] === 'overbought' && !($rsi > 70)) $matches = false;
    }

    if ($matches && $filters['macd'] !== '') {
        if ($filters['macd'] === 'bullish' && strpos($macdTrend, 'BULLISH') === false) $matches = false;
        elseif ($filters['macd'] === 'bearish' && strpos($macdTrend, 'BEARISH') === false) $matches = false;
        elseif ($filters['macd'] === 'neutral' && strpos($macdTrend, 'BULLISH') !== false) $matches = false;
    }

    if ($matches && $filters['momentum'] !== '') {
        if ($filters['momentum'] === 'up2' && !($changePercent > 2)) $matches = false;
        elseif ($filters['momentum'] === 'up5' && !($changePercent > 5)) $matches = false;
        elseif ($filters['momentum'] === 'up10' && !($changePercent > 10)) $matches = false;
        elseif ($filters['momentum'] === 'down2' && !($changePercent < -2)) $matches = false;
    }

    if ($matches && $filters['volume'] !== '') {
        if ($filters['volume'] === '10m' && !($volume >= 10000000)) $matches = false;
        elseif ($filters['volume'] === '50m' && !($volume >= 50000000)) $matches = false;
        elseif ($filters['volume'] === '100m' && !($volume >= 100000000)) $matches = false;
    }

    if ($matches && $filters['drawdown'] !== '') {
        if ($filters['drawdown'] === '5' && !($drawdown <= 5)) $matches = false;
        elseif ($filters['drawdown'] === '10' && !($drawdown <= 10)) $matches = false;
        elseif ($filters['drawdown'] === '15' && !($drawdown <= 15)) $matches = false;
    }

    if ($matches && $filters['signal'] !== '') {
        if ($filters['signal'] === 'buy' && strpos($signal, 'BUY') === false) $matches = false;
        elseif ($filters['signal'] === 'sell' && strpos($signal, 'SELL') === false) $matches = false;
        elseif ($filters['signal'] === 'hold' && $signal !== 'HOLD') $matches = false;
    }

    if ($matches) {
        $matchesAll[] = [
            'quote' => $q,
            'signal' => $sig,
            'drawdown' => $drawdown,
            'changePercent' => $changePercent,
            'rsi' => $rsi,
            'macdTrend' => $macdTrend,
            'volume' => $volume,
            'signalLabel' => $signal
        ];
    }
}

$totalMatches = count($matchesAll);
$pageCount = max(1, (int)ceil($totalMatches / $perPage));
$offset = ($page - 1) * $perPage;
$screenedResults = array_slice($matchesAll, $offset, $perPage);
$startItem = $totalMatches > 0 ? $offset + 1 : 0;
$endItem = min($offset + count($screenedResults), $totalMatches);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col w-full gap-4">
  <!-- Header Title -->
  <div class="flex items-end justify-between w-full">
    <div>
      <h1 class="text-xl font-bold text-primary mb-1">Screener</h1>
      <p class="text-xs text-text-muted">Cari saham dengan indikator trading yang membantu keputusan entry dan exit.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?php echo BASE_URL; ?>pages/screener.php" class="btn btn-outline flex items-center gap-1.5 text-xs py-1.5">
        <span class="material-symbols-outlined text-[16px]">clear_all</span>
        <span>Clear All</span>
      </a>
      <button type="submit" form="screenerForm" class="btn btn-bullish flex items-center gap-1.5 text-xs py-1.5">
        <span class="material-symbols-outlined text-[16px]">play_arrow</span>
        <span>Run Screener</span>
      </button>
    </div>
  </div>

  <!-- Active Filters Section -->
  <form id="screenerForm" method="get" action="<?php echo BASE_URL; ?>pages/screener.php" class="w-full bg-surface-container-low rounded-lg border border-border-subtle p-4 flex flex-col gap-4">
    <div class="flex items-center justify-between border-b border-border-subtle pb-3">
      <div class="flex items-center gap-2">
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">Active Filters</h2>
        <span class="px-1.5 py-0.5 rounded bg-primary/10 text-primary text-[10px] font-bold border border-primary/20">
          <?php
            $activeFilterCount = 0;
            foreach ($filters as $value) {
              if ($value !== '') {
                $activeFilterCount++;
              }
            }
            echo $activeFilterCount . ' ' . ($activeFilterCount === 1 ? 'APPLIED' : 'APPLIED');
          ?>
        </span>
      </div>
    </div>

    <input type="hidden" name="run_screener" value="1" />
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
      <div class="flex flex-col gap-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">RSI (14)</label>
        <select name="rsi" class="px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary focus:outline-none focus:border-primary">
          <option value="">Any</option>
          <option value="oversold" <?php echo $filters['rsi'] === 'oversold' ? 'selected' : ''; ?>>&lt; 30 (Oversold)</option>
          <option value="neutral" <?php echo $filters['rsi'] === 'neutral' ? 'selected' : ''; ?>>30 - 70 (Neutral)</option>
          <option value="bullish" <?php echo $filters['rsi'] === 'bullish' ? 'selected' : ''; ?>>&gt; 50 (Bullish)</option>
          <option value="overbought" <?php echo $filters['rsi'] === 'overbought' ? 'selected' : ''; ?>>&gt; 70 (Overbought)</option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">MACD</label>
        <select name="macd" class="px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary focus:outline-none focus:border-primary">
          <option value="">Any</option>
          <option value="bullish" <?php echo $filters['macd'] === 'bullish' ? 'selected' : ''; ?>>Bullish</option>
          <option value="bearish" <?php echo $filters['macd'] === 'bearish' ? 'selected' : ''; ?>>Bearish</option>
          <option value="neutral" <?php echo $filters['macd'] === 'neutral' ? 'selected' : ''; ?>>Neutral</option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">MOMENTUM</label>
        <select name="momentum" class="px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary focus:outline-none focus:border-primary">
          <option value="">Any</option>
          <option value="up2" <?php echo $filters['momentum'] === 'up2' ? 'selected' : ''; ?>>&gt; +2%</option>
          <option value="up5" <?php echo $filters['momentum'] === 'up5' ? 'selected' : ''; ?>>&gt; +5%</option>
          <option value="up10" <?php echo $filters['momentum'] === 'up10' ? 'selected' : ''; ?>>&gt; +10%</option>
          <option value="down2" <?php echo $filters['momentum'] === 'down2' ? 'selected' : ''; ?>>&lt; -2%</option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">VOLUME</label>
        <select name="volume" class="px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary focus:outline-none focus:border-primary">
          <option value="">Any</option>
          <option value="10m" <?php echo $filters['volume'] === '10m' ? 'selected' : ''; ?>>&ge; 10M</option>
          <option value="50m" <?php echo $filters['volume'] === '50m' ? 'selected' : ''; ?>>&ge; 50M</option>
          <option value="100m" <?php echo $filters['volume'] === '100m' ? 'selected' : ''; ?>>&ge; 100M</option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">DRAWDOWN</label>
        <select name="drawdown" class="px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary focus:outline-none focus:border-primary">
          <option value="">Any</option>
          <option value="5" <?php echo $filters['drawdown'] === '5' ? 'selected' : ''; ?>>&le; 5%</option>
          <option value="10" <?php echo $filters['drawdown'] === '10' ? 'selected' : ''; ?>>&le; 10%</option>
          <option value="15" <?php echo $filters['drawdown'] === '15' ? 'selected' : ''; ?>>&le; 15%</option>
        </select>
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">SIGNAL</label>
        <select name="signal" class="px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary focus:outline-none focus:border-primary">
          <option value="">Any</option>
          <option value="buy" <?php echo $filters['signal'] === 'buy' ? 'selected' : ''; ?>>BUY / STRONG BUY</option>
          <option value="hold" <?php echo $filters['signal'] === 'hold' ? 'selected' : ''; ?>>HOLD</option>
          <option value="sell" <?php echo $filters['signal'] === 'sell' ? 'selected' : ''; ?>>SELL / STRONG SELL</option>
        </select>
      </div>
    </div>
  </form>

  <!-- Screening Results Table -->
  <div class="w-full bg-surface-container-low rounded-lg border border-border-subtle overflow-hidden flex flex-col">
    <div class="flex items-center justify-between px-4 py-3 border-b border-border-subtle bg-surface-container-high/30">
      <div class="flex items-center gap-3">
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">Screening Results</h2>
        <span class="text-xs text-text-muted"><?php echo $runScreener ? $totalMatches . ' Matches Found (IHSG universe)' : 'No results yet. Run screener to scan IHSG universe.'; ?></span>
      </div>
      <div class="flex items-center gap-2">
        <div class="relative">
          <input class="w-48 pl-3 pr-2.5 py-1 bg-surface-container-lowest border border-border-subtle rounded text-xs text-primary placeholder:text-text-muted" placeholder="Search ticker..." type="text"/>
        </div>
        <button class="p-1 rounded text-text-muted hover:bg-surface-container-high hover:text-white transition-colors" title="Export CSV">
          <span class="material-symbols-outlined text-[18px]">download</span>
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead>
          <tr class="border-b border-border-subtle bg-surface-container-high/20 text-[10px] font-bold text-text-muted uppercase">
            <th class="px-4 py-2.5">TICKER</th>
            <th class="px-4 py-2.5">COMPANY</th>
            <th class="px-4 py-2.5 text-right">LAST PRICE</th>
            <th class="px-4 py-2.5 text-right">% CHANGE</th>
            <th class="px-4 py-2.5 text-right">VOLUME</th>
            <th class="px-4 py-2.5 text-right">RSI</th>
            <th class="px-4 py-2.5 text-right">DRAWDOWN</th>
            <th class="px-4 py-2.5 text-center">SIGNAL</th>
            <th class="px-4 py-2.5 text-center">ACTIONS</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-subtle text-xs font-mono">
          <?php if (!$runScreener): ?>
            <tr>
              <td class="px-4 py-10 text-center text-text-muted" colspan="9">Belum ada hasil. Pilih filter lalu klik Run Screener untuk memindai seluruh saham IHSG.</td>
            </tr>
          <?php elseif (empty($screenedResults)): ?>
            <tr>
              <td class="px-4 py-10 text-center text-text-muted" colspan="9">Tidak ada saham yang cocok dengan filter saat ini.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($screenedResults as $res): 
              $q = $res['quote'];
              $sig = $res['signal'];
            ?>
              <tr class="hover:bg-surface-container-high transition-colors group cursor-pointer" onclick="window.location='<?php echo BASE_URL; ?>pages/analysis.php?stock=<?php echo $q['symbol']; ?>'">
                <td class="px-4 py-3 font-bold text-primary"><?php echo $q['symbol']; ?></td>
                <td class="px-4 py-3 font-sans text-text-muted"><?php echo htmlspecialchars($q['shortName']); ?></td>
                <td class="px-4 py-3 text-right"><?php echo formatRupiah($q['price'], false); ?></td>
                <td class="px-4 py-3 text-right <?php echo getChangeColorClass($q['change']); ?>"><?php echo formatPercent($q['changePercent']); ?></td>
                <td class="px-4 py-3 text-right text-text-muted"><?php echo number_format($q['volume'] / 1000000, 1); ?>M</td>
                <td class="px-4 py-3 text-right text-warning"><?php echo number_format($res['rsi'], 1); ?></td>
                <td class="px-4 py-3 text-right text-text-muted"><?php echo number_format($res['drawdown'], 1); ?>%</td>
                <td class="px-4 py-3 text-center"><?php echo getSignalBadgeHTML($sig['signal']); ?></td>
                <td class="px-4 py-3 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[16px] text-text-muted hover:text-primary">bookmark_add</span>
                    <span class="material-symbols-outlined text-[16px] text-text-muted hover:text-primary">insights</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="px-4 py-3 border-t border-border-subtle flex items-center justify-between bg-surface-container-high/10 text-xs">
      <span class="text-text-muted">Showing <?php echo $startItem; ?> to <?php echo $endItem; ?> of <?php echo $totalMatches; ?> entries</span>
      <div class="flex gap-1">
        <a href="<?php echo $page > 1 ? buildPageUrl($page - 1) : '#'; ?>" class="btn btn-outline py-1 px-2.5 text-xs <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">Prev</a>
        <?php for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++): ?>
          <a href="<?php echo buildPageUrl($pageNum); ?>" class="btn <?php echo $pageNum === $page ? 'btn-primary' : 'btn-outline'; ?> py-1 px-2.5 text-xs"><?php echo $pageNum; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $page < $pageCount ? buildPageUrl($page + 1) : '#'; ?>" class="btn btn-outline py-1 px-2.5 text-xs <?php echo $page >= $pageCount ? 'opacity-50 pointer-events-none' : ''; ?>">Next</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
