<?php
session_start();

$pageTitle = 'Trading Signals';
$activePage = 'signals';

require_once __DIR__ . '/../classes/StockData.php';
require_once __DIR__ . '/../classes/SignalGenerator.php';
require_once __DIR__ . '/../classes/AiRecommendationService.php';

$defaultWatchlistSymbols = ['BBCA', 'BBNI', 'ADRO', 'TLKM', 'BMRI'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $watchlistAction = isset($_POST['watchlist_action']) ? $_POST['watchlist_action'] : '';
  $watchlistSymbol = strtoupper(trim((string)($_POST['watchlist_symbol'] ?? '')));

  if (!isset($_SESSION['watchlist_symbols']) || empty($_SESSION['watchlist_symbols'])) {
    $_SESSION['watchlist_symbols'] = $defaultWatchlistSymbols;
  }

  if ($watchlistAction === 'add' && $watchlistSymbol !== '') {
    $symbols = array_values(array_filter($_SESSION['watchlist_symbols'], function ($value) {
      return trim((string)$value) !== '';
    }));
    if (!in_array($watchlistSymbol, array_map('strtoupper', $symbols), true)) {
      $symbols[] = $watchlistSymbol;
    }
    $_SESSION['watchlist_symbols'] = $symbols;
  } elseif ($watchlistAction === 'remove' && $watchlistSymbol !== '') {
    $_SESSION['watchlist_symbols'] = array_values(array_filter($_SESSION['watchlist_symbols'], function ($value) use ($watchlistSymbol) {
      return strtoupper(trim((string)$value)) !== $watchlistSymbol;
    }));
  }
}

if (!isset($_SESSION['watchlist_symbols']) || empty($_SESSION['watchlist_symbols'])) {
  $_SESSION['watchlist_symbols'] = $defaultWatchlistSymbols;
}

$watchlistSymbols = array_values(array_filter(array_map('strtoupper', $_SESSION['watchlist_symbols']), function ($value) {
  return trim((string)$value) !== '';
}));
$signalCards = [];

