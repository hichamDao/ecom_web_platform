<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Coupon;

/**
 * /api/coupons — codes promo (liste, création, attribution).
 */
final class CouponController extends ApiController
{
    /** GET /api/coupons/available — codes utilisables par le client connecté. */
    public function available(): never
    {
        $this->requireAuth();

        $coupons = Coupon::availableFor((int) $this->userId());
        $usable = array_filter(
            $coupons,
            fn (Coupon $coupon): bool => $coupon->isUsableBy((int) $this->userId())
        );

        $this->ok(array_map(static fn (Coupon $coupon): array => $coupon->toApi(), array_values($usable)));
    }

    /** GET /api/coupons (admin) */
    public function index(): never
    {
        $this->requireAdmin();

        $this->ok(array_map(
            static fn (Coupon $coupon): array => $coupon->toApi(),
            Coupon::all('created_at DESC')
        ));
    }

    /** GET /api/coupons/{id} */
    public function show(int $id): never
    {
        $this->requireAdmin();

        $coupon = Coupon::find($id);
        if ($coupon === null) {
            $this->fail('Code promo introuvable.', 404);
        }

        $this->ok($coupon->toApi());
    }

    /** POST /api/coupons (admin) */
    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload(['percentage' => 'required|numeric']);

        $coupon = $this->createCoupon($this->payload());
        $this->created($coupon->toApi(), 'Code promo créé');
    }

    /** POST /api/coupons/generate (admin) — crée un code nominatif pour un client. */
    public function generate(): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload(['percentage' => 'required|numeric']);

        $payload = $this->payload();
        $userId = $payload['userId'] ?? $payload['user_id'] ?? null;

        if ($userId === null || $userId === '') {
            $this->fail('Un client cible (userId) est requis pour générer un code.', 422);
        }
        if (\App\Models\User::find((int) $userId) === null) {
            $this->fail('Client introuvable.', 422);
        }

        $payload['userId'] = (int) $userId;
        $payload['isActive'] = true;
        $payload['code'] = Coupon::generateCode('PERSO');

        $coupon = $this->createCoupon($payload);
        $this->created($coupon->toApi(), 'Code promo généré pour le client');
    }

    /** PUT /api/coupons/{id} (admin) */
    public function update(int $id): never
    {
        $this->requireAdmin();

        $coupon = Coupon::find($id);
        if ($coupon === null) {
            $this->fail('Code promo introuvable.', 404);
        }

        $payload = Coupon::fromPayload($this->payload());
        foreach ($payload as $key => $value) {
            $coupon->{$key} = $value;
        }

        $code = trim((string) $this->request->input('code', (string) $coupon->code));
        if ($code !== '' && Coupon::codeExists($code, $id)) {
            $this->fail('Ce code promo existe déjà.', 409);
        }
        $coupon->code = mb_strtoupper($code);
        $coupon->save();

        $this->ok($coupon->toApi(), 'Code promo mis à jour');
    }

    /** DELETE /api/coupons/{id} (admin) */
    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $coupon = Coupon::find($id);
        if ($coupon === null) {
            $this->fail('Code promo introuvable.', 404);
        }

        $coupon->delete();
        $this->ok(null, 'Code promo supprimé');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createCoupon(array $payload): Coupon
    {
        $normalized = Coupon::fromPayload($payload);
        if ((float) $normalized['percentage'] <= 0 || (float) $normalized['percentage'] > 100) {
            $this->fail('Le pourcentage doit être compris entre 0 et 100.', 422);
        }

        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            $code = Coupon::generateCode();
        }
        $code = mb_strtoupper($code);

        if (Coupon::codeExists($code)) {
            $this->fail('Ce code promo existe déjà.', 409);
        }

        return Coupon::create($normalized + ['code' => $code]);
    }
}