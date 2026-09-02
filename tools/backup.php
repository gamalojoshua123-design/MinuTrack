<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backup_functions.php';
requireOwner();

$page_title = 'Database Backup';
$active_page = 'backup';

$message = '';
$message_type = '';

// Create backup directory if it doesn't exist
$backup_dir = __DIR__ . '/backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

/* =============================================
 * ACTIONS
 * ============================================= */

// CREATE BACKUP (full or custom)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    requireCsrfToken();
    try {
        $backup_type = $_POST['backup_type'] ?? 'full';

        if ($backup_type === 'custom') {
            $tables = $_POST['tables'] ?? [];
            $tables = array_map('trim', array_map('strval', (array)$tables));
            if (empty($tables)) {
                throw new Exception('Please select at least one table to backup.');
            }
        } else {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tables)) {
                throw new Exception('No tables found in the database.');
            }
        }

        $sql = generateBackupSql($pdo, $tables);
        if (trim($sql) === '') {
            throw new Exception('No backup data was generated.');
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = 'backup_' . $timestamp . '.sql';
        $filepath = $backup_dir . $filename;

        // Compress if requested
        $compress = !empty($_POST['compress']);
        if ($compress) {
            if (!function_exists('gzencode')) {
                throw new Exception('GZIP compression is not available on this server.');
            }
            $gz_content = gzencode($sql, 9);
            $filename = 'backup_' . $timestamp . '.sql.gz';
            $filepath = $backup_dir . $filename;
            if ($gz_content === false || file_put_contents($filepath, $gz_content) === false) {
                throw new Exception('Failed to write compressed backup file. Please check directory permissions.');
            }
        } else {
            if (file_put_contents($filepath, $sql) === false) {
                throw new Exception('Failed to write backup file. Please check directory permissions.');
            }
        }

        $message = 'Backup created successfully: ' . $filename;
        $message_type = 'success';

        // Auto-download if requested
        if (!empty($_POST['download'])) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// RESTORE BACKUP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    requireCsrfToken();
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        $filepath = resolveBackupFilePath($backup_dir, $backup_file);

        if ($filepath === false) {
            throw new Exception('Invalid backup file selected.');
        }

        $sql = file_get_contents($filepath);
        if ($sql === false || trim($sql) === '') {
            throw new Exception('Backup file is empty or unreadable.');
        }

        if (strtolower(pathinfo($filepath, PATHINFO_EXTENSION)) === 'gz') {
            if (!function_exists('gzdecode')) {
                throw new Exception('GZIP decompression is not available on this server.');
            }
            $sql = gzdecode($sql);
            if ($sql === false || trim($sql) === '') {
                throw new Exception('Failed to decompress backup file.');
            }
        }

        $statements = splitSqlStatements($sql);
        if (empty($statements)) {
            throw new Exception('No valid SQL statements found in the backup file.');
        }

        // NOTE: no wrapping transaction — the dump contains DDL (DROP/CREATE TABLE)
        // which MySQL implicitly commits, so a transaction cannot be used here.
        $executed = 0;
        $ignored = 0;
        $errors = [];

        foreach ($statements as $stmt) {
            try {
                $pdo->exec($stmt);
                $executed++;
            } catch (Exception $e) {
                $msg = $e->getMessage();
                // Ignore benign errors during restore
                if (strpos($msg, 'already exists') !== false ||
                    strpos($msg, 'Duplicate entry') !== false ||
                    strpos($msg, 'Duplicate column') !== false) {
                    $ignored++;
                } else {
                    $errors[] = $msg;
                }
            }
        }

        $message = "Restore completed successfully! $executed statements executed.";
        if ($ignored > 0) {
            $message .= " ($ignored benign warnings ignored.)";
        }
        if (!empty($errors)) {
            $message .= ' Warnings: ' . count($errors) . ' statement(s) could not be applied.';
        }
        $message_type = 'success';

    } catch (Exception $e) {
        $message = 'Restore failed: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// DOWNLOAD BACKUP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_backup'])) {
    requireCsrfToken();
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        $filepath = resolveBackupFilePath($backup_dir, $backup_file);

        if ($filepath === false || !is_file($filepath)) {
            throw new Exception('Backup file not found.');
        }

        $safe_name = basename($filepath);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $safe_name . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;

    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// DELETE BACKUP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    requireCsrfToken();
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        $filepath = resolveBackupFilePath($backup_dir, $backup_file);

        if ($filepath === false || !is_file($filepath)) {
            throw new Exception('Backup file not found.');
        }

        if (unlink($filepath)) {
            $message = 'Backup file deleted successfully.';
            $message_type = 'success';
        } else {
            throw new Exception('Failed to delete backup file.');
        }

    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

/* =============================================
 * DATA FOR THE PAGE
 * ============================================= */

// List of backup files
$backup_files = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext !== 'sql' && $ext !== 'gz') {
            continue;
        }
        $filepath = $backup_dir . $file;
        if (!is_file($filepath)) {
            continue;
        }
        $backup_files[] = [
            'name' => $file,
            'size' => filesize($filepath),
            'modified' => filemtime($filepath),
            'is_gz' => $ext === 'gz'
        ];
    }
    usort($backup_files, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Total size of backup files
$total_backup_size = array_sum(array_column($backup_files, 'size'));

// List of tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Table row counts
$table_counts = [];
foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
    $table_counts[$table] = (int)$stmt->fetchColumn();
}

