<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\PromoBanner;

/**
 * /api/promo-banners — bannières de la page d'accueil.
 */
final class PromoBannerController extends ApiController
{
    /** GET /api/promo-banners (admin) */
    public function index(): never
    {
        $this->requireAdmin();

        $this->ok(array_map(
            static fn (PromoBanner $banner): array => $banner->toApi(),
            PromoBanner::allOrdered()
        ));
    }

    /** GET /api/promo-banners/active — public */
    public function active(): never
    {
        $this->ok(array_map(
            static fn (PromoBanner $banner): array => $banner->toApi(),
            PromoBanner::active()
        ));
    }

    /** GET /api/promo-banners/{id} */
    public function show(int $id): never
    {
        $banner = PromoBanner::find($id);
        if ($banner === null) {
            $this->fail('Bannière introuvable.', 404);
        }

        $this->ok($banner->toApi());
    }

    /** POST /api/promo-banners (admin) */
    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload(['title' => 'required|max:160']);

        $payload = PromoBanner::fromPayload($this->payload());
        $payload['position'] = $payload['position'] > 0 ? $payload['position'] : PromoBanner::nextPosition();

        $banner = PromoBanner::create($payload);
        $this->created($banner->toApi(), 'Bannière créée');
    }

    /** PUT /api/promo-banners/{id} (admin) */
    public function update(int $id): never
    {
        $this->requireAdmin();

        $banner = PromoBanner::find($id);
        if ($banner === null) {
            $this->fail('Bannière introuvable.', 404);
        }

        foreach (PromoBanner::fromPayload($this->payload()) as $key => $value) {
            $banner->{$key} = $value;
        }
        $banner->save();

        $this->ok($banner->toApi(), 'Bannière mise à jour');
    }

    /** PUT /api/promo-banners/reorder (admin) */
    public function reorder(): never
    {
        $this->requireAdmin();

        $ids = $this->arrayValue($this->request->input('ids', []));
        $ids = array_map('intval', $ids);

        if ($ids === []) {
            $this->fail('La liste des identifiants est vide.', 422);
        }

        PromoBanner::reorder($ids);
        $this->ok(null, 'Ordre des bannières mis à jour');
    }

    /** DELETE /api/promo-banners/{id} (admin) */
    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $banner = PromoBanner::find($id);
        if ($banner === null) {
            $this->fail('Bannière introuvable.', 404);
        }

        $banner->delete();
        $this->ok(null, 'Bannière supprimée');
    }
}