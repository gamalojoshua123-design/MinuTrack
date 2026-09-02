<?php
/*
 * =============================================
 * SHARED DATABASE BACKUP HELPERS
 * Minute Burger POS
 *
 * Requires a PDO connection to be available as $pdo.
 * Used by tools/backup.php (manual backups) and by the
 * automatic backup hooks on logout / shift end.
 * =============================================
 */

if (!function_exists('getBackupDir')) {
    function getBackupDir()
    {
        return __DIR__ . '/../tools/backups/';
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $decimals = 2)
    {
        $bytes = (float)$bytes;
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        return number_format($bytes / pow(1024, $power), $decimals) . ' ' . $units[$power];
    }
}

if (!function_exists('generateBackupSql')) {
    // Generate a valid SQL dump for the given tables (must already be whitelisted)
    function generateBackupSql($pdo, $tables)
    {
        $valid_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $sql = "-- ==========================================\n";
        $sql .= "-- Minute Burger Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Tables: " . implode(', ', $tables) . "\n";
        $sql .= "-- ==========================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            $table = trim($table);
            if (empty($table) || !in_array($table, $valid_tables, true)) {
                continue;
            }

            // Structure
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $create = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql .= "-- ------------------------------------------------------\n";
            $sql .= "-- Table structure for table `$table`\n";
            $sql .= "-- ------------------------------------------------------\n\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create['Create Table'] . ";\n\n";

            // Data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sql .= "-- ------------------------------------------------------\n";
                $sql .= "-- Dumping data for table `$table`\n";
                $sql .= "-- ------------------------------------------------------\n\n";

                $columns = array_keys($rows[0]);
                $column_list = implode('`, `', $columns);

                $inserts = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($columns as $col) {
                        $value = $row[$col];
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_int($value) || is_float($value)) {
                            $values[] = $value;
                        } else {
                            $quoted = $pdo->quote((string)$value);
                            $values[] = $quoted === false ? "''" : $quoted;
                        }
                    }
                    $inserts[] = "(" . implode(', ', $values) . ")";
                }

                $sql .= "INSERT INTO `$table` (`$column_list`) VALUES\n";
                $sql .= implode(",\n", $inserts);
                $sql .= ";\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $sql;
    }
}

if (!function_exists('splitSqlStatements')) {
    // Robustly split an SQL dump into individual statements
    function splitSqlStatements($sql)
    {
        $statements = [];
        $current = '';
        $len = strlen($sql);
        $in_single = false;
        $in_double = false;
        $in_backtick = false;
        $in_line_comment = false;
        $in_block_comment = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

            if (!$in_single && !$in_double && !$in_backtick && !$in_block_comment) {
                if ($ch === '#' || ($ch === '-' && $next === '-')) {
                    $in_line_comment = true;
                    $current .= $ch;
                    continue;
                }
            }

            if ($in_line_comment) {
                $current .= $ch;
                if ($ch === "\n") {
                    $in_line_comment = false;
                }
                continue;
            }

            if (!$in_single && !$in_double && !$in_backtick && !$in_line_comment) {
                if ($ch === '/' && $next === '*') {
                    $in_block_comment = true;
                    $current .= '/*';
                    $i++;
                    continue;
                }
            }

            if ($in_block_comment) {
                $current .= $ch;
                if ($ch === '*' && $next === '/') {
                    $current .= '/';
                    $i++;
                    $in_block_comment = false;
                }
                continue;
            }

            if ($in_single) {
                $current .= $ch;
                if ($ch === '\\' && $next !== '') {
                    $current .= $next;
                    $i++;
                    continue;
                }
                if ($ch === "'") {
                    $in_single = false;
                }
                continue;
            }

            if ($in_double) {
                $current .= $ch;
                if ($ch === '\\' && $next !== '') {
                    $current .= $next;
                    $i++;
                    continue;
                }
                if ($ch === '"') {
                    $in_double = false;
                }
                continue;
            }

            if ($in_backtick) {
                $current .= $ch;
                if ($ch === '`') {
                    $in_backtick = false;
                }
                continue;
            }

            if ($ch === "'") {
                $in_single = true;
                $current .= $ch;
                continue;
            }
            if ($ch === '"') {
                $in_double = true;
                $current .= $ch;
                continue;
            }
            if ($ch === '`') {
                $in_backtick = true;
                $current .= $ch;
                continue;
            }
            if ($ch === ';') {
                $current = trim($current);
                if ($current !== '') {
                    $statements[] = $current;
                }
                $current = '';
                continue;
            }
            $current .= $ch;
        }

        $current = trim($current);
        if ($current !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}

if (!function_exists('resolveBackupFilePath')) {
    // Validate that a file name points to an allowed backup file (no path traversal)
    function resolveBackupFilePath($backup_dir, $name)
    {
        $name = basename(trim($name));
        if ($name === '' || $name === '.' || $name === '..') {
            return false;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext !== 'sql' && $ext !== 'gz') {
            return false;
        }
        $real_dir = realpath($backup_dir);
        $real_file = realpath($backup_dir . $name);
        if ($real_dir === false || $real_file === false) {
            return false;
        }
        if (strpos($real_file, $real_dir . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }
        return $backup_dir . $name;
    }
}

if (!function_exists('createFullBackup')) {
    /*
     * Create a full SQL backup of the entire database.
     *
     * $label: short label embedded in the filename, e.g. 'logout', 'shiftend'.
     *         Only [a-zA-Z0-9_-] characters are kept.
     *
     * Returns: ['success' => bool, 'filename' => ?string, 'error' => ?string]
     */
    function createFullBackup($pdo, $label = 'manual')
    {
        $result = ['success' => false, 'filename' => null, 'error' => null];

        try {
            $backup_dir = getBackupDir();
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            if (!is_dir($backup_dir) || !is_writable($backup_dir)) {
                $result['error'] = 'Backup directory is not writable.';
                return $result;
            }

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tables)) {
                $result['error'] = 'No tables found in the database.';
                return $result;
            }

            $sql = generateBackupSql($pdo, $tables);
            if (trim($sql) === '') {
                $result['error'] = 'No backup data was generated.';
                return $result;
            }

            $label = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$label);
            $label = $label !== '' ? $label : 'auto';

            $filename = 'auto_' . $label . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backup_dir . $filename;

            if (file_put_contents($filepath, $sql) === false) {
                $result['error'] = 'Failed to write backup file.';
                return $result;
            }

            $result['success'] = true;
            $result['filename'] = $filename;

            // Enforce retention so automatic backups do not grow forever.
            cleanupAutoBackups($backup_dir);

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }
}

