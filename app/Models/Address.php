<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Adresse de livraison enregistrée par un client.
 */
final class Address extends Model
{
    protected static string $table = 'addresses';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'user_id', 'full_name', 'phone', 'address', 'city', 'state', 'zip_code', 'country', 'is_default',
    ];

    protected static array $casts = [
        'id'         => 'int',
        'user_id'    => 'int',
        'is_default' => 'bool',
    ];

    /** @return array<int, self> */
    public static function forUser(int $userId): array
    {
        return self::rows(
            'SELECT * FROM addresses WHERE user_id = :userId ORDER BY is_default DESC, id DESC',
            ['userId' => $userId]
        );
    }

    public static function findForUser(int $id, int $userId): ?self
    {
        return self::row(
            'SELECT * FROM addresses WHERE id = :id AND user_id = :userId LIMIT 1',
            ['id' => $id, 'userId' => $userId]
        );
    }

    public static function defaultFor(int $userId): ?self
    {
        return self::row(
            'SELECT * FROM addresses WHERE user_id = :userId ORDER BY is_default DESC, id DESC LIMIT 1',
            ['userId' => $userId]
        );
    }

    public static function createFor(int $userId, array $data): self
    {
        $isDefault = (bool) ($data['isDefault'] ?? $data['is_default'] ?? false);
        if ($isDefault) {
            self::clearDefault($userId);
        }

        $address = self::create([
            'user_id'    => $userId,
            'full_name'  => trim((string) ($data['fullName'] ?? $data['full_name'] ?? '')),
            'phone'      => $data['phone'] ?? null,
            'address'    => trim((string) ($data['address'] ?? '')),
            'city'       => trim((string) ($data['city'] ?? '')),
            'state'      => $data['state'] ?? null,
            'zip_code'   => $data['zipCode'] ?? $data['zip_code'] ?? null,
            'country'    => trim((string) ($data['country'] ?? 'Maroc')),
            'is_default' => $isDefault ?: self::countFor($userId) === 0,
        ]);

        return $address;
    }

    public static function updateFor(int $id, int $userId, array $data): ?self
    {
        $address = self::findForUser($id, $userId);
        if ($address === null) {
            return null;
        }

        if (!empty($data['isDefault'] ?? $data['is_default'] ?? false)) {
            self::clearDefault($userId);
            $address->is_default = true;
        }

        $address->full_name = trim((string) ($data['fullName'] ?? $data['full_name'] ?? $address->full_name));
        $address->phone = $data['phone'] ?? $address->phone;
        $address->address = trim((string) ($data['address'] ?? $address->address));
        $address->city = trim((string) ($data['city'] ?? $address->city));
        $address->state = $data['state'] ?? $address->state;
        $address->zip_code = $data['zipCode'] ?? $data['zip_code'] ?? $address->zip_code;
        $address->country = trim((string) ($data['country'] ?? $address->country));
        $address->save();

        return $address;
    }

    public static function clearDefault(int $userId): void
    {
        self::db()->exec('UPDATE addresses SET is_default = 0 WHERE user_id = ' . (int) $userId);
    }

    public static function setDefault(int $id, int $userId): void
    {
        self::clearDefault($userId);
        self::db()->exec('UPDATE addresses SET is_default = 1 WHERE id = ' . (int) $id . ' AND user_id = ' . (int) $userId);
    }

    public static function countFor(int $userId): int
    {
        return (int) self::value('SELECT COUNT(*) FROM addresses WHERE user_id = :id', ['id' => $userId], 0);
    }

    public function formatted(): string
    {
        return sprintf(
            '%s, %s, %s %s, %s',
            (string) $this->address,
            (string) $this->city,
            (string) ($this->state ?? ''),
            (string) ($this->zip_code ?? ''),
            (string) $this->country
        );
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'        => (int) $this->id,
            'userId'    => (int) $this->user_id,
            'fullName'  => (string) $this->full_name,
            'phone'     => $this->phone,
            'address'   => (string) $this->address,
            'city'      => (string) $this->city,
            'state'     => $this->state,
            'zipCode'   => $this->zip_code,
            'country'   => (string) $this->country,
            'isDefault' => (bool) $this->is_default,
        ];
    }

    /** @return array<string, mixed> Format imbriqué attendu par OrderModel (Flutter). */
    public function toShippingAddress(): array
    {
        return [
            'fullName' => (string) $this->full_name,
            'address'  => (string) $this->address,
            'city'     => (string) $this->city,
            'state'    => $this->state,
            'zipCode'  => $this->zip_code,
            'country'  => (string) $this->country,
            'phone'    => $this->phone,
        ];
    }
}