// Total database size
$db_size = 0;
$stmt = $pdo->query("
    SELECT SUM(data_length + index_length)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
");
$db_size = (int)$stmt->fetchColumn();

// Inventory alerts for the notification panel
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= min_stock
      AND (status IS NULL OR status = 'active')
      AND deleted_at IS NULL
");
$stmt->execute();
$low_stock_total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM inventory
    WHERE quantity <= 0
      AND deleted_at IS NULL
");
$stmt->execute();
$out_of_stock_total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$total_alerts = $low_stock_total + $out_of_stock_total;

$stmt = $pdo->prepare("
    SELECT id, item_name, quantity, min_stock, unit
    FROM inventory
    WHERE quantity <= 0
      AND deleted_at IS NULL
    ORDER BY item_name ASC
    LIMIT 10
");
$stmt->execute();
$out_of_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT id, item_name, quantity, min_stock, unit
    FROM inventory
    WHERE quantity <= min_stock
      AND quantity > 0
      AND deleted_at IS NULL
    ORDER BY quantity ASC
    LIMIT 10
");
$stmt->execute();
$low_stock_notify = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Minute Burger Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* ═══════════════ PAGE-SPECIFIC: BACKUP ═══════════════ */
        .stats-grid-backup {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .backup-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .backup-actions .btn {
            padding: 0.55rem 1.2rem;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .backup-actions .btn i {
            font-size: 1rem;
        }

        .create-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .stats-grid-backup .stat-header > div { min-width: 0; }
        .stats-grid-backup .stat-value { font-size: 1.35rem; overflow-wrap: anywhere; }

        .btn-success {
            background: #10b981;
            color: white;
            border: none;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
            border: none;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .restore-form {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
            margin-left: auto;
        }

        .restore-select {
            padding: 0.6rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card);
            min-width: 240px;
            max-width: 100%;
            transition: var(--transition);
            cursor: pointer;
        }

        .restore-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.08);
        }

        .table-selector {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .table-selector .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
            max-height: 320px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }

        .table-selector label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            padding: 0.3rem 0.5rem;
            border-radius: 6px;
            transition: var(--transition);
            cursor: pointer;
        }

        .table-selector label:hover {
            background: var(--bg-card);
        }

        .table-selector input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--primary);
            flex-shrink: 0;
        }

        .table-selector .select-all {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .table-selector .table-count {
            font-size: 0.7rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .backup-files .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            transition: var(--transition);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .backup-files .file-item:hover {
            background: var(--bg);
        }

        .backup-files .file-item:last-child {
            border-bottom: none;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 200px;
        }

        .file-info .file-icon {
            font-size: 1.5rem;
            color: var(--text-muted);
            flex-shrink: 0;
        }

        .file-info .file-details {
            min-width: 0;
        }

        .file-info .file-name {
            font-weight: 600;
            color: var(--text-primary);
            word-break: break-all;
        }

        .file-info .file-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.15rem;
        }

        .file-info .file-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .file-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .file-actions .btn {
            padding: 0.3rem 0.8rem;
            font-size: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.75rem;
            display: block;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 0.9rem;
            margin: 0;
        }

        /* ═══════════════ NOTIFICATION PANEL ═══════════════ */
        .notification-panel {
            position: fixed;
            top: 70px;
            right: 20px;
            width: 380px;
            max-width: 90vw;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            z-index: 2000;
            display: none;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .notification-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h4 {
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .notification-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .notification-close:hover {
            background: rgba(255,255,255,0.3);
        }

        .notification-body {
            max-height: 450px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
            gap: 0.5rem;
        }

        .notification-item:hover {
            background: var(--bg);
        }

        .notification-item.critical {
            border-left: 3px solid var(--red);
            background: var(--red-light);
        }

        .notification-item.warning {
            border-left: 3px solid var(--amber);
            background: var(--amber-light);
        }

        .notification-item .item-info {
            flex: 1;
            min-width: 0;
        }

        .notification-item .item-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .notification-item .item-stock {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .notification-item .stock-critical {
            color: var(--red);
            font-weight: 600;
        }

        .notification-item .stock-warning {
            color: var(--amber);
            font-weight: 600;
        }

        .notification-item .update-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            flex-shrink: 0;
        }

        .notification-item .update-btn:hover {
            background: var(--primary-dark);
        }

        .empty-notification {
            text-align: center;
            padding: 2.5rem;
            color: var(--text-secondary);
        }

        .empty-notification i {
            font-size: 2.5rem;
            color: var(--green);
            margin-bottom: 0.5rem;
        }

        .empty-notification p {
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
        }

        .backup-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .backup-options label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        .backup-options input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        @media (max-width: 1024px) {
            .stats-grid-backup {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid-backup {
                grid-template-columns: 1fr 1fr;
            }
            .backup-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .create-actions {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }
            .create-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .backup-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .restore-form {
                flex-direction: column;
                align-items: stretch;
                margin-left: 0;
                width: 100%;
            }
            .restore-select {
                width: 100%;
            }
            .table-selector .grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            .file-item {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
            .file-actions {
                width: 100%;
            }
            .file-actions .btn {
                flex: 1;
                justify-content: center;
            }
            .notification-panel {
                top: 70px;
                right: 10px;
                width: calc(100% - 20px);
            }
        }

        @media (max-width: 480px) {
            .stats-grid-backup {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-database'></i> Database Backup</h3>
                    </div>
                    <div class="card-body">

                        <!-- Stats -->
                        <div class="stats-grid-backup">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background:var(--blue-light);color:var(--blue);"><i class='bx bx-hdd'></i></div>
                                    <div>
                                        <div class="stat-title">Total DB Size</div>
                                        <div class="stat-value"><?php echo formatBytes($db_size); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background:var(--green-light);color:var(--green);"><i class='bx bx-table'></i></div>
                                    <div>
                                        <div class="stat-title">Total Tables</div>
                                        <div class="stat-value"><?php echo count($tables); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background:var(--amber-light);color:var(--amber);"><i class='bx bx-file-blank'></i></div>
                                    <div>
                                        <div class="stat-title">Backup Files</div>
                                        <div class="stat-value"><?php echo count($backup_files); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background:var(--blue-light);color:var(--blue);"><i class='bx bx-data'></i></div>
                                    <div>
                                        <div class="stat-title">Total Backup Size</div>
                                        <div class="stat-value"><?php echo formatBytes($total_backup_size); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Backup Actions -->
                        <div class="backup-actions">
                            <div class="create-actions">
                                <form method="POST" style="display:inline;" id="backup-form">
                                    <?= csrfField() ?>
                                    <button type="submit" name="create_backup" value="1" class="btn btn-success">
                                        <i class='bx bx-save'></i> Create Full Backup
                                    </button>
                                </form>
                                <button class="btn btn-warning" onclick="showCustomBackup()">
                                    <i class='bx bx-select-multiple'></i> Custom Backup
                                </button>
                            </div>
                            <form method="POST" class="restore-form" onsubmit="return confirmRestore(event)">
                                <?= csrfField() ?>
                                <select name="backup_file" class="restore-select" id="restore-select">
                                    <option value="">Select backup to restore...</option>
                                    <?php foreach ($backup_files as $file): ?>
                                        <option value="<?php echo htmlspecialchars($file['name']); ?>">
                                            <?php echo htmlspecialchars($file['name']); ?>
                                            (<?php echo formatBytes($file['size']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="restore_backup" value="1" class="btn btn-danger">
                                    <i class='bx bx-undo'></i> Restore Selected
                                </button>
                            </form>
                        </div>

                        <!-- Custom Backup Modal -->
                        <div class="modal" id="custom-backup-modal">
                            <div class="modal-content" style="max-width:600px;">
                                <div class="modal-header">
                                    <h3 class="modal-title"><i class='bx bx-select-multiple'></i> Custom Backup</h3>
                                    <button class="modal-close" aria-label="Close modal" onclick="closeModal('custom-backup-modal')"><i class='bx bx-x'></i></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="custom-backup-form">
                                        <?= csrfField() ?>
                                        <div class="table-selector">
                                            <label class="select-all">
                                                <input type="checkbox" id="select-all-tables" onchange="toggleAllTables()">
                                                Select All Tables
                                                <span class="table-count">(<?php echo count($tables); ?> tables)</span>
                                            </label>
                                            <div class="grid" id="table-grid">
                                                <?php foreach ($tables as $table): ?>
                                                    <label>
                                                        <input type="checkbox" name="tables[]" value="<?php echo htmlspecialchars($table); ?>" class="table-checkbox">
                                                        <?php echo htmlspecialchars($table); ?>
                                                        <span class="table-count"><?php echo number_format($table_counts[$table] ?? 0); ?> rows</span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="backup-options">
                                            <label>
                                                <input type="checkbox" name="compress" value="1">
                                                Compress (GZIP)
                                            </label>
                                            <label>
                                                <input type="checkbox" name="download" value="1" checked>
                                                Download after creation
                                            </label>
                                        </div>
                                        <input type="hidden" name="backup_type" value="custom">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-outline" onclick="closeModal('custom-backup-modal')">Cancel</button>
                                    <button class="btn btn-success" onclick="submitCustomBackup()">
                                        <i class='bx bx-save'></i> Create Backup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backup Files -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class='bx bx-folder-open'></i> Backup Files</h3>
                        <?php if (!empty($backup_files)): ?>
                            <span style="font-size:0.8rem;color:var(--text-secondary);">
                                Total: <?php echo formatBytes($total_backup_size); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backup_files)): ?>
                            <div class="empty-state">
                                <i class='bx bx-file-blank'></i>
                                <p>No backup files found. Create your first backup using the buttons above.</p>
                            </div>
                        <?php else: ?>
                            <div class="backup-files">
                                <?php foreach ($backup_files as $file): ?>
                                    <div class="file-item">
                                        <div class="file-info">
                                            <span class="file-icon">
                                                <i class='bx <?php echo $file['is_gz'] ? 'bx-archive' : 'bx-file'; ?>'></i>
                                            </span>
                                            <div class="file-details">
                                                <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                                                <div class="file-meta">
                                                    <span>
                                                        <i class='bx bx-calendar'></i>
                                                        <?php echo date('F d, Y h:i A', $file['modified']); ?>
                                                    </span>
                                                    <span>
                                                        <i class='bx bx-hdd'></i>
                                                        <?php echo formatBytes($file['size']); ?>
                                                    </span>
                                                    <?php if ($file['is_gz']): ?>
                                                        <span><i class='bx bx-archive'></i> Compressed</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            <form method="POST" style="display:inline;">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($file['name']); ?>">
                                                <button type="submit" name="download_backup" value="1" class="btn btn-edit" title="Download">
                                                    <i class='bx bx-download'></i> Download
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return askConfirm(event, 'Delete this backup file? This cannot be undone.')">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($file['name']); ?>">
                                                <button type="submit" name="delete_backup" value="1" class="btn btn-danger" aria-label="Delete backup">
                                                    <i class='bx bx-trash'></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Panel -->
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h4><i class='bx bx-bell'></i> Inventory Alerts</h4>
                <button class="notification-close" aria-label="Close notifications" onclick="closeNotificationPanel()"><i class='bx bx-x'></i></button>
            </div>
            <div class="notification-body" id="notificationBody"></div>
        </div>
    </div>

    <script>
        // Store alert data
        const alertData = {
            outOfStock: <?php echo json_encode($out_of_stock_items); ?>,
            lowStock: <?php echo json_encode($low_stock_notify); ?>,
            totalAlerts: <?php echo $total_alerts; ?>
        };

        let notifPanel = document.getElementById('notificationPanel');
        let notifVisible = false;

        // Render notification panel
        function renderNotificationPanel() {
            const body = document.getElementById('notificationBody');
            if (!body) return;

            let html = '';

            if (alertData.outOfStock && alertData.outOfStock.length > 0) {
                alertData.outOfStock.forEach(item => {
                    html += `
                        <div class="notification-item critical">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.item_name)}</div>
                                <div class="item-stock">Stock: <span class="stock-critical">0 ${escapeHtml(item.unit || 'piece')}</span> (Min: ${item.min_stock})</div>
                            </div>
                            <button class="update-btn" onclick="goToInventory(${item.id})">Update</button>
                        </div>
                    `;
                });
            }

            if (alertData.lowStock && alertData.lowStock.length > 0) {
                alertData.lowStock.forEach(item => {
                    html += `
                        <div class="notification-item warning">
                            <div class="item-info">
                                <div class="item-name">${escapeHtml(item.item_name)}</div>
                                <div class="item-stock">Stock: <span class="stock-warning">${item.quantity} ${escapeHtml(item.unit || 'piece')}</span> (Min: ${item.min_stock})</div>
                            </div>
                            <button class="update-btn" onclick="goToInventory(${item.id})">Update</button>
                        </div>
                    `;
                });
            }

            if (!html) {
                html = '<div class="empty-notification"><i class="bx bx-check-circle"></i><p>All inventory items are well stocked!</p></div>';
            }

            body.innerHTML = html;
        }

        function goToInventory(id) {
            closeNotificationPanel();
            window.location.href = '../inventory/inventory.php';
        }

        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            if (notifVisible) {
                panel.style.display = 'none';
                notifVisible = false;
            } else {
                renderNotificationPanel();
                panel.style.display = 'block';
                notifVisible = true;
            }
        }

        function closeNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            if (panel) {
                panel.style.display = 'none';
                notifVisible = false;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Custom Backup Functions
        function showCustomBackup() {
            document.getElementById('custom-backup-modal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function toggleAllTables() {
            const checked = document.getElementById('select-all-tables').checked;
            document.querySelectorAll('.table-checkbox').forEach(cb => {
                cb.checked = checked;
            });
        }

        function submitCustomBackup() {
            const checked = document.querySelectorAll('.table-checkbox:checked');
            if (checked.length === 0) {
                showToastMsg('Please select at least one table to backup.', 'warning');
                return;
            }
            document.getElementById('custom-backup-form').submit();
        }

        function confirmRestore(event) {
            const select = document.getElementById('restore-select');
            if (!select.value) {
                showToastMsg('Please select a backup file to restore first.', 'warning');
                return false;
            }
            return askConfirm(event, 'WARNING: This will restore the database to the selected backup. All current data will be replaced. Are you sure?');
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        // Close notification panel when clicking outside
        document.addEventListener('click', function(e) {
            const bell = document.querySelector('.notification-bell');
            if (notifVisible && !notifPanel.contains(e.target) && !bell?.contains(e.target)) {
                closeNotificationPanel();
            }
        });

        // Update notification badge using header.php's badge ID
        document.addEventListener('DOMContentLoaded', function() {
            const badge = document.getElementById('alert-count-badge');
            if (badge && alertData.totalAlerts > 0) {
                badge.textContent = alertData.totalAlerts;
                badge.style.display = 'flex';
            } else if (badge) {
                badge.style.display = 'none';
            }
        });
    </script>
    <?php include __DIR__ . '/../includes/confirm_modal.php'; ?>
</body>
</html>
