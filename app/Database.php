<?php
declare(strict_types=1);
final class Database {
    private PDO $pdo;
    public function __construct(array $config) {
        if ($config['driver'] === 'sqlite') {
            $this->pdo = new PDO('sqlite:'.$config['sqlite_path']);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA busy_timeout = 5000');
        } else {
            $this->pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4", $config['username'], $config['password'], [PDO::ATTR_EMULATE_PREPARES=>false]);
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    public function connection(): PDO { return $this->pdo; }
    public function query(string $sql, array $params = []): PDOStatement {
        $query = $this->pdo->prepare($sql); $query->execute($params); return $query;
    }
}
