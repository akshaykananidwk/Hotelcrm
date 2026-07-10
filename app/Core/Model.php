<?php
namespace App\Core;

/**
 * Lightweight active-record-ish base model. Subclasses declare $table and
 * (optionally) $fillable. Scoping by hotel_id is handled by callers to keep
 * multi-tenant queries explicit and auditable.
 */
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    protected function db(): Database
    {
        return App::db();
    }

    public function find($id): ?array
    {
        return $this->db()->first(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    public function findBy(string $column, $value): ?array
    {
        return $this->db()->first(
            "SELECT * FROM `{$this->table}` WHERE `$column` = ? LIMIT 1",
            [$value]
        );
    }

    public function all(string $where = '1', array $params = [], string $order = 'id DESC'): array
    {
        return $this->db()->all(
            "SELECT * FROM `{$this->table}` WHERE $where ORDER BY $order",
            $params
        );
    }

    /** Paginated fetch. Returns ['data' => [...], 'total' => n, 'page', 'pages']. */
    public function paginate(int $page = 1, int $perPage = 20, string $where = '1', array $params = [], string $order = 'id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = (int) $this->db()->scalar("SELECT COUNT(*) FROM `{$this->table}` WHERE $where", $params);
        $data = $this->db()->all(
            "SELECT * FROM `{$this->table}` WHERE $where ORDER BY $order LIMIT $perPage OFFSET $offset",
            $params
        );
        return [
            'data'    => $data,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => (int) ceil($total / $perPage),
        ];
    }

    public function create(array $data): int
    {
        return $this->db()->insert($this->table, $this->filter($data));
    }

    public function update($id, array $data): int
    {
        return $this->db()->update(
            $this->table,
            $this->filter($data),
            "`{$this->primaryKey}` = :__pk",
            ['__pk' => $id]
        );
    }

    public function delete($id): int
    {
        return $this->db()->delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function count(string $where = '1', array $params = []): int
    {
        return (int) $this->db()->scalar("SELECT COUNT(*) FROM `{$this->table}` WHERE $where", $params);
    }

    /** Restrict incoming data to fillable columns when declared. */
    protected function filter(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
