<?php
header('Content-Type: application/json');

$baseDir = dirname(__DIR__) . '/';
$runtimeDir = $baseDir . 'runtime';
if (!is_dir($runtimeDir)) {
    @mkdir($runtimeDir, 0755, true);
}
$logbookPath = $runtimeDir . '/logbook.json';

function readLogbook(string $path): array
{
    $json = @file_get_contents($path);
    if (!$json) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function writeLogbook(string $path, array $data): bool
{
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function normalizeRow(array $row, array $headerMap): array
{
    $result = [
        'date' => '',
        'stock' => '',
        'action' => '',
        'avg_price' => '',
        'pnl' => '',
        'tags' => '',
        'notes' => '',
    ];

    foreach ($row as $index => $value) {
        $key = $headerMap[$index] ?? null;
        if ($key === null) {
            continue;
        }
        $value = trim((string)$value);
        if ($key === 'avg_price' || $key === 'pnl') {
            $value = str_replace(['Rp', ',', '.'], '', $value);
            $value = is_numeric($value) ? (float)$value : 0;
        }
        $result[$key] = $value;
    }

    return $result;
}

function mapHeader(array $header): array
{
    $map = [];
    foreach ($header as $index => $value) {
        $normalized = strtolower(trim((string)$value));
        switch ($normalized) {
            case 'date':
            case 'tanggal':
            case 'time':
            case 'datetime':
            case 'date_time':
                $map[$index] = 'date';
                break;
            case 'symbol':
            case 'stock':
            case 'ticker':
                $map[$index] = 'stock';
                break;
            case 'action':
            case 'side':
                $map[$index] = 'action';
                break;
            case 'avg_price':
            case 'price':
            case 'avg price':
            case 'harga':
            case 'price_avg':
                $map[$index] = 'avg_price';
                break;
            case 'pnl':
            case 'profit':
            case 'profit_loss':
            case 'net pnl':
            case 'net pl':
            case 'pl':
                $map[$index] = 'pnl';
                break;
            case 'tags':
            case 'tag':
            case 'setup':
                $map[$index] = 'tags';
                break;
            case 'notes':
            case 'note':
            case 'comment':
                $map[$index] = 'notes';
                break;
            default:
                break;
        }
    }
    return $map;
}

function parseCsvFile(string $path): array
{
    $rows = [];
    if (($handle = fopen($path, 'r')) === false) {
        return [];
    }
    while (($data = fgetcsv($handle, 0, ',')) !== false) {
        $rows[] = $data;
    }
    fclose($handle);
    return $rows;
}

function parseXlsxFile(string $path): array
{
    require_once __DIR__ . '/../classes/SimpleXLSX.php';
    $xlsx = SimpleXLSX::parse($path);
    if (!$xlsx) {
        return [];
    }
    return $xlsx->rows(0);
}

function parseImportedRows(array $rows): array
{
    if (empty($rows)) {
        return [];
    }

    $header = array_map('strtolower', array_map('trim', $rows[0]));
    $hasHeader = count(array_filter($header, fn($value) => in_array($value, ['date', 'tanggal', 'time', 'symbol', 'stock', 'action', 'price', 'pnl', 'notes', 'tags', 'side']), true)) >= 4;

    $entries = [];
    $headerMap = [];
    if ($hasHeader) {
        $headerMap = mapHeader($header);
        $rows = array_slice($rows, 1);
    } else {
        $headerMap = [
            0 => 'date',
            1 => 'stock',
            2 => 'action',
            3 => 'avg_price',
            4 => 'pnl',
            5 => 'tags',
            6 => 'notes',
        ];
    }

    foreach ($rows as $row) {
        if (!is_array($row) || count(array_filter($row, fn($value) => trim((string)$value) !== '')) === 0) {
            continue;
        }
        $entry = normalizeRow($row, $headerMap);
        if ($entry['stock'] === '' || $entry['action'] === '') {
            continue;
        }
        $entry['date'] = $entry['date'] ?: date('Y-m-d H:i');
        $entries[] = $entry;
    }

    return $entries;
}

$action = $_REQUEST['action'] ?? 'load';

switch ($action) {
    case 'load':
        echo json_encode(['ok' => true, 'entries' => readLogbook($logbookPath)]);
        exit;
    case 'clear':
        writeLogbook($logbookPath, []);
        echo json_encode(['ok' => true]);
        exit;
    case 'save':
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body) || !isset($body['entry']) || !is_array($body['entry'])) {
            echo json_encode(['ok' => false, 'error' => 'Invalid payload.']);
            exit;
        }

        $rawEntry = $body['entry'];
        $entry = [
            'date' => trim((string)($rawEntry['date'] ?? '')),
            'stock' => strtoupper(trim((string)($rawEntry['stock'] ?? ''))),
            'action' => strtoupper(trim((string)($rawEntry['action'] ?? ''))),
            'avg_price' => is_numeric($rawEntry['avg_price'] ?? '') ? (float)$rawEntry['avg_price'] : 0,
            'pnl' => is_numeric($rawEntry['pnl'] ?? '') ? (float)$rawEntry['pnl'] : 0,
            'tags' => trim((string)($rawEntry['tags'] ?? '')),
            'notes' => trim((string)($rawEntry['notes'] ?? '')),
        ];

        if ($entry['date'] === '') {
            $entry['date'] = date('Y-m-d H:i');
        }

        if ($entry['stock'] === '' || $entry['action'] === '') {
            echo json_encode(['ok' => false, 'error' => 'Stock and action are required.']);
            exit;
        }

        $entries = readLogbook($logbookPath);
        $entries[] = $entry;
        writeLogbook($logbookPath, $entries);
        echo json_encode(['ok' => true, 'entry' => $entry]);
        exit;
    case 'import':
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'error' => 'No file uploaded.']);
            exit;
        }

        $tmpName = $_FILES['file']['tmp_name'];
        $fileName = strtolower($_FILES['file']['name']);
        $rows = [];

        if (str_ends_with($fileName, '.csv')) {
            $rows = parseCsvFile($tmpName);
        } elseif (str_ends_with($fileName, '.xlsx')) {
            $rows = parseXlsxFile($tmpName);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Only CSV and XLSX files are supported.']);
            exit;
        }

        $entries = parseImportedRows($rows);
        if (empty($entries)) {
            echo json_encode(['ok' => false, 'error' => 'Could not parse any valid rows from the file.']);
            exit;
        }

        $existing = readLogbook($logbookPath);
        $merged = array_merge($existing, $entries);
        writeLogbook($logbookPath, $merged);
        echo json_encode(['ok' => true, 'imported' => count($entries), 'entries' => $entries]);
        exit;
    default:
        echo json_encode(['ok' => false, 'error' => 'Unsupported action.']);
        exit;
}
