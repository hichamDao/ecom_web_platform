<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Classe de base de tous les modèles (entités).
 *
 * - Chaque modèle mappe une table et sait se construire depuis une ligne SQL.
 * - Les attributs sont protégés par des casts (int, float, bool, json).
 * - Les requêtes sont écrites explicitement dans les modèles enfants
 *   à l'aide des helpers statiques rows()/row()/value().
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    /** Colonnes acceptées par create()/update(). */
    protected static array $fillable = [];

    /** Types de colonnes : 'int' | 'float' | 'bool' | 'json'. */
    protected static array $casts = [];

    /** @var array<string, mixed> */
    protected array $data = [];

    protected bool $exists = false;

    public function __construct(array $attributes = [], bool $exists = false)
    {
        $this->exists = $exists;
        if ($attributes !== []) {
            $this->fill($attributes);
        }
    }

    // -----------------------------------------------------------------
    // Accès aux attributs
    // -----------------------------------------------------------------

    public function fill(array $attributes): static
    {
        $fillable = static::$fillable;
        foreach ($attributes as $key => $value) {
            if ($fillable !== [] && !in_array($key, $fillable, true)) {
                continue;
            }
            $this->data[$key] = $this->castValue($key, $value);
        }

        return $this;
    }

    /** Hydratation complète depuis une ligne SQL (sans filtrer par $fillable). */
    public function hydrate(array $row): static
    {
        foreach ($row as $key => $value) {
            $this->data[$key] = $this->castValue($key, $value);
        }
        $this->exists = true;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $this->castValue($key, $value);

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    /** Attributs prêts à être envoyés en base (JSON re-encodé). */
    public function attributesForDb(): array
    {
        $data = $this->data;
        foreach (static::$casts as $column => $type) {
            if ($type === 'json' && isset($data[$column]) && !is_string($data[$column])) {
                $data[$column] = $data[$column] === [] ? null : json_encode($data[$column], JSON_UNESCAPED_UNICODE);
            }
        }

        return $data;
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $this->castValue($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    protected function castValue(string $key, mixed $value): mixed
    {
        $type = static::$casts[$key] ?? null;
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => (bool) $value,
            'json'  => is_array($value) ? $value : (json_decode((string) $value, true) ?? []),
            default => $value,
        };
    }

    // -----------------------------------------------------------------
    // Persistance
    // -----------------------------------------------------------------

    public function save(): bool
    {
        $data = $this->attributesForDb();
        $key = static::$primaryKey;
        unset($data[$key]);

        if ($this->exists && isset($this->data[$key])) {
            if ($data === []) {
                return true;
            }
            Database::instance()->update(
                static::$table,
                $data,
                "{$key} = :id",
                ['id' => $this->data[$key]]
            );

            return true;
        }

        $id = Database::instance()->insert(static::$table, $data);
        $this->data[$key] = $id;
        $this->exists = true;

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        Database::instance()->delete(
            static::$table,
            static::$primaryKey . ' = :id',
            ['id' => $this->data[static::$primaryKey]]
        );
        $this->exists = false;

        return true;
    }

    // -----------------------------------------------------------------
    // Helpers de requête
    // -----------------------------------------------------------------

    public static function db(): PDO
    {
        return Database::instance()->pdo();
    }

    public static function tableName(): string
    {
        if (static::$table === '') {
            throw new RuntimeException('Le modèle ' . static::class . ' ne définit pas de table.');
        }

        return static::$table;
    }

    /** @return array<int, static> */
    public static function rows(string $sql, array $params = []): array
    {
        $models = [];
        foreach (Database::instance()->fetchAll($sql, $params) as $row) {
            $model = new static();
            $model->hydrate($row);
            $models[] = $model;
        }

        return $models;
    }

    protected static function row(string $sql, array $params = []): ?static
    {
        $data = Database::instance()->fetchOne($sql, $params);
        if ($data === null) {
            return null;
        }

        $model = new static();
        $model->hydrate($data);

        return $model;
    }

    protected static function value(string $sql, array $params = [], mixed $default = null): mixed
    {
        return Database::instance()->fetchValue($sql, $params, $default);
    }

    protected static function countRows(string $sql, array $params = []): int
    {
        return (int) static::value($sql, $params, 0);
    }

    // -----------------------------------------------------------------
    // Constructeurs statiques
    // -----------------------------------------------------------------

    public static function find(mixed $id): ?static
    {
        return static::row(
            'SELECT * FROM ' . static::tableName() . ' WHERE ' . static::$primaryKey . ' = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public static function findBy(string $column, mixed $value, ?string $orderBy = null): ?static
    {
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE ' . $column . ' = :value';
        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        return static::row($sql . ' LIMIT 1', ['value' => $value]);
    }

    /** @return array<int, static> indexés par identifiant */
    public static function findMany(array $ids, ?string $orderBy = null): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT * FROM ' . static::tableName() . ' WHERE ' . static::$primaryKey . ' IN (' . $placeholders . ')';
        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        $models = [];
        foreach (Database::instance()->fetchAll($sql, $ids) as $data) {
            $model = new static();
            $model->hydrate($data);
            $models[(int) $data[static::$primaryKey]] = $model;
        }

        return $models;
    }

    /** @return array<int, static> */
    public static function all(?string $orderBy = null): array
    {
        $sql = 'SELECT * FROM ' . static::tableName();
        if ($orderBy !== null) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        return static::rows($sql);
    }

    public static function create(array $data): static
    {
        $model = new static();
        $model->fill($data);
        $model->save();

        return $model;
    }

    public static function countAll(): int
    {
        return static::countRows('SELECT COUNT(*) FROM ' . static::tableName());
    }

    public static function deleteWhere(array $where): int
    {
        [$clause, $params] = static::buildWhere($where);

        return Database::instance()->delete(static::tableName(), $clause, $params);
    }

    public static function updateWhere(array $where, array $data): int
    {
        [$clause, $params] = static::buildWhere($where);
        $filtered = [];
        foreach (array_keys($data) as $column) {
            if (in_array($column, static::$fillable, true)) {
                $filtered[$column] = $data[$column];
            }
        }

        return Database::instance()->update(static::tableName(), $filtered, $clause, $params);
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    protected static function buildWhere(array $where, string $glue = 'AND'): array
    {
        $clauses = [];
        $params = [];
        $index = 0;
        foreach ($where as $column => $value) {
            $placeholder = 'w' . $index++;
            if ($value === null) {
                $clauses[] = "{$column} IS NULL";
                continue;
            }
            $clauses[] = "{$column} = :{$placeholder}";
            $params[$placeholder] = $value;
        }

        return [$clauses === [] ? '1=1' : implode(' ' . $glue . ' ', $clauses), $params];
    }
}
