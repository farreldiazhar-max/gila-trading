/**
 * SahamPintar — Advanced Technical Candlestick Engine
 * Supports TradingView Lightweight Charts + HTML5 Canvas Fallback
 * Includes Timeframe Range Selector (1D, 1W, 1M, YTD, 1Y, 3Y, 5Y) & Dynamic UI Sync
 */

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('chartContainer');
  if (!container) return;

  const stockSymbol = container.getAttribute('data-stock') || 'BBCA';
  let currentRange = '1y';
  let currentInterval = '1d';

  // Read initial history JSON data
  const historyAttr = container.getAttribute('data-history');
  let rawHistory = [];
  if (historyAttr) {
    try { rawHistory = JSON.parse(historyAttr); } catch (e) { console.error('History parse error:', e); }
  }

  // Dual-Engine Chart Control
  let lightweightChart = null;
  let candlestickSeries = null;
  let volumeSeries = null;
  let ma5Series = null;
  let ma10Series = null;
  let ma50Series = null;
  let bollingerUpperSeries = null;
  let bollingerMiddleSeries = null;
  let bollingerLowerSeries = null;
  let legendEl = null;
  let showMa5 = true;
  let showMa10 = true;
  let showMa50 = true;
  let showBollinger = true;
  let currentHistory = [];

  function normalizeChartTime(value) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return new Date(value);

    const absolute = Math.abs(numeric);
    if (absolute >= 1e12) {
      return new Date(numeric);
    }
    if (absolute >= 1e9) {
      return new Date(numeric * 1000);
    }
    return new Date(numeric * 1000);
  }

  function getTimeFormatOptions(range = currentRange) {
    switch (range) {
      case '1d':
        return { hour: '2-digit', minute: '2-digit', hour12: false };
      case '5d':
      case '1mo':
        return { day: '2-digit', month: 'short' };
      case 'ytd':
      case '1y':
        return { month: 'short', year: '2-digit' };
      case '3y':
      case '5y':
        return { year: 'numeric' };
      default:
        return { day: '2-digit', month: 'short', year: 'numeric' };
    }
  }

  function formatChartTimeLabel(value, range = currentRange) {
    const date = normalizeChartTime(value);
    return new Intl.DateTimeFormat('id-ID', {
      timeZone: 'Asia/Jakarta',
      ...getTimeFormatOptions(range),
    }).format(date);
  }

  function applyTimeScaleFormatting() {
    if (!lightweightChart) return;

    lightweightChart.applyOptions({
      localization: {
        timeFormatter: (time) => formatChartTimeLabel(time, currentRange),
      },
    });

    lightweightChart.timeScale().applyOptions({
      tickMarkFormatter: (time) => formatChartTimeLabel(time, currentRange),
    });
  }

  // Initialize Chart Engine
  function syncIndicatorTogglesUI() {
    const ma5Button = document.getElementById('ma5Toggle');
    const ma5State = document.getElementById('ma5ToggleState');
    if (ma5Button) {
      ma5Button.className = `inline-flex items-center gap-2 px-3 py-1.5 rounded border transition-colors ${showMa5 ? 'border-cyan-400/40 bg-cyan-400/10 text-cyan-300' : 'border-border-subtle bg-surface-container text-text-muted hover:text-white'}`;
    }
    if (ma5State) ma5State.innerText = showMa5 ? 'ON' : 'OFF';

    const ma10Button = document.getElementById('ma10Toggle');
    const ma10State = document.getElementById('ma10ToggleState');
    if (ma10Button) {
      ma10Button.className = `inline-flex items-center gap-2 px-3 py-1.5 rounded border transition-colors ${showMa10 ? 'border-violet-400/40 bg-violet-400/10 text-violet-300' : 'border-border-subtle bg-surface-container text-text-muted hover:text-white'}`;
    }
    if (ma10State) ma10State.innerText = showMa10 ? 'ON' : 'OFF';

    const ma50Button = document.getElementById('ma50Toggle');
    const ma50State = document.getElementById('ma50ToggleState');
    if (ma50Button) {
      ma50Button.className = `inline-flex items-center gap-2 px-3 py-1.5 rounded border transition-colors ${showMa50 ? 'border-blue-400/40 bg-blue-400/10 text-blue-300' : 'border-border-subtle bg-surface-container text-text-muted hover:text-white'}`;
    }
    if (ma50State) ma50State.innerText = showMa50 ? 'ON' : 'OFF';

    const bollingerButton = document.getElementById('bollingerToggle');
    const bollingerState = document.getElementById('bollingerToggleState');
    if (bollingerButton) {
      bollingerButton.className = `inline-flex items-center gap-2 px-3 py-1.5 rounded border transition-colors ${showBollinger ? 'border-amber-400/40 bg-amber-400/10 text-amber-300' : 'border-border-subtle bg-surface-container text-text-muted hover:text-white'}`;
    }
    if (bollingerState) bollingerState.innerText = showBollinger ? 'ON' : 'OFF';
  }

  function renderChart(history) {
    if (typeof LightweightCharts !== 'undefined' && lightweightChart) {
      renderLightweightData(history);
    } else {
      renderCanvasCandlestick(history);
    }
  }

  function initChartEngine() {
    container.innerHTML = '';

    // Create Legend Element
    legendEl = document.createElement('div');
    legendEl.className = 'chart-legend';
    legendEl.style.cssText = 'position: absolute; left: 14px; top: 12px; z-index: 20; font-family: JetBrains Mono, monospace; font-size: 11px; color: #94a3b8; pointer-events: none; background: rgba(15, 19, 28, 0.85); padding: 6px 12px; border-radius: 4px; border: 1px solid rgba(148, 163, 184, 0.1); backdrop-filter: blur(8px);';
    container.appendChild(legendEl);

    if (typeof LightweightCharts !== 'undefined') {
      // ----------------------------------------------------
      // ENGINE 1: TradingView Lightweight Charts
      // ----------------------------------------------------
      lightweightChart = LightweightCharts.createChart(container, {
        width: container.clientWidth,
        height: 420,
        layout: {
          background: { type: 'solid', color: '#181b25' },
          textColor: '#94a3b8',
          fontFamily: 'JetBrains Mono, monospace',
        },
        grid: {
          vertLines: { color: 'rgba(148, 163, 184, 0.08)' },
          horzLines: { color: 'rgba(148, 163, 184, 0.08)' },
        },
        crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
        rightPriceScale: { borderColor: 'rgba(148, 163, 184, 0.15)' },
        localization: {
          timeFormatter: (time) => formatChartTimeLabel(time, currentRange),
        },
        timeScale: {
          borderColor: 'rgba(148, 163, 184, 0.15)',
          timeVisible: true,
          tickMarkFormatter: (time) => formatChartTimeLabel(time, currentRange),
        },
      });

      candlestickSeries = lightweightChart.addCandlestickSeries({
        upColor: '#22c55e',
        downColor: '#ef4444',
        borderVisible: false,
        wickUpColor: '#22c55e',
        wickDownColor: '#ef4444',
      });

      volumeSeries = lightweightChart.addHistogramSeries({
        color: 'rgba(59, 130, 246, 0.3)',
        priceFormat: { type: 'volume' },
        priceScaleId: '',
        scaleMargins: { top: 0.8, bottom: 0 },
      });

      ma5Series = lightweightChart.addLineSeries({ color: '#22d3ee', lineWidth: 1.4, title: 'MA5' });
      ma10Series = lightweightChart.addLineSeries({ color: '#a78bfa', lineWidth: 1.4, title: 'MA10' });
      ma50Series = lightweightChart.addLineSeries({ color: '#60a5fa', lineWidth: 1.4, title: 'MA50' });
      bollingerUpperSeries = lightweightChart.addLineSeries({ color: '#f59e0b', lineWidth: 1, title: 'BB Upper' });
      bollingerMiddleSeries = lightweightChart.addLineSeries({ color: '#8b5cf6', lineWidth: 1, title: 'BB Middle' });
      bollingerLowerSeries = lightweightChart.addLineSeries({ color: '#f59e0b', lineWidth: 1, title: 'BB Lower' });

      lightweightChart.subscribeCrosshairMove((param) => {
        if (!param.time || !param.seriesData) return;
        const candle = param.seriesData.get(candlestickSeries);
        if (candle) updateLegendText(candle);
      });

      window.addEventListener('resize', () => {
        if (lightweightChart) lightweightChart.applyOptions({ width: container.clientWidth });
      });

      currentHistory = rawHistory;
      applyTimeScaleFormatting();
      renderChart(rawHistory);
      if (lightweightChart) {
        // start incremental realtime updates for the chart
        startRealtimeUpdates();
      }
    } else {
      // ----------------------------------------------------
      // ENGINE 2: HTML5 Canvas Standalone Candlestick Renderer
      // ----------------------------------------------------
      currentHistory = rawHistory;
      renderCanvasCandlestick(rawHistory);
    }

    syncIndicatorTogglesUI();
  }

  function renderLightweightData(history) {
    if (!history || history.length === 0) return;

    const candleData = [];
    const volumeData = [];
    const ma5Data = [];
    const ma10Data = [];
    const ma50Data = [];
    const bollingerUpperData = [];
    const bollingerMiddleData = [];
    const bollingerLowerData = [];
    const closes = [];

    history.forEach((item, index) => {
      if (!item.time || item.open === undefined) return;

      const open = parseFloat(item.open);
      const high = parseFloat(item.high);
      const low = parseFloat(item.low);
      const close = parseFloat(item.close);
      const volume = parseFloat(item.volume || 0);

      candleData.push({ time: item.time, open, high, low, close });

      volumeData.push({
        time: item.time,
        value: volume,
        color: close >= open ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)',
      });

      closes.push(close);

      if (index >= 4) {
        const slice5 = closes.slice(Math.max(0, index - 4), index + 1);
        const avg5 = slice5.reduce((a, b) => a + b, 0) / slice5.length;
        ma5Data.push({ time: item.time, value: avg5 });
      }

      if (index >= 9) {
        const slice10 = closes.slice(Math.max(0, index - 9), index + 1);
        const avg10 = slice10.reduce((a, b) => a + b, 0) / slice10.length;
        ma10Data.push({ time: item.time, value: avg10 });
      }

      if (index >= 49) {
        const slice50 = closes.slice(Math.max(0, index - 49), index + 1);
        const avg50 = slice50.reduce((a, b) => a + b, 0) / slice50.length;
        ma50Data.push({ time: item.time, value: avg50 });
      }

      if (index >= 19) {
        const bbSlice = closes.slice(Math.max(0, index - 19), index + 1);
        const middle = bbSlice.reduce((a, b) => a + b, 0) / bbSlice.length;
        const variance = bbSlice.reduce((sum, value) => sum + Math.pow(value - middle, 2), 0) / bbSlice.length;
        const std = Math.sqrt(variance);
        const upper = middle + (std * 2);
        const lower = middle - (std * 2);
        bollingerUpperData.push({ time: item.time, value: upper });
        bollingerMiddleData.push({ time: item.time, value: middle });
        bollingerLowerData.push({ time: item.time, value: lower });
      }
    });

    if (candleData.length > 0) {
      candlestickSeries.setData(candleData);
      volumeSeries.setData(volumeData);

      if (ma5Series) {
        ma5Series.applyOptions({ visible: showMa5 });
        ma5Series.setData(ma5Data);
      }
      if (ma10Series) {
        ma10Series.applyOptions({ visible: showMa10 });
        ma10Series.setData(ma10Data);
      }
      if (ma50Series) {
        ma50Series.applyOptions({ visible: showMa50 });
        ma50Series.setData(ma50Data);
      }

      if (bollingerUpperSeries) {
        bollingerUpperSeries.applyOptions({ visible: showBollinger });
        bollingerUpperSeries.setData(bollingerUpperData);
      }
      if (bollingerMiddleSeries) {
        bollingerMiddleSeries.applyOptions({ visible: showBollinger });
        bollingerMiddleSeries.setData(bollingerMiddleData);
      }
      if (bollingerLowerSeries) {
        bollingerLowerSeries.applyOptions({ visible: showBollinger });
        bollingerLowerSeries.setData(bollingerLowerData);
      }

      lightweightChart.timeScale().fitContent();
      updateLegendText(candleData[candleData.length - 1]);
    }
  }

    // Real-time updater: poll latest price and append/update the chart without redrawing all data
    function startRealtimeUpdates() {
      const pollMs = 5000;
      setInterval(() => {
        // Fetch latest quote (single-symbol) using existing API
        fetch(`${location.origin}/api/stock.php?stock=${encodeURIComponent(stockSymbol)}&range=${encodeURIComponent(currentRange)}&interval=${encodeURIComponent(currentInterval)}`)
          .then(r => r.json())
          .then(json => {
            const quote = json.quote || json.quotes && json.quotes[stockSymbol];
            if (!quote || !quote.history || quote.history.length === 0) return;
            const latest = quote.history[quote.history.length - 1];
            if (!latest) return;

            // Determine time format (number or string)
            const time = latest.time;
            const candle = {
              time: time,
              open: parseFloat(latest.open),
              high: parseFloat(latest.high),
              low: parseFloat(latest.low),
              close: parseFloat(latest.close)
            };

            try {
              candlestickSeries.update(candle);
              volumeSeries.update({ time: time, value: parseFloat(latest.volume || 0), color: candle.close >= candle.open ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.3)' });
              // update legend and header price if present
              updateLegendText(candle);
              const priceHeader = document.getElementById('stockPriceHeader');
              if (priceHeader) priceHeader.textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(candle.close);
            } catch (e) {
              // if update fails, ignore and rely on full redraw next interval
            }
          })
          .catch(() => {});
      }, pollMs);
    }

  function renderCanvasCandlestick(history) {
    let canvas = container.querySelector('canvas');
    if (!canvas) {
      canvas = document.createElement('canvas');
      canvas.width = container.clientWidth;
      canvas.height = 420;
      canvas.style.cssText = 'width:100%; height:100%; display:block;';
      container.appendChild(canvas);
    }

    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    ctx.fillStyle = '#181b25';
    ctx.fillRect(0, 0, width, height);

    if (!history || history.length === 0) return;

    // Calculate Min & Max Prices
    let minPrice = Infinity;
    let maxPrice = -Infinity;
    history.forEach(d => {
      if (d.low < minPrice) minPrice = d.low;
      if (d.high > maxPrice) maxPrice = d.high;
    });

    const padding = 30;
    const chartHeight = height - padding * 2;
    const candleWidth = Math.max(2, (width - padding * 2) / history.length - 4);
    const range = maxPrice - minPrice || 1;

    // Draw Grid Lines
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.08)';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
      const y = padding + (chartHeight / 4) * i;
      ctx.beginPath();
      ctx.moveTo(padding, y);
      ctx.lineTo(width - padding, y);
      ctx.stroke();
    }

    const bbValues = [];
    const ma5Values = [];
    const ma10Values = [];
    const ma50Values = [];
    history.forEach((d, index) => {
      if (d.close !== undefined) bbValues.push(parseFloat(d.close));

      if (index >= 4) {
        const slice5 = history.slice(Math.max(0, index - 4), index + 1).map(item => parseFloat(item.close));
        const avg5 = slice5.reduce((a, b) => a + b, 0) / slice5.length;
        ma5Values.push(avg5);
      } else {
        ma5Values.push(null);
      }

      if (index >= 9) {
        const slice10 = history.slice(Math.max(0, index - 9), index + 1).map(item => parseFloat(item.close));
        const avg10 = slice10.reduce((a, b) => a + b, 0) / slice10.length;
        ma10Values.push(avg10);
      } else {
        ma10Values.push(null);
      }

      if (index >= 49) {
        const slice50 = history.slice(Math.max(0, index - 49), index + 1).map(item => parseFloat(item.close));
        const avg50 = slice50.reduce((a, b) => a + b, 0) / slice50.length;
        ma50Values.push(avg50);
      } else {
        ma50Values.push(null);
      }
    });

    // Draw Candlesticks
    history.forEach((d, i) => {
      const x = padding + i * (candleWidth + 4) + candleWidth / 2;
      const openY = padding + chartHeight - ((d.open - minPrice) / range) * chartHeight;
      const closeY = padding + chartHeight - ((d.close - minPrice) / range) * chartHeight;
      const highY = padding + chartHeight - ((d.high - minPrice) / range) * chartHeight;
      const lowY = padding + chartHeight - ((d.low - minPrice) / range) * chartHeight;

      const isUp = d.close >= d.open;
      const color = isUp ? '#22c55e' : '#ef4444';

      // Draw Wick Line
      ctx.strokeStyle = color;
      ctx.beginPath();
      ctx.moveTo(x, highY);
      ctx.lineTo(x, lowY);
      ctx.stroke();

      // Draw Candle Body Box
      ctx.fillStyle = color;
      const bodyTop = Math.min(openY, closeY);
      const bodyHeight = Math.max(3, Math.abs(closeY - openY));
      ctx.fillRect(x - candleWidth / 2, bodyTop, candleWidth, bodyHeight);
    });

    const drawMovingAverageLine = (values, color, visible) => {
      if (!visible) return;
      ctx.strokeStyle = color;
      ctx.lineWidth = 1.2;
      ctx.beginPath();
      let started = false;
      history.forEach((d, i) => {
        const value = values[i];
        if (value === null || value === undefined) return;
        const x = padding + i * (candleWidth + 4) + candleWidth / 2;
        const y = padding + chartHeight - ((value - minPrice) / range) * chartHeight;
        if (!started) {
          ctx.moveTo(x, y);
          started = true;
        } else {
          ctx.lineTo(x, y);
        }
      });
      if (started) ctx.stroke();
    };

    if (showMa5) drawMovingAverageLine(ma5Values, '#22d3ee', true);
    if (showMa10) drawMovingAverageLine(ma10Values, '#a78bfa', true);
    if (showMa50) drawMovingAverageLine(ma50Values, '#60a5fa', true);

    // Draw Bollinger Bands as simple lines on canvas
    if (showBollinger && bbValues.length >= 20) {
      const bbSlice = bbValues.slice(-20);
      const middle = bbSlice.reduce((a, b) => a + b, 0) / bbSlice.length;
      const variance = bbSlice.reduce((sum, value) => sum + Math.pow(value - middle, 2), 0) / bbSlice.length;
      const std = Math.sqrt(variance);
      const upper = middle + (std * 2);
      const lower = middle - (std * 2);

      const drawBandLine = (value, color) => {
        const y = padding + chartHeight - ((value - minPrice) / range) * chartHeight;
        ctx.strokeStyle = color;
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
      };

      drawBandLine(upper, '#f59e0b');
      drawBandLine(middle, '#8b5cf6');
      drawBandLine(lower, '#f59e0b');
    }

    // Update legend
    const last = history[history.length - 1];
    updateLegendText(last);
  }

  function updateLegendText(candle) {
    if (!candle || !legendEl) return;
    const isUp = candle.close >= candle.open;
    const colorClass = isUp ? '#22c55e' : '#ef4444';
    legendEl.innerHTML = `
      <span style="color: #e2e8f0; font-weight: 700;">OHLC</span> 
      O: <span style="color:${colorClass}">${candle.open}</span> 
      H: <span style="color:${colorClass}">${candle.high}</span> 
      L: <span style="color:${colorClass}">${candle.low}</span> 
      C: <span style="color:${colorClass}">${candle.close}</span>
    `;
  }

  // Initialize Chart
  initChartEngine();

  const ma5Toggle = document.getElementById('ma5Toggle');
  if (ma5Toggle) {
    ma5Toggle.addEventListener('click', () => {
      showMa5 = !showMa5;
      syncIndicatorTogglesUI();
      if (typeof LightweightCharts !== 'undefined' && lightweightChart) {
        if (ma5Series) ma5Series.applyOptions({ visible: showMa5 });
      } else {
        renderCanvasCandlestick(currentHistory);
      }
    });
  }

  const ma10Toggle = document.getElementById('ma10Toggle');
  if (ma10Toggle) {
    ma10Toggle.addEventListener('click', () => {
      showMa10 = !showMa10;
      syncIndicatorTogglesUI();
      if (typeof LightweightCharts !== 'undefined' && lightweightChart) {
        if (ma10Series) ma10Series.applyOptions({ visible: showMa10 });
      } else {
        renderCanvasCandlestick(currentHistory);
      }
    });
  }

  const ma50Toggle = document.getElementById('ma50Toggle');
  if (ma50Toggle) {
    ma50Toggle.addEventListener('click', () => {
      showMa50 = !showMa50;
      syncIndicatorTogglesUI();
      if (typeof LightweightCharts !== 'undefined' && lightweightChart) {
        if (ma50Series) ma50Series.applyOptions({ visible: showMa50 });
      } else {
        renderCanvasCandlestick(currentHistory);
      }
    });
  }

  const bollingerToggle = document.getElementById('bollingerToggle');
  if (bollingerToggle) {
    bollingerToggle.addEventListener('click', () => {
      showBollinger = !showBollinger;
      syncIndicatorTogglesUI();

      if (typeof LightweightCharts !== 'undefined' && lightweightChart) {
        if (bollingerUpperSeries) bollingerUpperSeries.applyOptions({ visible: showBollinger });
        if (bollingerMiddleSeries) bollingerMiddleSeries.applyOptions({ visible: showBollinger });
        if (bollingerLowerSeries) bollingerLowerSeries.applyOptions({ visible: showBollinger });
      } else {
        renderCanvasCandlestick(currentHistory);
      }
    });
  }

  // ----------------------------------------------------
  // Timeframe Range Selector Handler (1D, 1W, 1M, YTD, 1Y, 3Y, 5Y)
  // ----------------------------------------------------
  const timeframeButtons = document.querySelectorAll('.timeframe-btn');

  timeframeButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Update UI active state
      timeframeButtons.forEach(b => {
        b.className = 'timeframe-btn px-2.5 py-1 rounded text-xs font-medium text-text-muted hover:text-white transition-colors';
      });
      btn.className = 'timeframe-btn px-2.5 py-1 rounded text-xs font-medium bg-primary text-white';

      currentRange = btn.getAttribute('data-range') || '1y';
      currentInterval = btn.getAttribute('data-interval') || '1d';
      applyTimeScaleFormatting();

      fetchStockData(stockSymbol, currentRange, currentInterval);
    });
  });

  // Fetch Stock Data via AJAX API
  function fetchStockData(symbol, range, interval) {
    fetch(`../api/stock.php?stock=${encodeURIComponent(symbol)}&range=${encodeURIComponent(range)}&interval=${encodeURIComponent(interval)}`)
      .then(res => res.json())
      .then(data => {
        if (data && data.status === 'success' && data.quote) {
          const q = data.quote;
          const sig = data.signal;

          // 1. Update Price Header
          const priceHeader = document.getElementById('stockPriceHeader');
          if (priceHeader) priceHeader.innerText = 'Rp ' + Number(q.price).toLocaleString('id-ID');

          const changeHeader = document.getElementById('stockChangeHeader');
          if (changeHeader) {
            const isPos = q.change >= 0;
            changeHeader.className = `font-mono ${isPos ? 'text-bullish' : 'text-bearish'} flex items-center justify-end gap-1 mt-1 text-sm bg-surface-container-low px-2 py-0.5 rounded inline-flex border border-border-subtle`;
            changeHeader.innerHTML = `
              <span class="material-symbols-outlined text-[14px]">${isPos ? 'arrow_upward' : 'arrow_downward'}</span>
              ${isPos ? '+' : ''}${q.change} (${isPos ? '+' : ''}${q.changePercent}%)
            `;
          }

          // 2. Update Chart
          if (q.history) {
            currentHistory = q.history;
            renderChart(q.history);
          }

          // 3. Update Indicators & AI Signals
          if (sig) {
            const rsiVal = document.getElementById('rsiValueDisplay');
            if (rsiVal) rsiVal.innerText = sig.rsi;

            const rsiBar = document.getElementById('rsiBarDisplay');
            if (rsiBar) rsiBar.style.width = `${Math.min(100, Math.max(0, sig.rsi))}%`;

            const macdTrend = document.getElementById('macdTrendDisplay');
            if (macdTrend) {
              macdTrend.innerHTML = `<span class="material-symbols-outlined text-[14px]">trending_up</span> ${sig.macd.trend.replace('_', ' ')}`;
            }

            const bollingerStatus = document.getElementById('bollingerStatusDisplay');
            if (bollingerStatus) bollingerStatus.innerText = sig.bollinger?.status || 'NEUTRAL';

            const bollingerUpper = document.getElementById('bollingerUpperDisplay');
            if (bollingerUpper) bollingerUpper.innerText = Number(sig.bollinger?.upper || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const bollingerMiddle = document.getElementById('bollingerMiddleDisplay');
            if (bollingerMiddle) bollingerMiddle.innerText = Number(sig.bollinger?.middle || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const bollingerLower = document.getElementById('bollingerLowerDisplay');
            if (bollingerLower) bollingerLower.innerText = Number(sig.bollinger?.lower || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const bandarmologyStatus = document.getElementById('bandarmologyStatusDisplay');
            if (bandarmologyStatus) bandarmologyStatus.innerText = sig.bandarmology?.status || 'NEUTRAL';

            const bandarmologyScore = document.getElementById('bandarmologyScoreDisplay');
            if (bandarmologyScore) bandarmologyScore.innerText = Number(sig.bandarmology?.score || 50).toLocaleString('id-ID', { maximumFractionDigits: 0 });

            const bandarmologyBuy = document.getElementById('bandarmologyBuyDisplay');
            if (bandarmologyBuy) bandarmologyBuy.innerText = Number(sig.bandarmology?.buy_power || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

            const bandarmologySell = document.getElementById('bandarmologySellDisplay');
            if (bandarmologySell) bandarmologySell.innerText = Number(sig.bandarmology?.sell_power || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });

            const ema20Display = document.getElementById('ema20Display');
            if (ema20Display) ema20Display.innerText = Number(sig.ema20 || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const stochKDisplay = document.getElementById('stochKDisplay');
            if (stochKDisplay) stochKDisplay.innerText = Number(sig.stochastic?.k || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 });

            const stochStatusDisplay = document.getElementById('stochStatusDisplay');
            if (stochStatusDisplay) stochStatusDisplay.innerText = sig.stochastic?.status || 'NEUTRAL';

            const emaStochDisplay = document.getElementById('emaStochDisplay');
            if (emaStochDisplay) emaStochDisplay.innerText = `${Number(sig.ema20 || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })} / ${Number(sig.stochastic?.k || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 })}`;

            const supportDisplay = document.getElementById('supportDisplay');
            if (supportDisplay) supportDisplay.innerText = Number(sig.support_resistance?.support || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const resistanceDisplay = document.getElementById('resistanceDisplay');
            if (resistanceDisplay) resistanceDisplay.innerText = Number(sig.support_resistance?.resistance || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

            const patternDisplay = document.getElementById('patternDisplay');
            if (patternDisplay) patternDisplay.innerText = sig.pattern?.name || 'NONE';

            const breakoutDisplay = document.getElementById('breakoutDisplay');
            if (breakoutDisplay) breakoutDisplay.innerText = sig.breakout?.status || 'CONSOLIDATION';

            const volumeDisplay = document.getElementById('volumeDisplay');
            if (volumeDisplay) volumeDisplay.innerText = sig.volume_profile?.status || 'NORMAL';

            const mtfDisplay = document.getElementById('mtfDisplay');
            if (mtfDisplay) mtfDisplay.innerText = sig.multi_timeframe?.bias || 'NEUTRAL';

            const structureStatus = document.getElementById('structureStatusDisplay');
            if (structureStatus) structureStatus.innerText = sig.breakout?.status || 'CONSOLIDATION';

            const aiSignal = document.getElementById('aiSignalDisplay');
            if (aiSignal) aiSignal.innerText = sig.signal;

            const aiConf = document.getElementById('aiConfidenceDisplay');
            if (aiConf) aiConf.innerText = `${sig.confidence}%`;

            const aiConfBar = document.getElementById('aiConfidenceBar');
            if (aiConfBar) aiConfBar.style.width = `${sig.confidence}%`;

            const aiEntry = document.getElementById('aiEntryDisplay');
            if (aiEntry) aiEntry.innerText = `${Number(sig.entry_min).toLocaleString('id-ID')} - ${Number(sig.entry_max).toLocaleString('id-ID')}`;

            const aiTp1 = document.getElementById('aiTp1Display');
            if (aiTp1) aiTp1.innerText = Number(sig.target_1).toLocaleString('id-ID');

            const aiTp2 = document.getElementById('aiTp2Display');
            if (aiTp2) aiTp2.innerText = Number(sig.target_2).toLocaleString('id-ID');

            const aiSl = document.getElementById('aiSlDisplay');
            if (aiSl) aiSl.innerText = Number(sig.stop_loss).toLocaleString('id-ID');

            const aiRr = document.getElementById('aiRrDisplay');
            if (aiRr) aiRr.innerText = sig.risk_reward;

            const aiReason = document.getElementById('aiReasoningDisplay');
            if (aiReason) aiReason.innerText = sig.reasoning;
          }
        }
      })
      .catch(err => console.error('Error fetching stock data:', err));
  }

  // Realtime Polling Engine (Fetches quote every 100ms)
  setInterval(() => {
    fetchStockData(stockSymbol, currentRange, currentInterval);
  }, 100);
});
