<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Auth;
use App\Models\User;

/**
 * POST /api/auth/login | register | logout | change-password | forgot-password
 */
final class AuthController extends ApiController
{
    public function login(): never
    {
        $data = $this->validatePayload([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = Auth::attempt((string) $data['email'], (string) $data['password']);

        if ($user === null) {
            $this->fail('E-mail ou mot de passe incorrect.', 401);
        }
        if (!$user->isActive()) {
            $this->fail('Ce compte a été désactivé.', 403);
        }

        $token = Auth::issueToken($user);

        $this->ok([
            'token'      => $token,
            'accessToken' => $token,
            'tokenType'  => 'Bearer',
            'user'       => $user->toApi(),
        ], 'Connexion réussie');
    }

    public function register(): never
    {
        $data = $this->validatePayload([
            'username'  => 'required|max:80',
            'email'     => 'required|email|max:160',
            'password'  => 'required|min:6',
            'firstName' => 'max:80',
            'lastName'  => 'max:80',
        ]);

        if (User::emailExists((string) $data['email'])) {
            $this->fail('Un compte existe déjà avec cet e-mail.', 409);
        }
        if (User::usernameExists((string) $data['username'])) {
            $this->fail('Ce nom d\'utilisateur est déjà utilisé.', 409);
        }

        $user = User::register($data);
        $token = Auth::issueToken($user);

        $this->created([
            'token'      => $token,
            'accessToken' => $token,
            'tokenType'  => 'Bearer',
            'user'       => $user->toApi(),
        ], 'Compte créé avec succès');
    }

    public function logout(): never
    {
        $this->requireAuth();
        $this->ok(null, 'Déconnexion réussie');
    }

    public function changePassword(): never
    {
        $this->requireAuth();

        $data = $this->validatePayload([
            'oldPassword' => 'required',
            'newPassword' => 'required|min:6',
        ]);

        $user = $this->user();
        if ($user === null || !$user->verifyPassword((string) $data['oldPassword'])) {
            $this->fail('Ancien mot de passe incorrect.', 400);
        }

        $user->setPassword((string) $data['newPassword']);
        $user->save();

        $this->ok(null, 'Mot de passe modifié');
    }

    public function forgotPassword(): never
    {
        $data = $this->validatePayload(['email' => 'required|email']);
        // Réponse volontairement générique pour ne pas révéler les comptes existants.
        $this->ok(['email' => $data['email']], 'Si un compte existe, un e-mail de réinitialisation a été envoyé.');
    }
}