<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Auth;
use App\Models\User;

/**
 * Contrôleur de base de l'API REST consommée par l'application mobile
 * ecom_app_fend (Authorization: Bearer <JWT>).
 */
abstract class ApiController extends Controller
{
    protected function requireAuth(): void
    {
        $user = Auth::user();
        if ($user === null) {
            throw HttpException::unauthorized('Session expirée, veuillez vous reconnecter');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            throw HttpException::forbidden('Accès réservé aux administrateurs');
        }
    }

    protected function user(): ?User
    {
        return Auth::user();
    }

    protected function userId(): ?int
    {
        return Auth::id();
    }

    protected function isAdminUser(): bool
    {
        return Auth::isAdmin();
    }

    /** @return array<string, mixed> Données JSON (tableau ou objet). */
    protected function payload(): array
    {
        $data = $this->request->all();

        return is_array($data) ? $data : [];
    }

    protected function ok(mixed $data = null, string $message = ''): never
    {
        Response::json($data, 200, $message !== '' ? $message : null);
    }

    protected function created(mixed $data = null, string $message = ''): never
    {
        Response::json($data, 201, $message !== '' ? $message : null);
    }

    protected function fail(string $message, int $status = 400, array $errors = []): never
    {
        Response::error($message, $status, $errors);
    }

    /**
     * @param  array<string, string> $rules
     * @return array<string, mixed>
     */
    protected function validatePayload(array $rules): array
    {
        $validator = Validator::make($this->payload(), $rules);
        if ($validator->fails()) {
            throw HttpException::unprocessable('Données invalides', $validator->errors());
        }

        return $validator->validated();
    }

    /** Convertit une valeur JSON (chaîne ou tableau) en tableau. */
    protected function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
