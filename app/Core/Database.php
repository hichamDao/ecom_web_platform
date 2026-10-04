<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * Connexion MySQL (singleton PDO) et raccourcis d'exécution de requêtes.
 */
final class Database
{
    private static ?self $instance = null;

    private PDO $pdo;

    /** Empêche l'instanciation directe. */
    private function __construct(private array $config)
    {
        $host    = $config['host'] ?? '127.0.0.1';
        $port    = (int) ($config['port'] ?? 3306);
        $name    = $config['name'] ?? 'ecom_platform';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $config['user'] ?? 'root', $config['pass'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Connexion MySQL impossible (' . $name . ') : ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public static function boot(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('La base de données n\'a pas été initialisée.');
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Prépare et exécute une requête en lieant chaque paramètre avec son type PHP. */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            // PDO exige un identifiant 1-based pour les paramètres positionnels.
            $parameter = is_int($key) ? $key + 1 : $key;
            $statement->bindValue($parameter, $value, self::pdoType($value));
        }

        $statement->execute();

        return $statement;
    }

    /**
     * Type PDO correspondant au type PHP.
     *
     * Indispensable : execute($params) lie tout en PARAM_STR, ce qui fait
     * passer les booléens à '' — rejeté par MySQL sur une colonne numérique.
     */
    private static function pdoType(mixed $value): int
    {
        return match (true) {
            $value === null => PDO::PARAM_NULL,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value)  => PDO::PARAM_INT,
            default         => PDO::PARAM_STR,
        };
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public function fetchValue(string $sql, array $params = [], mixed $default = null): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? $default : $value;
    }

    public function execute(string $sql, array $params = []): bool
    {
        return $this->run($sql, $params)->rowCount() >= 0;
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->run($sql, $data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $assignments = [];
        $params = [];
        foreach ($data as $column => $value) {
            $assignments[] = "{$column} = :set_{$column}";
            $params["set_{$column}"] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $assignments), $where);

        return $this->run($sql, array_merge($params, $whereParams))->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->run("DELETE FROM {$table} WHERE {$where}", $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Exécute un callback dans une transaction (rollback automatique en cas d'erreur).
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo;
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($this);
            if ($ownTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function isConnected(): bool
    {
        try {
            return (bool) $this->pdo->query('SELECT 1')->fetchColumn();
        } catch (PDOException) {
            return false;
        }
    }
}
