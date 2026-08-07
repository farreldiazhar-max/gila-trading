<?php
$pageTitle = 'Trade Logbook';
$activePage = 'logbook';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col w-full gap-6">
  <!-- Top Metrics Bar -->
  <div class="w-full grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col justify-between">
      <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">WIN RATE</span>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-bold text-primary tracking-tight">68.5%</span>
        <span class="font-mono text-xs text-bullish">+2.4%</span>
      </div>
      <div class="w-full h-1 bg-surface-container-highest rounded-full mt-3 overflow-hidden">
        <div class="h-full bg-bullish rounded-full w-[68.5%]"></div>
      </div>
    </div>

    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col justify-between">
      <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">TOTAL P&L (MTD)</span>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-bold text-bullish tracking-tight">Rp 12.4M</span>
      </div>
      <div class="h-6 w-full mt-2 text-bullish">
        <svg class="w-full h-full stroke-current fill-none" viewBox="0 0 100 30">
          <path d="M0,25 Q10,20 20,22 T40,15 T60,18 T80,5 T100,2" stroke-linecap="round" stroke-width="2"></path>
        </svg>
      </div>
    </div>

    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col justify-between">
      <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider mb-1">AVG TRADE DURATION</span>
      <div class="flex items-baseline gap-2">
        <span class="text-2xl font-bold text-primary tracking-tight">3.2</span>
        <span class="text-xs text-text-muted">Days</span>
      </div>
    </div>

    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-4 flex flex-col items-center justify-center gap-2 hover:bg-surface-container-high cursor-pointer transition-colors group">
      <span class="material-symbols-outlined text-text-muted group-hover:text-primary transition-colors">sync</span>
      <span class="font-mono text-xs text-text-muted group-hover:text-primary transition-colors">Sync with Sheets</span>
    </div>
  </div>

  <!-- Trade Journal Table -->
  <div class="w-full flex flex-col bg-surface-container-low border border-border-subtle rounded-lg overflow-hidden">
    <div class="flex flex-col gap-3 px-4 py-3 border-b border-border-subtle bg-surface-container-high/30 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">TRADE LOGBOOK</h2>
        <p class="text-[10px] text-text-muted">Masukkan jurnal trading manual atau impor dari CSV/XLSX.</p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <button id="clearLogbook" class="btn btn-outline py-1 px-3 text-xs flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">delete_sweep</span> Clear Logbook
        </button>
        <button id="toggleAddTrade" class="btn btn-primary py-1 px-3 text-xs flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">add</span> Add Trade
        </button>
        <label class="btn btn-outline py-1 px-3 text-xs flex items-center gap-1 cursor-pointer">
          <span class="material-symbols-outlined text-[16px]">upload_file</span> Import CSV/XLSX
          <input id="importFile" type="file" accept=".csv,.xlsx" class="hidden" />
        </label>
      </div>
    </div>

    <div id="tradeFormContainer" class="hidden px-4 py-4 border-b border-border-subtle bg-surface-container-high/10">
      <form id="tradeForm" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div>
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Tanggal / Waktu</label>
          <input id="tradeDate" type="datetime-local" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary" />
        </div>
        <div>
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Saham</label>
          <input id="tradeStock" type="text" placeholder="BBCA" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary" />
        </div>
        <div>
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Aksi</label>
          <select id="tradeAction" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary">
            <option value="BUY">BUY</option>
            <option value="SELL">SELL</option>
          </select>
        </div>
        <div>
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Harga Rata-rata</label>
          <input id="tradePrice" type="number" step="any" placeholder="0" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary" />
        </div>
        <div>
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Net P&L</label>
          <input id="tradePnl" type="number" step="any" placeholder="0" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary" />
        </div>
        <div>
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Tags / Setup</label>
          <input id="tradeTags" type="text" placeholder="Breakout, Swing" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary" />
        </div>
        <div class="md:col-span-2 xl:col-span-4">
          <label class="block text-[10px] text-text-muted uppercase tracking-wider mb-1">Catatan</label>
          <textarea id="tradeNotes" rows="3" placeholder="Catatan strategi atau kondisi pasar" class="w-full rounded border border-border-subtle bg-surface-container-lowest px-3 py-2 text-sm text-primary"></textarea>
        </div>
        <div class="md:col-span-2 xl:col-span-4 text-right">
          <button type="button" id="saveTradeBtn" class="btn btn-primary py-2 px-4 text-xs">Save Entry</button>
        </div>
      </form>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left whitespace-nowrap border-collapse">
        <thead>
          <tr class="text-[10px] font-bold text-text-muted uppercase border-b border-border-subtle bg-surface-container-high/20">
            <th class="py-2.5 px-4">DATE</th>
            <th class="py-2.5 px-4">STOCK</th>
            <th class="py-2.5 px-4 text-center">ACTION</th>
            <th class="py-2.5 px-4 text-right">PRICE (AVG)</th>
            <th class="py-2.5 px-4 text-right">NET P&L</th>
            <th class="py-2.5 px-4">TAGS / SETUP</th>
            <th class="py-2.5 px-4 w-1/3">NOTES</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border-subtle font-mono text-xs">
          <!-- Row 1 -->
          <tr class="hover:bg-surface-container-high transition-colors">
            <td class="py-3 px-4 text-text-muted">24 Oct, 09:15</td>
            <td class="py-3 px-4 text-primary font-bold">BBCA</td>
            <td class="py-3 px-4 text-center"><span class="badge badge-buy">BUY</span></td>
            <td class="py-3 px-4 text-right">9,150</td>
            <td class="py-3 px-4 text-right text-text-muted">-</td>
            <td class="py-3 px-4">
              <span class="px-2 py-0.5 rounded text-[10px] text-text-muted border border-border-subtle bg-surface-container">Breakout</span>
            </td>
            <td class="py-3 px-4 font-sans text-text-muted truncate max-w-[200px]" title="Entering on volume spike above resistance level.">Entering on volume spike...</td>
          </tr>

          <!-- Row 2 -->
          <tr class="hover:bg-surface-container-high transition-colors">
            <td class="py-3 px-4 text-text-muted">23 Oct, 14:40</td>
            <td class="py-3 px-4 text-primary font-bold">GOTO</td>
            <td class="py-3 px-4 text-center"><span class="badge badge-sell">SELL</span></td>
            <td class="py-3 px-4 text-right">64</td>
            <td class="py-3 px-4 text-right text-bearish">-Rp 450.000</td>
            <td class="py-3 px-4">
              <span class="px-2 py-0.5 rounded text-[10px] text-text-muted border border-border-subtle bg-surface-container">Stoploss</span>
            </td>
            <td class="py-3 px-4 font-sans text-text-muted truncate max-w-[200px]" title="Hit stoploss at MA20 support breakdown.">Hit stoploss at MA20...</td>
          </tr>

          <!-- Row 3 -->
          <tr class="hover:bg-surface-container-high transition-colors">
            <td class="py-3 px-4 text-text-muted">21 Oct, 10:20</td>
            <td class="py-3 px-4 text-primary font-bold">BRPT</td>
            <td class="py-3 px-4 text-center"><span class="badge badge-sell">SELL</span></td>
            <td class="py-3 px-4 text-right">1,250</td>
            <td class="py-3 px-4 text-right text-bullish">+Rp 2.150.000</td>
            <td class="py-3 px-4">
              <span class="px-2 py-0.5 rounded text-[10px] text-text-muted border border-border-subtle bg-surface-container">Swing</span>
              <span class="px-2 py-0.5 rounded text-[10px] text-text-muted border border-border-subtle bg-surface-container">Take Profit</span>
            </td>
            <td class="py-3 px-4 font-sans text-text-muted truncate max-w-[200px]" title="TP1 hit. Scaling out 50% position.">TP1 hit. Scaling out 50%...</td>
          </tr>

          <!-- Row 4 -->
          <tr class="hover:bg-surface-container-high transition-colors">
            <td class="py-3 px-4 text-text-muted">18 Oct, 09:05</td>
            <td class="py-3 px-4 text-primary font-bold">AMMN</td>
            <td class="py-3 px-4 text-center"><span class="badge badge-buy">BUY</span></td>
            <td class="py-3 px-4 text-right">6,800</td>
            <td class="py-3 px-4 text-right text-text-muted">-</td>
            <td class="py-3 px-4">
              <span class="px-2 py-0.5 rounded text-[10px] text-text-muted border border-border-subtle bg-surface-container">Bounce</span>
            </td>
            <td class="py-3 px-4 font-sans text-text-muted truncate max-w-[200px]" title="Buy on weakness near Fibonacci level.">Buy on weakness near Fibo...</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t border-border-subtle flex flex-col gap-3 md:flex-row md:items-center md:justify-between bg-surface-container-high/10 text-xs">
      <span id="logbookSummary" class="font-mono text-text-muted">Showing 0 entries</span>
      <div class="flex flex-wrap gap-1">
        <button id="prevPage" class="btn btn-outline py-1 px-2.5 text-xs">&lt;</button>
        <button id="nextPage" class="btn btn-outline py-1 px-2.5 text-xs">&gt;</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const logbookTableBody = document.querySelector('tbody');
    const logbookSummary = document.getElementById('logbookSummary');
    const toggleAddTrade = document.getElementById('toggleAddTrade');
    const tradeFormContainer = document.getElementById('tradeFormContainer');
    const saveTradeBtn = document.getElementById('saveTradeBtn');
    const clearLogbookBtn = document.getElementById('clearLogbook');
    const importFileInput = document.getElementById('importFile');
    const tradeDate = document.getElementById('tradeDate');
    const tradeStock = document.getElementById('tradeStock');
    const tradeAction = document.getElementById('tradeAction');
    const tradePrice = document.getElementById('tradePrice');
    const tradePnl = document.getElementById('tradePnl');
    const tradeTags = document.getElementById('tradeTags');
    const tradeNotes = document.getElementById('tradeNotes');

    let entries = [];
    let page = 1;
    const pageSize = 20;

    const fetchEntries = async () => {
      const response = await fetch('<?php echo BASE_URL; ?>api/logbook.php?action=load');
      const result = await response.json();
      if (result.ok) {
        entries = result.entries;
        renderTable();
      }
    };

    const renderTable = () => {
      const start = (page - 1) * pageSize;
      const pageEntries = entries.slice(start, start + pageSize);
      logbookTableBody.innerHTML = pageEntries.map(entry => `
        <tr class="hover:bg-surface-container-high transition-colors">
          <td class="py-3 px-4 text-text-muted">${entry.date || '-'}</td>
          <td class="py-3 px-4 text-primary font-bold">${entry.stock || '-'}</td>
          <td class="py-3 px-4 text-center"><span class="badge ${entry.action === 'SELL' ? 'badge-sell' : 'badge-buy'}">${entry.action || '-'}</span></td>
          <td class="py-3 px-4 text-right">${entry.avg_price ? Number(entry.avg_price).toLocaleString('id-ID') : '-'}</td>
          <td class="py-3 px-4 text-right ${entry.pnl > 0 ? 'text-bullish' : (entry.pnl < 0 ? 'text-bearish' : 'text-text-muted')} ">${entry.pnl ? Number(entry.pnl).toLocaleString('id-ID') : '-'}</td>
          <td class="py-3 px-4">${entry.tags || '-'}</td>
          <td class="py-3 px-4 font-sans text-text-muted truncate max-w-[200px]" title="${entry.notes || ''}">${entry.notes || '-'}</td>
        </tr>
      `).join('');
      logbookSummary.textContent = `Showing ${entries.length} entries`;
    };

    toggleAddTrade.addEventListener('click', () => {
      tradeFormContainer.classList.toggle('hidden');
      if (!tradeDate.value) {
        tradeDate.value = new Date().toISOString().slice(0, 16);
      }
    });

    saveTradeBtn.addEventListener('click', async () => {
      const payload = {
        entry: {
          date: tradeDate.value,
          stock: tradeStock.value,
          action: tradeAction.value,
          avg_price: tradePrice.value,
          pnl: tradePnl.value,
          tags: tradeTags.value,
          notes: tradeNotes.value,
        }
      };
      const response = await fetch('<?php echo BASE_URL; ?>api/logbook.php?action=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const result = await response.json();
      if (result.ok) {
        entries.unshift(result.entry);
        renderTable();
        tradeStock.value = '';
        tradePrice.value = '';
        tradePnl.value = '';
        tradeTags.value = '';
        tradeNotes.value = '';
      } else {
        alert(result.error || 'Gagal menyimpan entry.');
      }
    });

    clearLogbookBtn.addEventListener('click', async () => {
      if (!confirm('Kosongkan logbook? Data tidak dapat dikembalikan.')) {
        return;
      }
      const response = await fetch('<?php echo BASE_URL; ?>api/logbook.php?action=clear', { method: 'POST' });
      const result = await response.json();
      if (result.ok) {
        entries = [];
        renderTable();
      } else {
        alert(result.error || 'Gagal mengosongkan logbook.');
      }
    });

    importFileInput.addEventListener('change', async (event) => {
      const file = event.target.files[0];
      if (!file) {
        return;
      }
      const formData = new FormData();
      formData.append('file', file);
      const response = await fetch('<?php echo BASE_URL; ?>api/logbook.php?action=import', {
        method: 'POST',
        body: formData,
      });
      const result = await response.json();
      if (result.ok) {
        entries = result.entries.concat(entries);
        renderTable();
        alert(`Imported ${result.imported} rows successfully.`);
      } else {
        alert(result.error || 'Gagal mengimpor file.');
      }
      importFileInput.value = '';
    });

    fetchEntries();
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
