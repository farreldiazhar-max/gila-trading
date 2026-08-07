<?php
// Startup safety: ensure cache directories exist so restarts don't fail due to missing folders
$rootCache = __DIR__ . '/cache';
$aiCache = $rootCache . '/ai_recommendation';
if (!is_dir($rootCache)) {
	@mkdir($rootCache, 0777, true);
}
if (!is_dir($aiCache)) {
	@mkdir($aiCache, 0777, true);
}

require_once __DIR__ . '/config/app.php';
header('Location: ' . BASE_URL . 'pages/dashboard.php');
exit;