foreach ($watchlistSymbols as $symbol) {
  $quote = StockData::getQuote($symbol);
  $signal = AiRecommendationService::buildRecommendation($symbol, $quote);
  $signalCards[] = [
    'symbol' => $symbol,
    'quote' => $quote,
    'signal' => $signal,
  ];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col w-full gap-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-primary mb-1">Trading Signals</h1>
      <p class="text-xs text-text-muted">Watchlist saham yang dipilih akan muncul di sini dan harga-nya diperbarui secara realtime.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?php echo BASE_URL; ?>pages/signals.php" class="btn btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">refresh</span> Refresh Signals
      </a>
    </div>
  </div>

  <div class="bg-surface-container-low rounded-lg border border-border-subtle p-4 flex flex-col md:flex-row md:items-end gap-3">
    <form method="post" class="flex flex-1 flex-col md:flex-row gap-2">
      <input type="hidden" name="watchlist_action" value="add">
      <div class="flex-1">
        <label class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Tambah saham ke watchlist</label>
        <input type="text" name="watchlist_symbol" class="w-full mt-1 px-3 py-2 bg-surface-container-lowest border border-border-subtle rounded text-sm text-primary placeholder:text-text-muted" placeholder="Contoh: BBRI" />
      </div>
      <button type="submit" class="btn btn-bullish text-xs self-end">Add to Watchlist</button>
    </form>
  </div>

  <?php if (empty($watchlistSymbols)): ?>
    <div class="bg-surface-container-low rounded-lg border border-border-subtle p-6 text-center text-sm text-text-muted">
      Watchlist masih kosong. Tambahkan saham untuk memulai monitoring.
    </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="signalsGrid">
    <?php foreach ($signalCards as $card): 
      $quote = $card['quote'];
      $signal = $card['signal'];
      $signalClass = strpos($signal['signal'], 'BUY') !== false ? 'border-bullish/30' : (strpos($signal['signal'], 'SELL') !== false ? 'border-bearish/30' : 'border-warning/30');
      $badgeClass = strpos($signal['signal'], 'BUY') !== false ? 'badge-buy' : (strpos($signal['signal'], 'SELL') !== false ? 'badge-sell' : 'badge-hold');
    ?>
      <div class="bg-surface-container-low border <?php echo $signalClass; ?> rounded-lg p-5 flex flex-col justify-between hover:border-primary transition-colors shadow-sm signal-card" data-symbol="<?php echo htmlspecialchars($card['symbol']); ?>">
        <div>
          <div class="flex justify-between items-start mb-3">
            <div>
              <span class="font-mono text-lg font-bold text-primary"><?php echo htmlspecialchars($card['symbol']); ?>.JK</span>
              <div class="text-xs text-text-muted"><?php echo htmlspecialchars($quote['shortName']); ?></div>
            </div>
            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($signal['signal']); ?></span>
          </div>
          <div class="space-y-1.5 text-xs font-mono my-4">
            <div class="flex justify-between"><span class="text-text-muted">Price:</span><span class="text-primary font-bold realtime-price" data-symbol="<?php echo htmlspecialchars($card['symbol']); ?>"><?php echo formatRupiah($quote['price'], false); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">Change:</span><span class="<?php echo getChangeColorClass($quote['change']); ?> font-bold"><?php echo ($quote['change'] >= 0 ? '+' : '') . number_format($quote['change'], 2); ?> (<?php echo formatPercent($quote['changePercent']); ?>)</span></div>
            <div class="flex justify-between"><span class="text-text-muted">Confidence:</span><span class="text-bullish font-bold"><?php echo $signal['confidence']; ?>%</span></div>
            <div class="flex justify-between"><span class="text-text-muted">Entry:</span><span><?php echo formatRupiah($signal['entry_min'], false); ?> - <?php echo formatRupiah($signal['entry_max'], false); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">Target 1:</span><span class="text-primary"><?php echo formatRupiah($signal['target_1'], false); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">Stop Loss:</span><span class="text-bearish"><?php echo formatRupiah($signal['stop_loss'], false); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">S/R:</span><span><?php echo number_format((float)($signal['support_resistance']['support'] ?? 0), 0); ?> / <?php echo number_format((float)($signal['support_resistance']['resistance'] ?? 0), 0); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">Breakout:</span><span><?php echo htmlspecialchars($signal['breakout']['status'] ?? 'CONSOLIDATION'); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">Pattern:</span><span><?php echo htmlspecialchars($signal['pattern']['name'] ?? 'NONE'); ?></span></div>
            <div class="flex justify-between"><span class="text-text-muted">Volume:</span><span><?php echo htmlspecialchars($signal['volume_profile']['status'] ?? 'NORMAL'); ?></span></div>
          </div>
        </div>
        <div class="flex gap-2 mt-3">
          <a href="<?php echo BASE_URL; ?>pages/analysis.php?stock=<?php echo htmlspecialchars($card['symbol']); ?>" class="btn btn-outline text-xs flex-1 text-center">View Analysis</a>
          <form method="post" class="flex-0">
            <input type="hidden" name="watchlist_action" value="remove">
            <input type="hidden" name="watchlist_symbol" value="<?php echo htmlspecialchars($card['symbol']); ?>">
            <button type="submit" class="btn btn-outline text-xs">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const symbols = Array.from(document.querySelectorAll('.signal-card')).map(card => card.dataset.symbol);

    const updatePrices = () => {
      if (symbols.length === 0) return;
      const q = symbols.join(',');
      fetch(`<?php echo BASE_URL; ?>api/stock.php?batch=${encodeURIComponent(q)}`)
        .then(response => response.json())
        .then(data => {
          if (!data || !data.quotes) return;
          Object.keys(data.quotes).forEach(sym => {
            const priceEl = document.querySelector(`.realtime-price[data-symbol="${sym}"]`);
            if (!priceEl) return;
            const qt = data.quotes[sym];
            priceEl.textContent = new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              maximumFractionDigits: 0
            }).format(qt.price);
          });
        })
        .catch(() => {});
    };

    updatePrices();
    setInterval(updatePrices, 5000);
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
