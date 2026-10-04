<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Upload;
use App\Models\PromoBanner;
use Throwable;

/**
 * Back-office : bannières promotionnelles.
 */
final class PromoBannerController extends Controller
{
    public function index(): never
    {
        $this->requireAdmin();

        $this->view('admin/banners/index', [
            'title'   => 'Bannières',
            'banners' => PromoBanner::allOrdered(),
        ], 'layouts/admin');
    }

    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validate(['title' => 'required|max:160'], '/admin/bannieres');
        $payload = PromoBanner::fromPayload($this->request->all());
        $payload['position'] = $payload['position'] > 0 ? $payload['position'] : PromoBanner::nextPosition();

        try {
            $payload['photo'] = Upload::image($this->request->file('photo'), 'banners') ?? $payload['photo'];
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/admin/bannieres');
        }

        PromoBanner::create($payload);

        $this->success('Bannière créée.', '/admin/bannieres');
    }

    public function update(int $id): never
    {
        $this->requireAdmin();

        $banner = PromoBanner::find($id);
        if ($banner === null) {
            $this->abort('Bannière introuvable', 404);
        }

        $redirect = '/admin/bannieres';

        foreach (PromoBanner::fromPayload($this->request->all()) as $key => $value) {
            $banner->{$key} = $value;
        }

        try {
            $uploaded = Upload::image($this->request->file('photo'), 'banners');
            if ($uploaded !== null) {
                Upload::delete($banner->photo);
                $banner->photo = $uploaded;
            }
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), $redirect);
        }

        $banner->save();

        $this->success('Bannière mise à jour.', $redirect);
    }

    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $banner = PromoBanner::find($id);
        if ($banner === null) {
            $this->abort('Bannière introuvable', 404);
        }

        Upload::delete($banner->photo);
        $banner->delete();

        $this->success('Bannière supprimée.', '/admin/bannieres');
    }
}