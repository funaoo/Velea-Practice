<?php

namespace Nathan45\Valea\Database;

use Nathan45\Valea\Loader;

class SQLiteDatabase
{
    private \SQLite3 $db;
    private static self $instance;
    private array $statements = [];

    public function __construct(string $dataFolder)
    {
        $this->db = new \SQLite3($dataFolder . "practice.db");
        $this->db->enableExceptions(true);
        $this->db->busyTimeout(5000);
        $this->db->exec("PRAGMA journal_mode = WAL");
        $this->db->exec("PRAGMA synchronous = NORMAL");
        $this->db->exec("PRAGMA temp_store = MEMORY");
        $this->db->exec("PRAGMA cache_size = -8000");
        $this->db->exec("PRAGMA foreign_keys = OFF");
        self::$instance = $this;
        $this->initTables();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    private function initTables(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS `ban` (
            `player` TEXT PRIMARY KEY,
            `by_name` TEXT NOT NULL,
            `time_sec` INTEGER NOT NULL DEFAULT 0,
            `reason` TEXT NOT NULL DEFAULT ''
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS `valea` (
            `player` TEXT PRIMARY KEY,
            `coins` INTEGER NOT NULL DEFAULT 0,
            `kills` INTEGER NOT NULL DEFAULT 0,
            `death` INTEGER NOT NULL DEFAULT 0,
            `rank` INTEGER NOT NULL DEFAULT 0,
            `elo` INTEGER NOT NULL DEFAULT 0,
            `cps` TEXT NOT NULL DEFAULT 'true',
            `ip` TEXT,
            `id` TEXT,
            `friends` TEXT,
            `inventories` TEXT,
            `scoreboard` TEXT NOT NULL DEFAULT 'true',
            `death_message` TEXT NOT NULL DEFAULT 'true'
        )");

        $this->db->exec("CREATE INDEX IF NOT EXISTS `idx_valea_rank` ON `valea` (`rank`)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS `idx_valea_elo` ON `valea` (`elo`)");
    }

    private function prepare(string $sql): \SQLite3Stmt
    {
        if (!isset($this->statements[$sql])) {
            $this->statements[$sql] = $this->db->prepare($sql);
        }
        $stmt = $this->statements[$sql];
        $stmt->reset();
        $stmt->clear();
        return $stmt;
    }

    private function bind(\SQLite3Stmt $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value) => SQLITE3_INTEGER,
                is_float($value) => SQLITE3_FLOAT,
                is_null($value) => SQLITE3_NULL,
                default => SQLITE3_TEXT,
            };
            $stmt->bindValue(is_int($key) ? $key + 1 : ":" . $key, $value, $type);
        }
    }

    public function run(string $sql, array $params = []): bool
    {
        try {
            $stmt = $this->prepare($sql);
            $this->bind($stmt, $params);
            $stmt->execute();
            return true;
        } catch (\Exception $e) {
            Loader::getInstance()->getLogger()->error("SQLite error: " . $e->getMessage() . " | Query: " . $sql);
            return false;
        }
    }

    public function all(string $sql, array $params = []): array
    {
        $rows = [];
        try {
            $stmt = $this->prepare($sql);
            $this->bind($stmt, $params);
            $result = $stmt->execute();
            if ($result instanceof \SQLite3Result) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $rows[] = $row;
                }
            }
        } catch (\Exception $e) {
            Loader::getInstance()->getLogger()->error("SQLite error: " . $e->getMessage() . " | Query: " . $sql);
        }
        return $rows;
    }

    public function one(string $sql, array $params = []): ?array
    {
        return $this->all($sql, $params)[0] ?? null;
    }

    public function update(string $table, array $set, array $where): bool
    {
        $setParts = [];
        $params = [];
        foreach ($set as $col => $value) {
            $key = "s_" . $col;
            $setParts[] = "`$col` = :$key";
            $params[$key] = $value;
        }
        $whereParts = [];
        foreach ($where as $col => $value) {
            $key = "w_" . $col;
            $whereParts[] = "`$col` = :$key";
            $params[$key] = $value;
        }
        $sql = "UPDATE `$table` SET " . implode(", ", $setParts) . " WHERE " . implode(" AND ", $whereParts);
        return $this->run($sql, $params);
    }

    public function insert(string $table, array $data, bool $orIgnore = false): bool
    {
        $cols = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ":" . $c, $cols);
        $verb = $orIgnore ? "INSERT OR IGNORE" : "INSERT OR REPLACE";
        $sql = "$verb INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $placeholders) . ")";
        return $this->run($sql, $data);
    }

    public function delete(string $table, array $where): bool
    {
        $whereParts = [];
        $params = [];
        foreach ($where as $col => $value) {
            $key = "w_" . $col;
            $whereParts[] = "`$col` = :$key";
            $params[$key] = $value;
        }
        $sql = "DELETE FROM `$table` WHERE " . implode(" AND ", $whereParts);
        return $this->run($sql, $params);
    }

    public function transaction(\Closure $callback): bool
    {
        try {
            $this->db->exec("BEGIN IMMEDIATE");
            $callback($this);
            $this->db->exec("COMMIT");
            return true;
        } catch (\Exception $e) {
            $this->db->exec("ROLLBACK");
            Loader::getInstance()->getLogger()->error("SQLite transaction error: " . $e->getMessage());
            return false;
        }
    }

    public function close(): void
    {
        foreach ($this->statements as $stmt) {
            $stmt->close();
        }
        $this->statements = [];
        $this->db->close();
    }
}
