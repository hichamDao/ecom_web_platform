<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;

/**
 * Back-office : modération des avis.
 */
final class ReviewController extends Controller
{
    public function index(): never
    {
        $this->requireAdmin();

        $status = strtoupper((string) $this->request->input('statut', ''));
        $page = max(1, $this->request->int('page', 1));

        $paginator = Review::paginateReviews($status, $page - 1, 20);
        $paginator->page = $page - 1;

        $this->view('admin/reviews/index', [
            'title'     => 'Avis clients',
            'paginator' => $paginator,
            'status'    => $status,
            'counts'    => [
                'pending'  => Review::countByStatus(Review::STATUS_PENDING),
                'approved' => Review::countByStatus(Review::STATUS_APPROVED),
                'rejected' => Review::countByStatus(Review::STATUS_REJECTED),
            ],
        ], 'layouts/admin');
    }

    public function approve(int $id): never
    {
        $this->requireAdmin();

        $review = Review::find($id);
        if ($review === null) {
            $this->abort('Avis introuvable', 404);
        }

        $review->approve();
        $this->notifyAuthor($review, 'REVIEW_APPROVED', 'Votre avis a été publié', 'Votre avis est maintenant visible sur la fiche produit.');

        $this->success('Avis publié.', '/admin/avis');
    }

    public function reject(int $id): never
    {
        $this->requireAdmin();

        $review = Review::find($id);
        if ($review === null) {
            $this->abort('Avis introuvable', 404);
        }

        $review->reject();
        $this->notifyAuthor($review, 'REVIEW_REJECTED', 'Votre avis a été rejeté', 'Votre avis ne respecte pas nos conditions de publication.');

        $this->success('Avis rejeté.', '/admin/avis');
    }

    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $review = Review::find($id);
        if ($review === null) {
            $this->abort('Avis introuvable', 404);
        }

        $review->delete();
        $this->success('Avis supprimé.', '/admin/avis');
    }

    private function notifyAuthor(Review $review, string $type, string $title, string $message): void
    {
        $userId = $review->user_id === null ? null : (int) $review->user_id;
        if ($userId === null || User::find($userId) === null) {
            return;
        }

        Notification::notify($userId, $type, $title, $message, 'MEDIUM', [
            'reviewId'  => (int) $review->id,
            'productId' => (int) $review->product_id,
        ]);
    }
}