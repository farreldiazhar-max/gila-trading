document.addEventListener('DOMContentLoaded', () => {
  const logEl = document.getElementById('gaspolLog');
  const statusText = document.getElementById('gaspolStatusText');
  const startBtn = document.getElementById('gaspolStartBtn');
  const stopBtn = document.getElementById('gaspolStopBtn');
  const simBuy = document.getElementById('gaspolSimBuy');
  const simSell = document.getElementById('gaspolSimSell');
  const saveConfigBtn = document.getElementById('gaspolSaveConfig');
  const loadConfigBtn = document.getElementById('gaspolLoadConfig');

  const appendLog = (msg) => {
    const p = document.createElement('div');
    p.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
    logEl.prepend(p);
  };

  const fetchJson = (url, options={}) => fetch(url, options).then(r=>r.json());
  // Initial load: read server config and flags
  const initLoadConfig = () => {
    fetchJson('../api/gaspol_trade.php?action=get_config')
      .then(res => {
        if (res && res.config) {
          document.getElementById('stockbitApiUrl').value = res.config.stockbit_api_url || '';
          // server will not return stored API key for security
          if (res.env_key_present) {
            // hide API key input and show notice
            const keyInput = document.getElementById('stockbitApiKey');
            if (keyInput) { keyInput.style.display = 'none'; keyInput.placeholder = 'Using server-side API key'; }
            const note = document.createElement('div');
            note.className = 'text-xs text-text-muted';
            note.innerText = 'Using server environment STOCKBIT_API_KEY for broker connectivity.';
            keyInput?.parentNode.appendChild(note);
            const allowChk = document.getElementById('gaspolAllowSaveKey');
            if (allowChk) { allowChk.disabled = true; allowChk.checked = false; }
          }
          if (res.internal_key_required) {
            appendLog('Server requires internal API key for sensitive operations. UI save/start/trade may be restricted.');
          }
          appendLog('Config loaded');
        }
      }).catch(e=>appendLog('Load config failed: '+e.message));
  };
  initLoadConfig();

  loadConfigBtn?.addEventListener('click', initLoadConfig);

  saveConfigBtn?.addEventListener('click', () => {
    const payload = {
      stockbit_api_url: document.getElementById('stockbitApiUrl').value.trim(),
      stockbit_api_key: document.getElementById('stockbitApiKey').value.trim(),
      allow_save_key: !!document.getElementById('gaspolAllowSaveKey')?.checked
    };
    fetch('../api/gaspol_trade.php?action=save_config', {
      method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
    }).then(r=>r.json()).then(res=>{
      if (res && res.ok) appendLog('Config saved'); else appendLog('Failed to save config (server may require authorization)');
    }).catch(e=>appendLog('Save failed: '+e.message));
  });

  // Modal elements
  const modal = document.getElementById('gaspolConfirmModal');
  const modalMsg = document.getElementById('gaspolPreflightMsg');
  const modalCheckbox = document.getElementById('gaspolConfirmCheckbox');
  const modalConfirmInput = document.getElementById('gaspolConfirmInput');
  const modalCancel = document.getElementById('gaspolCancelConfirm');
  const modalConfirm = document.getElementById('gaspolConfirmAction');

  const showModal = (message) => {
    if (modalMsg) modalMsg.innerText = message || '';
    if (modal) modal.setAttribute('aria-hidden', 'false');
    if (modalCheckbox) modalCheckbox.checked = false;
    if (modalConfirmInput) modalConfirmInput.value = '';
  };
  const hideModal = () => { if (modal) modal.setAttribute('aria-hidden', 'true'); if (modalConfirmInput) modalConfirmInput.value = ''; };

  const preflightCheck = () => {
    return fetchJson('../api/gaspol_trade.php?action=preflight')
      .catch(err => ({ ok: false, error: err.message }));
  };

  startBtn?.addEventListener('click', async () => {
    const mode = document.getElementById('gaspolMode').value || 'semi-auto';
    const symbol = document.getElementById('gaspolSymbol').value.trim();
    const size = parseFloat(document.getElementById('gaspolSize').value) || 1;

    appendLog(`Start requested (${mode}) for ${symbol} x${size}`);

    if (mode === 'auto') {
      // run preflight
      showModal('Menjalankan pengecekan prasyarat...');
      const pf = await preflightCheck();
      if (!pf || !pf.ok) {
        showModal('Preflight gagal: ' + (pf && pf.message ? pf.message : (pf && pf.error ? pf.error : 'Unknown')));
        return;
      }
      // Show details
      showModal('Preflight OK. Broker reachable: ' + (pf.reachable ? 'Yes' : 'No') + '. Centang untuk konfirmasi.');
      modalConfirm.onclick = async () => {
        const typed = modalConfirmInput ? (modalConfirmInput.value || '').trim().toLowerCase() : '';
        if (!modalCheckbox.checked) { appendLog('Harap centang konfirmasi sebelum melanjutkan.'); return; }
        if (typed !== 'confirm') { appendLog('Harap ketik "CONFIRM" di kotak konfirmasi.'); return; }
        hideModal();
        const resp = await fetch('../api/gaspol_trade.php?action=start', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ mode }) }).then(r=>r.json()).catch(e=>({ok:false,error:e.message}));
        if (resp.ok) { statusText.innerText = 'Running'; appendLog('Bot started (live)'); }
        else appendLog('Start failed: ' + (resp.error || 'unknown'));
      };
    } else {
      // semi-auto: show confirmation modal but simpler
      showModal(`Semi-auto: akan meminta persetujuan sebelum eksekusi. Konfirmasi untuk start.`);
      modalConfirm.onclick = async () => {
        const typed = modalConfirmInput ? (modalConfirmInput.value || '').trim().toLowerCase() : '';
        // For semi-auto, require confirm typing as well for safety
        if (!modalCheckbox.checked) { appendLog('Harap centang konfirmasi sebelum melanjutkan.'); return; }
        if (typed !== 'confirm') { appendLog('Harap ketik "CONFIRM" di kotak konfirmasi.'); return; }
        hideModal();
        const resp = await fetch('../api/gaspol_trade.php?action=start', { method: 'POST' }).then(r=>r.json()).catch(e=>({ok:false,error:e.message}));
        if (resp.ok) { statusText.innerText = 'Running'; appendLog('Bot started (semi-auto)'); }
        else appendLog('Start failed: ' + (resp.error || 'unknown'));
      };
    }
  });

  stopBtn?.addEventListener('click', () => {
    fetch('../api/gaspol_trade.php?action=stop', { method: 'POST' })
      .then(r=>r.json()).then(res=>{
        if (res && res.ok) {
          statusText.innerText = 'Stopped';
          appendLog('Bot stopped');
        } else appendLog('Stop failed');
      }).catch(e=>appendLog('Stop error: '+e.message));
  });

  const doTrade = (side) => {
    const symbol = document.getElementById('gaspolSymbol').value.trim();
    const size = parseFloat(document.getElementById('gaspolSize').value) || 1;
    if (!symbol) { appendLog('Symbol required'); return; }
    fetch('../api/gaspol_trade.php?action=trade', {
      method: 'POST', headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ symbol, side, size })
    }).then(r=>r.json()).then(res=>{
      if (res && res.ok) appendLog(`Trade ${side} executed: ${JSON.stringify(res.result)}`);
      else appendLog('Trade failed: ' + (res.error || 'unknown'));
    }).catch(e=>appendLog('Trade error: '+e.message));
  };

  simBuy?.addEventListener('click', () => doTrade('BUY'));
  simSell?.addEventListener('click', () => doTrade('SELL'));

  modalCancel?.addEventListener('click', () => { hideModal(); appendLog('User cancelled confirmation'); });

  // Back button: try history.back() when referrer is Gila Trade, else navigate to /pages/gila_trade.php
  const backBtn = document.getElementById('gaspolBackBtn');
  if (backBtn) {
    backBtn.addEventListener('click', (e) => {
      const ref = document.referrer || '';
      if (ref.toLowerCase().includes('gila')) {
        history.back();
        return;
      }
      // fallback: navigate to site-relative pages/gila_trade.php
      try {
        const url = window.location.origin + '/pages/gila_trade.php';
        window.location.href = url;
      } catch (err) {
        // final fallback: go to homepage
        window.location.href = window.location.origin + '/index.php';
      }
    });
  }

});
