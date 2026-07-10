<?php
/**
 * Database migration/seed runner.
 * Usage:
 *   php cli/migrate.php           # load schema.sql
 *   php cli/migrate.php --seed    # load schema.sql then seed.sql
 *   php cli/migrate.php --fresh   # drop all tables, then schema (+ optional --seed)
 */

require __DIR__ . '/../bootstrap.php';

use App\Core\App;

$args = $argv;
$seed = in_array('--seed', $args, true);
$fresh = in_array('--fresh', $args, true);

$db = App::db();

if ($fresh) {
    echo "Dropping existing tables...\n";
    $db->pdo()->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($db->all('SHOW TABLES') as $row) {
        $table = array_values($row)[0];
        $db->pdo()->exec("DROP TABLE IF EXISTS `$table`");
        echo "  dropped $table\n";
    }
    $db->pdo()->exec('SET FOREIGN_KEY_CHECKS=1');
}

echo "Loading schema.sql...\n";
$db->pdo()->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
echo "Schema loaded.\n";

if ($seed) {
    echo "Loading seed.sql...\n";
    $db->pdo()->exec(file_get_contents(__DIR__ . '/../database/seed.sql'));
    echo "Seed data loaded.\n";
    echo "\nDefault login: admin@hotelcrm.test / Admin@123\n";
}

echo "Done.\n";
