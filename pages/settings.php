<?php
$pageTitle = 'Settings';
$activePage = 'settings';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="flex flex-col w-full gap-6">
  <!-- Header Title -->
  <div>
    <h1 class="text-xl font-bold text-primary mb-1">Application Settings</h1>
    <p class="text-xs text-text-muted">Configure Google Sheets API integration, database credentials, and system options.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- AI Provider Card -->
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-5 flex flex-col gap-4">
      <div class="flex items-center gap-2 border-b border-border-subtle pb-3">
        <span class="material-symbols-outlined text-bullish">smart_toy</span>
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">AI Provider Configuration</h2>
      </div>

      <div class="form-group">
        <label class="form-label">AI Provider</label>
        <select class="form-control">
          <option value="heuristic" <?php echo $aiProvider === 'heuristic' ? 'selected' : ''; ?>>Heuristic Fallback</option>
          <option value="gemini" <?php echo $aiProvider === 'gemini' ? 'selected' : ''; ?>>Gemini</option>
          <option value="openai" <?php echo $aiProvider === 'openai' ? 'selected' : ''; ?>>OpenAI</option>
          <option value="openrouter" <?php echo $aiProvider === 'openrouter' ? 'selected' : ''; ?>>OpenRouter</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Model Name</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($aiModel); ?>" />
      </div>

      <div class="form-group">
        <label class="form-label">API Key Status</label>
        <div class="p-3 bg-surface-container border border-border-subtle rounded text-xs flex items-center justify-between">
          <span class="font-mono text-text-muted"><?php echo $aiConfigured ? 'Configured' : 'Not configured'; ?></span>
          <span class="badge <?php echo $aiConfigured ? 'badge-buy' : 'badge-hold'; ?>"><?php echo $aiConfigured ? 'READY' : 'FALLBACK'; ?></span>
        </div>
      </div>

      <button class="btn btn-primary text-xs py-2">Save AI Config</button>
    </div>

    <!-- Broker Integration Card -->
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-5 flex flex-col gap-4">
      <div class="flex items-center gap-2 border-b border-border-subtle pb-3">
        <span class="material-symbols-outlined text-primary">link</span>
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">Broker / Securities Integrations</h2>
      </div>

      <div class="form-group">
        <label class="form-label">Provider</label>
        <select class="form-control">
          <option>Ajaib</option>
          <option>Stockbit</option>
          <option>Mirae Asset</option>
          <option>Manual / CSV</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">API Key / Token</label>
        <input type="password" class="form-control" placeholder="Masukkan token API atau secret" />
      </div>

      <div class="form-group">
        <label class="form-label">Recommended setup</label>
        <div class="p-3 bg-surface-container border border-border-subtle rounded text-xs text-text-muted leading-relaxed">
          Gunakan koneksi broker untuk mengambil posisi, saldo, dan sinyal trading. Untuk saat ini sistem mendukung mode simulasi dan integrasi modular yang siap dipasang saat API tersedia.
        </div>
      </div>

      <button class="btn btn-outline text-xs py-2">Connect Broker</button>
    </div>

    <!-- Google Sheets API Card -->
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-5 flex flex-col gap-4">
      <div class="flex items-center gap-2 border-b border-border-subtle pb-3">
        <span class="material-symbols-outlined text-bullish">table_chart</span>
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">Google Sheets Integration</h2>
      </div>

      <div class="form-group">
        <label class="form-label">Spreadsheet ID</label>
        <input type="text" class="form-control" placeholder="e.g. 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms" value="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"/>
      </div>

      <div class="form-group">
        <label class="form-label">Service Account JSON Key File</label>
        <div class="p-3 bg-surface-container border border-border-subtle rounded text-xs flex items-center justify-between">
          <span class="font-mono text-text-muted">google-service-account.json</span>
          <span class="badge badge-buy">CONFIGURED</span>
        </div>
      </div>

      <button class="btn btn-primary text-xs py-2">Save Sheets Config</button>
    </div>

    <!-- Database & System Card -->
    <div class="bg-surface-container-low border border-border-subtle rounded-lg p-5 flex flex-col gap-4">
      <div class="flex items-center gap-2 border-b border-border-subtle pb-3">
        <span class="material-symbols-outlined text-primary">database</span>
        <h2 class="text-xs font-bold text-primary uppercase tracking-wider">Database & System</h2>
      </div>

      <div class="form-group">
        <label class="form-label">MySQL Connection Status</label>
        <div class="p-3 bg-surface-container border border-border-subtle rounded text-xs flex items-center justify-between">
          <span class="font-mono text-text-muted">localhost / gila-trading</span>
          <span class="badge badge-buy">CONNECTED</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Yahoo Finance API Cache</label>
        <div class="p-3 bg-surface-container border border-border-subtle rounded text-xs flex items-center justify-between">
          <span class="font-mono text-text-muted">Cached Tickers: 12 stocks</span>
          <button class="btn btn-outline py-1 px-2 text-[10px]">Clear Cache</button>
        </div>
      </div>

      <button class="btn btn-outline text-xs py-2">Test Connections</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
