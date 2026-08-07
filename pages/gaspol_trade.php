<?php
$pageTitle = 'Gaspol Trade';
$activePage = 'gaspol';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="flex flex-col w-full gap-6 gaspol-page">
  <div class="flex items-center justify-between gaspol-hero">
    <div>
      <h1 class="h2">Gaspol Trade</h1>
      <p class="text-xs text-text-muted">Semi-auto trading integration with Stockbit — trusted workflow.</p>
    </div>
    <div class="hero-actions">
      <button id="gaspolBackBtn" class="btn btn-outline">← Kembali ke Gila Trade</button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
      <div class="glass-card">
        <div class="glass-card-header">
          <div class="glass-card-title">Bot Control Panel</div>
          <div class="text-xs text-text-muted">Status: <span id="gaspolStatusText">Stopped</span></div>
        </div>

        <div class="space-y-4">
          <div class="form-group">
            <label class="form-label">Trading Mode</label>
            <select id="gaspolMode" class="">
              <option value="semi-auto">Semi-Auto (require approval)</option>
              <option value="auto">Auto (live execution)</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="form-group">
              <label class="form-label">Symbol</label>
              <input id="gaspolSymbol" type="text" placeholder="BBCA" />
            </div>
            <div class="form-group">
              <label class="form-label">Order Size</label>
              <input id="gaspolSize" type="number" value="1" min="1" />
            </div>
          </div>

          <div class="flex gap-3">
            <button id="gaspolStartBtn" class="btn btn-primary">Start Bot</button>
            <button id="gaspolStopBtn" class="btn btn-outline">Stop Bot</button>
            <button id="gaspolSimBuy" class="btn btn-bullish">Simulate Buy</button>
            <button id="gaspolSimSell" class="btn btn-outline">Simulate Sell</button>
          </div>

          <div>
            <label class="form-label">Log</label>
            <div id="gaspolLog" class="gaspol-log p-3 bg-surface-container-low rounded shadow-sm text-text-secondary"></div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="glass-card">
        <div class="glass-card-header">
          <div class="glass-card-title">Stockbit / Broker Settings</div>
        </div>
        <div class="space-y-3">
          <div class="form-group">
            <label class="form-label">Stockbit API URL</label>
            <input id="stockbitApiUrl" type="text" placeholder="https://api.stockbit.com/..." />
          </div>
          <div class="form-group">
            <label class="form-label">API Key / Token</label>
            <input id="stockbitApiKey" type="text" placeholder="YOUR_API_KEY" />
            <label class="text-xs text-text-muted mt-1"><input type="checkbox" id="gaspolAllowSaveKey" /> Simpan API key di server (requires server-side authorization)</label>
          </div>
          <div class="flex gap-2">
            <button id="gaspolSaveConfig" class="btn btn-primary">Save Config</button>
            <button id="gaspolLoadConfig" class="btn btn-outline">Load Config</button>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Confirmation Modal -->
<div id="gaspolConfirmModal" class="confirm-modal" aria-hidden="true">
  <div class="confirm-modal-card">
    <h3 class="h3">Konfirmasi Eksekusi</h3>
    <p class="text-text-secondary" id="gaspolPreflightMsg">Memeriksa prasyarat...</p>
      <div class="confirm-modal-body">
        <label class="confirm-accept"><input type="checkbox" id="gaspolConfirmCheckbox" /> Saya memahami risiko dan mengizinkan eksekusi live</label>
        <div class="confirm-input-row">
          <input id="gaspolConfirmInput" placeholder="Ketik CONFIRM untuk mengaktifkan" class="form-control" />
          <span class="confirm-note text-text-muted">(case-insensitive)</span>
        </div>
      </div>
    <div class="confirm-modal-actions">
      <button id="gaspolCancelConfirm" class="btn btn-outline">Batal</button>
      <button id="gaspolConfirmAction" class="btn btn-primary">Konfirmasi & Jalankan</button>
    </div>
  </div>
</div>

<?php
  $extraScripts = ['assets/js/gaspol_trade.js?v=2'];
  require_once __DIR__ . '/../includes/footer.php';
?>
