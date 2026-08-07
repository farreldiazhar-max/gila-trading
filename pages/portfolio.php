<?php
session_start();

$pageTitle = 'Portfolio Tracker';
$activePage = 'portfolio';
require_once __DIR__ . '/../classes/PortfolioStrategyAnalyzer.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$analysisResult = null;
$manualEntries = $_SESSION['portfolio_manual_entries'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['portfolio_manual']) && $_POST['portfolio_manual'] === '1') {
    $entries = [];
    foreach (($_POST['holding_symbol'] ?? []) as $index => $symbol) {
      $symbol = trim((string)$symbol);
      if ($symbol === '') {
        continue;
      }
      $entries[] = [
        'symbol' => $symbol,
        'qty' => (float)($_POST['holding_qty'][$index] ?? 0),
        'avg_price' => (float)($_POST['holding_avg_price'][$index] ?? 0),
        'current_price' => (float)($_POST['holding_current_price'][$index] ?? 0),
      ];
    }
    $_SESSION['portfolio_manual_entries'] = $entries;
    $manualEntries = $entries;
    $analysisResult = PortfolioStrategyAnalyzer::analyzeManualPortfolio($entries);
}

if ($analysisResult === null && !empty($manualEntries)) {
  $analysisResult = PortfolioStrategyAnalyzer::analyzeManualPortfolio($manualEntries);
}

$summary = $analysisResult['summary'] ?? [
  'total_invested' => 0,
  'current_value' => 0,
  'unrealized_pnl' => 0,
  'pnl_pct' => 0,
  'position_count' => 0,
];
$holdings = $analysisResult['holdings'] ?? [];
?>

