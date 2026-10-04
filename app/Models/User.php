<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

/**
 * Utilisateur de la plateforme (client ou administrateur).
 */
final class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name',
        'phone', 'avatar', 'role', 'is_active', 'last_login_at',
    ];

    protected static array $casts = [
        'id'        => 'int',
        'is_active' => 'bool',
    ];

    public const ROLE_CUSTOMER = 'CUSTOMER';
    public const ROLE_ADMIN = 'ADMIN';

    public static function findByEmail(string $email): ?self
    {
        return self::row(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower(trim($email))]
        );
    }

    public static function findByUsername(string $username): ?self
    {
        return self::row(
            'SELECT * FROM users WHERE username = :username LIMIT 1',
            ['username' => trim($username)]
        );
    }

    public static function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => mb_strtolower(trim($email))];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) self::value($sql, $params, 0) > 0;
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
        $params = ['username' => trim($username)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) self::value($sql, $params, 0) > 0;
    }

    /** Crée un compte client (mot de passe hashé). */
    public static function register(array $data): self
    {
        $user = new self();
        $user->fill([
            'username'   => $data['username'] ?? '',
            'email'      => mb_strtolower(trim((string) ($data['email'] ?? ''))),
            'first_name' => $data['firstName'] ?? $data['first_name'] ?? null,
            'last_name'  => $data['lastName'] ?? $data['last_name'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'role'       => $data['role'] ?? self::ROLE_CUSTOMER,
            'is_active'  => true,
        ]);
        $user->setPassword((string) ($data['password'] ?? ''));
        $user->save();

        return $user;
    }

    public function setPassword(string $plainPassword): void
    {
        $this->set('password', password_hash($plainPassword, PASSWORD_DEFAULT));
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, (string) $this->password);
    }

    public function fullName(): string
    {
        $first = trim((string) $this->first_name);
        $last = trim((string) $this->last_name);
        if ($first !== '' && $last !== '') {
            return $first . ' ' . $last;
        }
        if ($first !== '' || $last !== '') {
            return $first !== '' ? $first : $last;
        }

        return (string) ($this->username ?: $this->email);
    }

    public function initials(): string
    {
        $name = $this->fullName();
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : mb_strtoupper(mb_substr((string) $this->username, 0, 2));
    }

    public function isAdmin(): bool
    {
        return strtoupper((string) $this->role) === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /** Mémorise la date de la dernière connexion réussie. */
    public function touchLastLogin(): void
    {
        $now = date('Y-m-d H:i:s');
        self::db()->exec('UPDATE users SET last_login_at = ' . self::db()->quote($now)
            . ' WHERE id = ' . (int) $this->id);
        $this->set('last_login_at', $now);
    }

    public function orderCount(): int
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM orders WHERE user_id = :id',
            ['id' => (int) $this->id],
            0
        );
    }

    public function totalSpent(): float
    {
        return (float) self::value(
            'SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = :id AND is_paid = 1 AND status NOT IN (\'CANCELLED\', \'REFUNDED\')',
            ['id' => (int) $this->id],
            0
        );
    }

    /** @return array<int, self> */
    public static function paginateUsers(string $keyword = '', int $page = 0, int $size = 20): Paginator
    {
        $where = '';
        $params = [];
        if ($keyword !== '') {
            $kw = '%' . $keyword . '%';
            $where = ' WHERE (email LIKE :kw1 OR username LIKE :kw2 OR first_name LIKE :kw3 OR last_name LIKE :kw4)';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
        }

        $total = self::countRows('SELECT COUNT(*) FROM users' . $where, $params);
        $offset = $page * $size;
        $items = self::rows(
            'SELECT * FROM users' . $where . ' ORDER BY created_at DESC LIMIT ' . (int) $size . ' OFFSET ' . (int) $offset,
            $params
        );

        return new Paginator($items, $total, $page, $size);
    }

    public static function countCustomers(): int
    {
        return (int) self::value("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'", [], 0);
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'        => (int) $this->id,
            'username'  => $this->username,
            'email'     => $this->email,
            'firstName' => $this->first_name,
            'lastName'  => $this->last_name,
            'phone'     => $this->phone,
            'avatar'    => $this->avatar,
            'role'      => $this->role,
            'isActive'  => (bool) $this->is_active,
            'fullName'  => $this->fullName(),
            'createdAt' => iso_date($this->created_at),
        ];
    }
}
