<?php
$currentPage = isset($activePage) ? $activePage : 'dashboard';
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo-icon">SP</div>
    <span class="sidebar-title">Gila Trading</span>
  </div>
  <nav class="sidebar-nav">
    <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
      <span class="material-symbols-outlined">dashboard</span>
      <span>Dashboard</span>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/screener.php" class="nav-item <?php echo $currentPage === 'screener' ? 'active' : ''; ?>">
      <span class="material-symbols-outlined">filter_list</span>
      <span>Screener</span>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/analysis.php" class="nav-item <?php echo $currentPage === 'analysis' ? 'active' : ''; ?>">
      <span class="material-symbols-outlined">insights</span>
      <span>Analysis</span>
    </a>
    <a href="<?php echo BASE_URL; ?>pages/signals.php" class="nav-item <?php echo $currentPage === 'signals' ? 'active' : ''; ?>">
      <span class="material-symbols-outlined">sensors</span>
      <span>Signals</span>
    </a>
  </nav>
</aside>

<div class="main-wrapper">
  <header class="header-bar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="text" id="globalSearchInput" placeholder="Search markets (e.g. BBCA, TLKM)" />
    </div>
    
    <div class="header-right">
      <div class="market-status-badge" id="marketStatusBadge">
        <span class="status-dot" id="marketStatusDot"></span>
        <span id="marketStatusText">MARKET OPEN</span>
      </div>

      <button class="text-text-muted hover:text-white transition-colors" title="Notifications">
        <span class="material-symbols-outlined">notifications</span>
      </button>

      <div class="profile-widget">
        <div class="profile-info">
          <div class="profile-name">Trader Alpha</div>
          <div class="profile-tag">PRO ACCOUNT</div>
        </div>
        <div class="profile-avatar">TA</div>
      </div>
    </div>
  </header>
  <main class="main-content">
