<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Coupon;
use App\Models\User;

/**
 * Back-office : codes promotionnels.
 */
final class CouponController extends Controller
{
    public function index(): never
    {
        $this->requireAdmin();

        $this->view('admin/coupons/index', [
            'title'    => 'Codes promo',
            'coupons'  => Coupon::all('created_at DESC'),
            'customers' => User::rows("SELECT id, username, email FROM users WHERE role = 'CUSTOMER' ORDER BY username"),
        ], 'layouts/admin');
    }

    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validate([
            'percentage' => 'required|numeric',
        ], '/admin/codes-promo');

        $payload = Coupon::fromPayload($this->request->all());

        if ((float) $payload['percentage'] <= 0 || (float) $payload['percentage'] > 100) {
            $this->failure('Le pourcentage doit être compris entre 0 et 100.', '/admin/codes-promo');
        }

        $code = mb_strtoupper(trim((string) $this->request->input('code', '')));
        if ($code === '') {
            $code = Coupon::generateCode();
        }
        if (Coupon::codeExists($code)) {
            $this->failure('Ce code promo existe déjà.', '/admin/codes-promo');
        }

        Coupon::create($payload + ['code' => $code]);

        $this->success('Code promo « ' . $code . ' » créé.', '/admin/codes-promo');
    }

    public function update(int $id): never
    {
        $this->requireAdmin();

        $coupon = Coupon::find($id);
        if ($coupon === null) {
            $this->abort('Code promo introuvable', 404);
        }

        $redirect = '/admin/codes-promo';

        foreach (Coupon::fromPayload($this->request->all()) as $key => $value) {
            $coupon->{$key} = $value;
        }

        $code = mb_strtoupper(trim((string) $this->request->input('code', (string) $coupon->code)));
        if ($code !== '' && Coupon::codeExists($code, $id)) {
            $this->failure('Ce code promo existe déjà.', $redirect);
        }
        $coupon->code = $code;
        $coupon->save();

        $this->success('Code promo mis à jour.', $redirect);
    }

    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $coupon = Coupon::find($id);
        if ($coupon === null) {
            $this->abort('Code promo introuvable', 404);
        }

        $coupon->delete();
        $this->success('Code promo supprimé.', '/admin/codes-promo');
    }
}