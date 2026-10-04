<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Erreur HTTP portant un code de statut (404, 403, 422...). */
class HttpException extends RuntimeException
{
    public function __construct(string $message = '', private int $status = 404, private array $errors = [])
    {
        parent::__construct($message !== '' ? $message : 'Erreur HTTP ' . $status, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public static function notFound(string $message = 'Ressource introuvable'): self
    {
        return new self($message, 404);
    }

    public static function unauthorized(string $message = 'Authentification requise'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message = 'Accès refusé'): self
    {
        return new self($message, 403);
    }

    public static function unprocessable(string $message = 'Données invalides', array $errors = []): self
    {
        return new self($message, 422, $errors);
    }
}
