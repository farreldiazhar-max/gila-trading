<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' . APP_NAME : APP_NAME . ' — Platform Analisis Saham Indonesia'; ?></title>
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "text-primary": "#e2e8f0",
            "surface-container": "#1c1f29",
            "border-subtle": "rgba(148, 163, 184, 0.1)",
            "surface": "#0a0e17",
            "bearish": "#ef4444",
            "bullish": "#22c55e",
            "warning": "#f59e0b",
            "primary": "#3b82f6",
            "primary-container": "#3b82f6",
            "on-primary-container": "#ffffff",
            "text-muted": "#94a3b8",
            "surface-container-low": "#181b25",
            "surface-container-high": "#262a34",
            "surface-container-highest": "#31353f",
            "surface-container-lowest": "#0a0e17",
            "background": "#0a0e17"
          },
          fontFamily: {
            "body-md": ["Inter", "sans-serif"],
            "headline-lg": ["Inter", "sans-serif"],
            "data-mono": ["JetBrains Mono", "monospace"],
            "label-caps": ["Inter", "sans-serif"]
          }
        }
      }
    }
  </script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css"/>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/components.css"/>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css"/>

  <!-- TradingView Lightweight Charts (Primary & Fallback CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/lightweight-charts@4.1.1/dist/lightweight-charts.standalone.production.js"></script>
  <script>if (typeof LightweightCharts === 'undefined') { document.write('<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"><\/script>'); }</script>
</head>
<body class="bg-background font-body-md text-on-surface">
<div class="app-layout">