if (!function_exists('cleanupAutoBackups')) {
    /*
     * Keep only the most recent automatic (auto_*.sql) backups.
     * Manual backups (backup_*.sql) are never removed.
     *
     * $keep: number of newest auto backups to retain.
     */
    function cleanupAutoBackups($backup_dir, $keep = 30)
    {
        if (!is_dir($backup_dir)) {
            return;
        }
        $files = glob($backup_dir . 'auto_*.sql');
        if (!$files) {
            return;
        }
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        foreach (array_slice($files, (int)$keep) as $old) {
            @unlink($old);
        }
    }
}

if (!function_exists('ensureBackupTokenTable')) {
    /*
     * Idempotently create the table used to hold one-time download tokens.
     */
    function ensureBackupTokenTable($pdo)
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS backup_download_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token CHAR(64) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                UNIQUE KEY uniq_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}

if (!function_exists('createBackupDownloadToken')) {
    /*
     * Generate a random one-time download token bound to a backup filename.
     *
     * $ttl_minutes: how many minutes the token stays valid.
     *
     * Returns: token string, or null on failure.
     */
    function createBackupDownloadToken($pdo, $filename, $ttl_minutes = 15)
    {
        try {
            ensureBackupTokenTable($pdo);

            // Purge expired tokens so the table stays small.
            $pdo->prepare("DELETE FROM backup_download_tokens WHERE expires_at < NOW()")->execute();

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + ((int)$ttl_minutes * 60));

            $stmt = $pdo->prepare(
                "INSERT INTO backup_download_tokens (token, filename, expires_at) VALUES (?, ?, ?)"
            );
            $stmt->execute([$token, $filename, $expires]);

            return $token;
        } catch (Exception $e) {
            error_log('createBackupDownloadToken failed: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('isBackupDownloadTokenValid')) {
    /*
     * Check (without consuming) whether a token is still valid for the given file.
     */
    function isBackupDownloadTokenValid($pdo, $token, $filename)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        try {
            ensureBackupTokenTable($pdo);
            $stmt = $pdo->prepare(
                "SELECT 1 FROM backup_download_tokens
                 WHERE token = ? AND filename = ? AND expires_at > NOW()
                 LIMIT 1"
            );
            $stmt->execute([$token, $filename]);
            return (bool)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('isBackupDownloadTokenValid failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('consumeBackupDownloadToken')) {
    /*
     * Validate a token and mark it used in a single step.
     *
     * Returns: the bound filename on success, or false if the token is
     *          invalid, expired, or already used.
     */
    function consumeBackupDownloadToken($pdo, $token)
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        try {
            ensureBackupTokenTable($pdo);
            $stmt = $pdo->prepare(
                "SELECT filename FROM backup_download_tokens
                 WHERE token = ? AND expires_at > NOW()
                 LIMIT 1"
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }
            // One-time use: delete the token immediately.
            $del = $pdo->prepare("DELETE FROM backup_download_tokens WHERE token = ?");
            $del->execute([$token]);
            return $row['filename'];
        } catch (Exception $e) {
            error_log('consumeBackupDownloadToken failed: ' . $e->getMessage());
            return false;
        }
    }
}
