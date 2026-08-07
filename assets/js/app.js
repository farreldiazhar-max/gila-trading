/**
 * SahamPintar — Core Application JavaScript
 * 1-Second Real-Time Live Market Polling Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  console.log('[SahamPintar] Engine 1-second Real-time Polling Active.');

  // Global Search Handler
  const searchInput = document.getElementById('globalSearchInput');
  if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && searchInput.value.trim() !== '') {
        const symbol = searchInput.value.trim().toUpperCase();
        window.location.href = `analysis.php?stock=${symbol}`;
      }
    });
  }

  // Market open/close indicator for Indonesia stock hours
  const marketStatusBadge = document.getElementById('marketStatusBadge');
  const marketStatusDot = document.getElementById('marketStatusDot');
  const marketStatusText = document.getElementById('marketStatusText');

  const updateMarketStatus = () => {
    if (!marketStatusBadge || !marketStatusDot || !marketStatusText) return;

    const now = new Date();
    const localOffset = now.getTimezoneOffset() * 60000;
    const jakartaOffset = 7 * 3600000;
    const jakartaTime = new Date(now.getTime() + localOffset + jakartaOffset);
    const day = jakartaTime.getDay();
    const hour = jakartaTime.getHours();
    const minute = jakartaTime.getMinutes();
    const totalMinutes = hour * 60 + minute;

    const marketOpenStart = 9 * 60 + 30; // 09:30
    const marketOpenEnd = 15 * 60;      // 15:00
    const isWeekday = day >= 1 && day <= 5;
    const isOpen = isWeekday && totalMinutes >= marketOpenStart && totalMinutes < marketOpenEnd;

    if (isOpen) {
      marketStatusBadge.classList.add('market-open');
      marketStatusBadge.classList.remove('market-closed');
      marketStatusText.innerText = 'MARKET OPEN';
      marketStatusDot.classList.add('blink');
    } else {
      marketStatusBadge.classList.add('market-closed');
      marketStatusBadge.classList.remove('market-open');
      marketStatusText.innerText = 'MARKET CLOSED';
      marketStatusDot.classList.remove('blink');
    }
  };

  updateMarketStatus();
  setInterval(updateMarketStatus, 100);

  const getRealtimeSymbols = () => Array.from(new Set(Array.from(document.querySelectorAll('[data-realtime-symbol], .realtime-price[data-symbol]'))
    .map((el) => (el.getAttribute('data-realtime-symbol') || el.getAttribute('data-symbol') || '').trim().toUpperCase())
    .filter(Boolean)));

  const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(value);

  const updateLiveStatus = (label) => {
    document.querySelectorAll('[data-live-status]').forEach((el) => {
      el.textContent = label;
      el.className = label.includes('offline')
        ? 'px-2 py-0.5 rounded border border-warning/30 bg-warning/10 text-[9px] font-bold text-warning uppercase'
        : 'px-2 py-0.5 rounded border border-bullish/30 bg-bullish/10 text-[9px] font-bold text-bullish uppercase';
    });
  };

  const updateRealtimeData = () => {
    if (document.visibilityState === 'hidden') return;

    const symbols = getRealtimeSymbols();
    if (symbols.length === 0) return;

    const apiUrl = `../api/stock.php?batch=${encodeURIComponent(symbols.join(','))}`;
    fetch(apiUrl, { cache: 'no-store' })
      .then((res) => res.json())
      .then((data) => {
        if (!data || !data.quotes) {
          updateLiveStatus('LIVE • offline');
          return;
        }

        document.querySelectorAll('[data-realtime-symbol]').forEach((el) => {
          const sym = (el.getAttribute('data-realtime-symbol') || '').trim().toUpperCase();
          const quote = data.quotes[sym];
          if (!quote) return;

          const field = (el.getAttribute('data-realtime-field') || 'price').toLowerCase();
          if (field === 'change') {
            el.textContent = `${quote.change >= 0 ? '+' : ''}${quote.change.toFixed(2)} (${(quote.changePercent || 0).toFixed(2)}%)`;
          } else if (field === 'changepercent') {
            el.textContent = `${(quote.changePercent || 0).toFixed(2)}%`;
          } else {
            el.textContent = formatCurrency(quote.price);
          }
        });

        document.querySelectorAll('.realtime-price[data-symbol]').forEach((el) => {
          const sym = (el.getAttribute('data-symbol') || '').trim().toUpperCase();
          const quote = data.quotes[sym];
          if (!quote) return;
          el.textContent = formatCurrency(quote.price);
        });

        const now = new Date();
        updateLiveStatus(`LIVE • ${now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`);
      })
      .catch((err) => {
        console.error('Realtime sync error:', err);
        updateLiveStatus('LIVE • offline');
      });
  };

  updateLiveStatus('LIVE • syncing');
  updateRealtimeData();
  setInterval(updateRealtimeData, 15000);
});
