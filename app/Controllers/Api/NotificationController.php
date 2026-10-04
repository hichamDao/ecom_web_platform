<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Notification;
use App\Models\User;

/**
 * /api/notifications — notifications du client et messages de diffusion.
 */
final class NotificationController extends ApiController
{
    /** GET /api/notifications/my-notifications */
    public function myNotifications(): never
    {
        $this->requireAuth();

        // Le client mobile attend une liste JSON simple.
        $notifications = Notification::forUser((int) $this->userId());
        $this->ok(array_map(static fn (Notification $n): array => $n->toApi(), $notifications));
    }

    /** GET /api/notifications/unread-count */
    public function unreadCount(): never
    {
        $this->requireAuth();

        $this->ok(['unreadCount' => Notification::unreadCount((int) $this->userId())]);
    }

    /** PUT /api/notifications/{id}/read */
    public function markAsRead(int $id): never
    {
        $this->requireAuth();

        Notification::markAsRead($id, (int) $this->userId());
        $this->ok(['id' => $id, 'isRead' => true], 'Notification marquée comme lue');
    }

    /** PUT /api/notifications/read-all */
    public function markAllAsRead(): never
    {
        $this->requireAuth();

        Notification::markAllAsRead((int) $this->userId());
        $this->ok(null, 'Toutes les notifications ont été marquées comme lues');
    }

    /** DELETE /api/notifications/my-notifications/{id} */
    public function destroy(int $id): never
    {
        $this->requireAuth();

        Notification::deleteFor($id, (int) $this->userId());
        $this->ok(null, 'Notification supprimée');
    }

    /** DELETE /api/notifications/all (admin) */
    public function destroyAll(): never
    {
        $this->requireAdmin();

        Notification::db()->exec('DELETE FROM notifications');
        $this->ok(null, 'Historique des notifications vidé');
    }

    /** POST /api/notifications/broadcast (admin) */
    public function broadcast(): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload([
            'title'   => 'required|max:200',
            'message' => 'required|max:2000',
        ]);

        $notification = Notification::broadcast(
            (string) $this->request->input('type', 'PROMOTION'),
            (string) $data['title'],
            (string) $data['message'],
            (string) $this->request->input('priority', 'MEDIUM'),
            $this->arrayValue($this->request->input('data'))
        );

        $this->created($notification->toApi(), 'Notification diffusée à tous les clients');
    }

    /** POST /api/notifications/send/{userId} (admin) */
    public function sendToUser(int $userId): never
    {
        $this->requireAdmin();

        if (User::find($userId) === null) {
            $this->fail('Client introuvable.', 404);
        }

        $data = $this->validatePayload([
            'title'   => 'required|max:200',
            'message' => 'required|max:2000',
        ]);

        $notification = Notification::notify(
            $userId,
            (string) $this->request->input('type', 'PROMOTION'),
            (string) $data['title'],
            (string) $data['message'],
            (string) $this->request->input('priority', 'MEDIUM'),
            $this->arrayValue($this->request->input('data'))
        );

        $this->created($notification->toApi(), 'Notification envoyée au client');
    }
}