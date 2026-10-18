<?php
/** Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
declare(strict_types=1);
namespace Friends;
use PDO;
final class Db
{
    public PDO $pdo;
    public function __construct(array $config)
    {
        $this->pdo = new PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') $this->pdo->exec('PRAGMA foreign_keys = ON');
    }
    public function query(string $sql, array $args = []): \PDOStatement { $q = $this->pdo->prepare($sql); $q->execute($args); return $q; }
    public function all(string $sql, array $args = []): array { return $this->query($sql, $args)->fetchAll(); }
    public function one(string $sql, array $args = []): ?array { return $this->query($sql, $args)->fetch() ?: null; }
    public function value(string $sql, array $args = []): mixed { return $this->query($sql, $args)->fetchColumn(); }
    public function insert(string $sql, array $args = []): int { $this->query($sql, $args); return (int) $this->pdo->lastInsertId(); }
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try { $result = $callback(); $this->pdo->commit(); return $result; }
        catch (\Throwable $error) { $this->pdo->rollBack(); throw $error; }
    }
    public function migrate(): void
    {
        $this->executeSchema(FRIENDS_ROOT . '/database/schema.sql');
        $this->executeSchema(FRIENDS_ROOT . '/database/features.sql');
    }
    public function executeSchema(string $path): void
    {
        $sql = file_get_contents($path);
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = preg_replace('/BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = str_replace('BIGINT UNSIGNED', 'INTEGER', $sql);
            $sql = preg_replace('/ ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci/', '', $sql);
        }
        foreach (explode(';', $sql) as $statement) if (trim($statement)) $this->pdo->exec($statement);
    }
}
