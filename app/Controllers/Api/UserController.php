<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\User;

/**
 * GET/PUT /api/users/me — GET /api/users/all (admin)
 */
final class UserController extends ApiController
{
    public function me(): never
    {
        $this->requireAuth();
        $this->ok($this->user()?->toApi());
    }

    public function updateMe(): never
    {
        $this->requireAuth();

        $payload = $this->payload();
        $user = $this->user();
        if ($user === null) {
            $this->fail('Utilisateur introuvable.', 404);
        }

        $username = trim((string) ($payload['username'] ?? $user->username));
        if ($username !== '' && $username !== $user->username && User::usernameExists($username, (int) $user->id)) {
            $this->fail('Ce nom d\'utilisateur est déjà utilisé.', 409);
        }

        $user->username = $username !== '' ? $username : $user->username;
        $user->first_name = trim((string) ($payload['firstName'] ?? $user->first_name));
        $user->last_name = trim((string) ($payload['lastName'] ?? $user->last_name));
        $user->phone = $payload['phone'] ?? $user->phone;
        $user->avatar = $payload['avatar'] ?? $user->avatar;
        $user->save();

        $this->ok($user->toApi(), 'Profil mis à jour');
    }

    public function all(): never
    {
        $this->requireAdmin();

        $keyword = trim((string) $this->request->input('keyword', ''));
        $page = max(0, $this->request->int('page', 0));
        $size = max(1, min(100, $this->request->int('size', 50)));

        $paginator = User::paginateUsers($keyword, $page, $size);

        $this->ok($paginator->map(static fn (User $user): array => $user->toApi())->toArray());
    }

    /** Suppression d'un compte (administrateur). */
    public function destroy(int $id): never
    {
        $this->requireAdmin();

        if ($id === $this->userId()) {
            $this->fail('Vous ne pouvez pas supprimer votre propre compte.', 400);
        }

        $user = User::find($id);
        if ($user === null) {
            $this->fail('Utilisateur introuvable.', 404);
        }

        $user->delete();
        $this->ok(null, 'Compte supprimé');
    }
}