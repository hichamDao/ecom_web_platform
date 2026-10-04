<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\Address;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\OrderService;
use Throwable;

/**
 * Authentification et espace client.
 */
final class AccountController extends Controller
{
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
    }

    // -----------------------------------------------------------------
    // Authentification
    // -----------------------------------------------------------------

    public function loginForm(): never
    {
        $this->requireGuest();

        $this->view('account/login', ['title' => 'Connexion']);
    }

    public function login(): never
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], '/connexion');

        $user = Auth::attempt((string) $data['email'], (string) $data['password']);

        if ($user === null) {
            $this->failure('E-mail ou mot de passe incorrect.', '/connexion');
        }
        if (!$user->isActive()) {
            $this->failure('Ce compte a été désactivé.', '/connexion');
        }

        Auth::loginWeb($user);

        $intended = Session::pull('intended');
        $target = $user->isAdmin() ? '/admin' : (is_string($intended) && $intended !== '' ? $intended : '/compte');

        $this->success('Bienvenue ' . $user->fullName() . ' !', $target);
    }

    public function registerForm(): never
    {
        $this->requireGuest();

        $this->view('account/register', ['title' => 'Créer un compte']);
    }

    public function register(): never
    {
        $data = $this->validate([
            'username' => 'required|max:80',
            'email'    => 'required|email|max:160',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|confirmed',
        ], '/inscription');

        if (User::emailExists((string) $data['email'])) {
            $this->failure('Un compte existe déjà avec cet e-mail.', '/inscription');
        }
        if (User::usernameExists((string) $data['username'])) {
            $this->failure('Ce nom d\'utilisateur est déjà utilisé.', '/inscription');
        }

        $user = User::register([
            'username'  => (string) $data['username'],
            'email'     => (string) $data['email'],
            'password'  => (string) $data['password'],
            'firstName' => $this->request->input('first_name'),
            'lastName'  => $this->request->input('last_name'),
            'phone'     => $this->request->input('phone'),
        ]);

        Auth::loginWeb($user);
        Notification::notify(
            (int) $user->id,
            'EMAIL_VERIFIED',
            'Bienvenue sur ' . config('app.name'),
            'Votre compte a été créé avec succès. Bonne visite !',
            'MEDIUM'
        );

        $this->success('Votre compte a été créé.', '/compte');
    }

    public function logout(): never
    {
        Auth::logoutWeb();
        $this->success('Vous êtes déconnecté.', '/');
    }

    // -----------------------------------------------------------------
    // Profil
    // -----------------------------------------------------------------

    public function profile(): never
    {
        $this->requireAuth();

        $user = $this->user();

        $this->view('account/profile', [
            'title'      => 'Mon compte',
            'user'       => $user,
            'orders'     => Order::forUser((int) $user->id),
            'addresses'  => Address::forUser((int) $user->id),
            'wishlist'   => Wishlist::countFor((int) $user->id),
            'reviews'    => Review::forUser((int) $user->id),
            'notifications' => Notification::forUser((int) $user->id, 5),
            'stats'      => [
                'orders' => $user->orderCount(),
                'spent'  => $user->totalSpent(),
            ],
        ]);
    }

    public function updateProfile(): never
    {
        $this->requireAuth();

        $user = $this->user();
        $username = trim((string) $this->request->input('username', (string) $user->username));
        $email = trim((string) $this->request->input('email', (string) $user->email));

        if ($username !== $user->username && User::usernameExists($username, (int) $user->id)) {
            $this->failure('Ce nom d\'utilisateur est déjà utilisé.', '/compte');
        }
        if ($email !== $user->email && User::emailExists($email, (int) $user->id)) {
            $this->failure('Un compte existe déjà avec cet e-mail.', '/compte');
        }

        $user->username = $username;
        $user->email = $email;
        $user->first_name = trim((string) $this->request->input('first_name'));
        $user->last_name = trim((string) $this->request->input('last_name'));
        $user->phone = trim((string) $this->request->input('phone'));
        $user->save();

        $this->success('Profil mis à jour.', '/compte');
    }

    public function changePassword(): never
    {
        $this->requireAuth();

        $user = $this->user();
        $old = (string) $this->request->input('old_password', '');
        $new = (string) $this->request->input('new_password', '');
        $confirm = (string) $this->request->input('new_password_confirmation', '');

        if (!$user->verifyPassword($old)) {
            $this->failure('Ancien mot de passe incorrect.', '/compte');
        }
        if (mb_strlen($new) < 6) {
            $this->failure('Le nouveau mot de passe doit contenir au moins 6 caractères.', '/compte');
        }
        if ($new !== $confirm) {
            $this->failure('La confirmation ne correspond pas.', '/compte');
        }

        $user->setPassword($new);
        $user->save();

        $this->success('Mot de passe modifié.', '/compte');
    }

    // -----------------------------------------------------------------
    // Commandes
    // -----------------------------------------------------------------

    public function orders(): never
    {
        $this->requireAuth();

        $orders = Order::forUserWithItems((int) $this->userId());

        $this->view('account/orders', [
            'title'  => 'Mes commandes',
            'orders' => $orders,
        ]);
    }

    public function orderDetail(int $id): never
    {
        $this->requireAuth();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->abort('Commande introuvable', 404);
        }
        if (!Auth::isAdmin() && (int) $order->user_id !== $this->userId()) {
            $this->abort('Cette commande ne vous appartient pas', 403);
        }

        $this->view('account/order-detail', [
            'title'   => 'Commande ' . $order->order_number,
            'order'   => $order,
            'history' => $order->history(),
        ]);
    }

    public function cancelOrder(int $id): never
    {
        $this->requireAuth();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->abort('Commande introuvable', 404);
        }

        try {
            $this->orderService->cancel($order, Auth::isAdmin() ? null : (int) $this->userId());
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/compte/commandes/' . $id);
        }

        $this->success('Commande annulée.', '/compte/commandes/' . $id);
    }

    // -----------------------------------------------------------------
    // Favoris et adresses
    // -----------------------------------------------------------------

    public function wishlist(): never
    {
        $this->requireAuth();

        $userId = (int) $this->userId();

        $this->view('account/wishlist', [
            'title'    => 'Mes favoris',
            'products' => Wishlist::products($userId),
        ]);
    }

    public function addresses(): never
    {
        $this->requireAuth();

        $this->view('account/addresses', [
            'title'     => 'Mes adresses',
            'addresses' => Address::forUser((int) $this->userId()),
        ]);
    }

    public function storeAddress(): never
    {
        $this->requireAuth();

        $data = $this->validate([
            'full_name' => 'required|max:160',
            'phone'     => 'required|max:40',
            'address'   => 'required|max:255',
            'city'      => 'required|max:120',
            'country'   => 'required|max:120',
        ], '/compte/adresses');

        Address::createFor((int) $this->userId(), $data);

        $this->success('Adresse enregistrée.', '/compte/adresses');
    }

    public function updateAddress(int $id): never
    {
        $this->requireAuth();

        $address = Address::updateFor($id, (int) $this->userId(), $this->request->all());
        if ($address === null) {
            $this->abort('Adresse introuvable', 404);
        }

        $this->success('Adresse mise à jour.', '/compte/adresses');
    }

    public function defaultAddress(int $id): never
    {
        $this->requireAuth();

        if (Address::findForUser($id, (int) $this->userId()) === null) {
            $this->abort('Adresse introuvable', 404);
        }

        Address::setDefault($id, (int) $this->userId());
        $this->success('Adresse par défaut mise à jour.', '/compte/adresses');
    }

    public function deleteAddress(int $id): never
    {
        $this->requireAuth();

        $address = Address::findForUser($id, (int) $this->userId());
        if ($address === null) {
            $this->abort('Adresse introuvable', 404);
        }

        $address->delete();
        $this->success('Adresse supprimée.', '/compte/adresses');
    }
}