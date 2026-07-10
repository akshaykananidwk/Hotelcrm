<?php
namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Thin PDO wrapper providing a single shared connection and convenience
 * helpers. Every query is parameterised with prepared statements, giving us
 * SQL-injection protection by construction.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(array $cfg)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'] ?? 3306,
            $cfg['name'],
            $cfg['charset'] ?? 'utf8mb4'
        );

        try {
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // Never leak credentials in the message.
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /** Get (and lazily create) the shared instance. */
    public static function instance(?array $cfg = null): Database
    {
        if (self::$instance === null) {
            if ($cfg === null) {
                $cfg = App::config('db');
            }
            self::$instance = new self($cfg);
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Run a parameterised statement and return the PDOStatement. */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row (associative array) or null. */
    public function first(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch all matching rows. */
    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** Fetch a single scalar column from the first row. */
    public function scalar(string $sql, array $params = [])
    {
        $val = $this->run($sql, $params)->fetchColumn();
        return $val === false ? null : $val;
    }

    /** Insert helper — returns the new auto-increment id. */
    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $place = array_map(fn ($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`,`', $cols),
            implode(',', $place)
        );
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    /** Update helper — returns affected row count. */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(',', array_map(fn ($c) => "`$c` = :$c", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE $where";
        return $this->run($sql, array_merge($data, $whereParams))->rowCount();
    }

    /** Delete helper — returns affected row count. */
    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->run("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); }
}
