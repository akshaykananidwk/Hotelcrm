<?php
namespace App\Services;

use App\Core\App;

/**
 * Database backup & restore. Produces a portable SQL dump of every table using
 * only PDO (no shell / mysqldump dependency), so it works on shared hosting.
 */
class BackupService
{
    public function create(): string
    {
        $db = App::db();
        $dir = App::config('paths.backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tables = array_map(
            fn ($r) => array_values($r)[0],
            $db->all('SHOW TABLES')
        );

        $sql = "-- HotelCRM backup " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $table) {
            $create = $db->first("SHOW CREATE TABLE `$table`");
            $sql .= "DROP TABLE IF EXISTS `$table`;\n" . ($create['Create Table'] ?? '') . ";\n\n";
            $rows = $db->all("SELECT * FROM `$table`");
            foreach ($rows as $row) {
                $cols = '`' . implode('`,`', array_keys($row)) . '`';
                $vals = implode(',', array_map(function ($v) use ($db) {
                    return $v === null ? 'NULL' : $db->pdo()->quote((string) $v);
                }, array_values($row)));
                $sql .= "INSERT INTO `$table` ($cols) VALUES ($vals);\n";
            }
            $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $file = $dir . '/backup-' . date('Ymd-His') . '.sql';
        file_put_contents($file, $sql);
        return $file;
    }

    /** Restore from a dump file. */
    public function restore(string $file): void
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Backup file not found.');
        }
        App::db()->pdo()->exec((string) file_get_contents($file));
    }
}
