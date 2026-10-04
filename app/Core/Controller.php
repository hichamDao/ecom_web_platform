<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Contrôleur de base de la boutique web (HTML) et du back-office.
 */
abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::instance();
    }

    /**
     * Affiche une vue avec le layout principal.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): never
    {
        Response::html(View::render($view, $data, $layout));
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    protected function back(string $fallback = '/'): never
    {
        Response::redirect($this->request->referer($fallback));
    }

    protected function withInput(array $input, array $except = ['password', '_token']): void
    {
        foreach ($except as $key) {
            unset($input[$key]);
        }
        Session::keep($input);
    }

    protected function success(string $message, string $redirect = '/'): never
    {
        Session::success($message);
        Response::redirect($redirect);
    }

    protected function failure(string $message, string $redirect = '/'): never
    {
        Session::error($message);
        Response::redirect($redirect);
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    /**
     * Valide et, en cas d'erreur, redirige avec messages + anciennes saisies.
     *
     * @param array<string, string> $rules
     * @return array<string, mixed>
     */
    protected function validate(array $rules, string $redirect = ''): array
    {
        $validator = Validator::make($this->request->all(), $rules);
        if ($validator->fails()) {
            foreach ($validator->errors() as $message) {
                Session::error($message);
            }
            $this->withInput($this->request->all());
            $this->back($redirect !== '' ? $redirect : $this->request->referer('/'));
        }

        return $validator->validated();
    }

    // -----------------------------------------------------------------
    // Garde d'accès
    // -----------------------------------------------------------------

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            if ($this->request->wantsJson()) {
                throw HttpException::unauthorized('Authentification requise');
            }
            Session::set('intended', $this->request->fullUrl());
            Session::error('Merci de vous connecter pour continuer.');
            Response::redirect('/connexion');
        }
    }

    protected function requireGuest(): void
    {
        if (Auth::check()) {
            Response::redirect('/compte');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            if ($this->request->wantsJson()) {
                throw HttpException::forbidden('Accès réservé aux administrateurs');
            }
            Session::error("Accès réservé aux administrateurs.");
            Response::redirect('/');
        }
    }

    // -----------------------------------------------------------------
    // Utilitaires
    // -----------------------------------------------------------------

    protected function user(): ?User
    {
        return Auth::user();
    }

    protected function userId(): ?int
    {
        return Auth::id();
    }

    protected function abort(string $message = 'Ressource introuvable', int $status = 404): never
    {
        throw new HttpException($message, $status);
    }

    protected function methodIs(string $method): bool
    {
        return $this->request->isMethod($method);
    }
}