<div class="flex flex-col w-full gap-6">
  <!-- Header & Stats -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-bold text-primary mb-1">Portfolio Tracker</h1>
      <p class="text-xs text-text-muted">Masukkan posisi Anda secara manual, lalu dapatkan ringkasan dan rekomendasi AI.</p>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4">
      <div class="text-[10px] font-bold text-text-muted uppercase">TOTAL INVESTED</div>
      <div class="text-2xl font-bold text-primary font-mono mt-1">Rp <?php echo number_format($summary['total_invested'], 0, ',', '.'); ?></div>
    </div>
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4">
      <div class="text-[10px] font-bold text-text-muted uppercase">CURRENT VALUE</div>
      <div class="text-2xl font-bold text-bullish font-mono mt-1">Rp <?php echo number_format($summary['current_value'], 0, ',', '.'); ?></div>
    </div>
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4">
      <div class="text-[10px] font-bold text-text-muted uppercase">UNREALIZED P&L</div>
      <div class="text-2xl font-bold <?php echo $summary['unrealized_pnl'] >= 0 ? 'text-bullish' : 'text-bearish'; ?> font-mono mt-1"><?php echo ($summary['unrealized_pnl'] >= 0 ? '+' : '') . 'Rp ' . number_format($summary['unrealized_pnl'], 0, ',', '.'); ?> (<?php echo ($summary['pnl_pct'] >= 0 ? '+' : '') . number_format($summary['pnl_pct'], 2, '.', '') . '%'; ?>)</div>
    </div>
  </div>

  <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
      <div>
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">Manual Portfolio Input</h2>
        <p class="text-[11px] text-text-muted mt-1">Masukkan posisi Anda secara manual. Saat halaman pertama kali dibuka, data akan kosong sampai Anda mengisinya.</p>
      </div>
      <form method="post" class="w-full md:w-auto">
        <input type="hidden" name="portfolio_manual" value="1" />
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3">
          <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Symbol</div>
          <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Qty</div>
          <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Avg Price</div>
          <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Current Price</div>
        </div>
        <?php for ($i = 0; $i < 5; $i++): $entry = $manualEntries[$i] ?? []; ?>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-2">
            <input type="text" name="holding_symbol[]" value="<?php echo htmlspecialchars($entry['symbol'] ?? ''); ?>" class="form-control text-xs" placeholder="Contoh: BBCA" />
            <input type="number" step="0.01" name="holding_qty[]" value="<?php echo htmlspecialchars($entry['qty'] ?? ''); ?>" class="form-control text-xs" placeholder="0" />
            <input type="number" step="0.01" name="holding_avg_price[]" value="<?php echo htmlspecialchars($entry['avg_price'] ?? ''); ?>" class="form-control text-xs" placeholder="0" />
            <input type="number" step="0.01" name="holding_current_price[]" value="<?php echo htmlspecialchars($entry['current_price'] ?? ''); ?>" class="form-control text-xs" placeholder="0" />
          </div>
        <?php endfor; ?>
        <button type="submit" class="btn btn-primary text-xs">Generate AI Summary</button>
      </form>
    </div>
  </div>





  <!-- Portfolio Table -->
  <div class="w-full bg-surface-container-low border border-border-subtle rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-border-subtle bg-surface-container-high/30">
      <h2 class="text-xs font-bold text-primary uppercase">Holdings Detail</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead>
          <tr class="bg-surface-container-high/20 border-b border-border-subtle text-[10px] font-bold text-text-muted uppercase">
            <th class="py-2.5 px-4">STOCK</th>
            <th class="py-2.5 px-4 text-right">QTY</th>
            <th class="py-2.5 px-4 text-right">AVG PRICE</th>
            <th class="py-2.5 px-4 text-right">CURRENT PRICE</th>
            <th class="py-2.5 px-4 text-right">MARKET VALUE</th>
            <th class="py-2.5 px-4 text-right">P&L (RP)</th>
            <th class="py-2.5 px-4 text-right">AI</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-subtle font-mono text-xs">
          <?php if (empty($holdings)): ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-text-muted">Belum ada data posisi. Isi form manual untuk melihat ringkasan AI.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($holdings as $holding): ?>
              <tr class="hover:bg-surface-container-high transition-colors">
                <td class="px-4 py-3 font-bold text-primary"><?php echo htmlspecialchars($holding['symbol']); ?></td>
                <td class="px-4 py-3 text-right"><?php echo number_format($holding['qty'], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right">Rp <?php echo number_format($holding['avg_price'], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right">Rp <?php echo number_format($holding['current_price'], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right">Rp <?php echo number_format($holding['market_value'], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right <?php echo $holding['pnl_value'] >= 0 ? 'text-bullish' : 'text-bearish'; ?>"><?php echo ($holding['pnl_value'] >= 0 ? '+' : '') . 'Rp ' . number_format($holding['pnl_value'], 0, ',', '.'); ?></td>
                <td class="px-4 py-3 text-right">
                  <span class="badge <?php echo strpos(strtoupper((string)$holding['ai_signal']), 'BUY') !== false ? 'badge-buy' : (strpos(strtoupper((string)$holding['ai_signal']), 'SELL') !== false ? 'badge-sell' : 'badge-hold'); ?>"><?php echo htmlspecialchars($holding['ai_signal']); ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($holdings)): ?>
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">AI Summary Per Stock</h2>
        <span class="badge badge-buy">AI READY</span>
      </div>
      <div class="rounded border border-border-subtle bg-surface-container p-3 text-sm text-text-muted leading-relaxed mb-4">
        <?php echo htmlspecialchars($analysisResult['ai_summary'] ?? ''); ?>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <?php foreach ($holdings as $holding): ?>
          <div class="rounded border border-border-subtle p-3">
            <div class="flex items-center justify-between mb-2">
              <div class="font-bold text-primary"><?php echo htmlspecialchars($holding['symbol']); ?></div>
              <span class="badge <?php echo strpos(strtoupper((string)$holding['ai_signal']), 'BUY') !== false ? 'badge-buy' : (strpos(strtoupper((string)$holding['ai_signal']), 'SELL') !== false ? 'badge-sell' : 'badge-hold'); ?>"><?php echo htmlspecialchars($holding['ai_signal']); ?></span>
            </div>
            <div class="text-[11px] text-text-muted leading-relaxed">
              <?php echo htmlspecialchars($holding['ai_summary']); ?>
            </div>
            <div class="text-[10px] font-bold text-text-muted uppercase tracking-wider mt-2">Confidence: <?php echo (int)$holding['ai_confidence']; ?>%</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
