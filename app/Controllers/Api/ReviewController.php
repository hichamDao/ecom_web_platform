<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Review;

/**
 * /api/reviews — avis du client et modération.
 */
final class ReviewController extends ApiController
{
    /** GET /api/reviews/my-reviews */
    public function myReviews(): never
    {
        $this->requireAuth();

        $reviews = Review::forUser((int) $this->userId());
        $this->ok(array_map(static fn (Review $review): array => $review->toApi(), $reviews));
    }

    /** GET /api/reviews/pending (admin) */
    public function pending(): never
    {
        $this->requireAdmin();

        $reviews = Review::pending();
        $this->ok(array_map(static fn (Review $review): array => $review->toApi(), $reviews));
    }

    /** PUT /api/reviews/{id}/approve (admin) */
    public function approve(int $id): never
    {
        $this->requireAdmin();

        $review = Review::find($id);
        if ($review === null) {
            $this->fail('Avis introuvable.', 404);
        }

        $review->approve();
        $this->ok($review->toApi(), 'Avis publié');
    }

    /** PUT /api/reviews/{id}/reject (admin) */
    public function reject(int $id): never
    {
        $this->requireAdmin();

        $review = Review::find($id);
        if ($review === null) {
            $this->fail('Avis introuvable.', 404);
        }

        $review->reject();
        $this->ok($review->toApi(), 'Avis rejeté');
    }

    /** DELETE /api/reviews/{id} (admin) */
    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $review = Review::find($id);
        if ($review === null) {
            $this->fail('Avis introuvable.', 404);
        }

        $review->delete();
        $this->ok(null, 'Avis supprimé');
    }

    /** DELETE /api/reviews/{id} — suppression par son auteur. */
    public function destroyMine(int $id): never
    {
        $this->requireAuth();

        $review = Review::find($id);
        if ($review === null) {
            $this->fail('Avis introuvable.', 404);
        }
        if ((int) $review->user_id !== $this->userId() && !$this->isAdminUser()) {
            $this->fail('Vous ne pouvez pas supprimer cet avis.', 403);
        }

        $review->delete();
        $this->ok(null, 'Avis supprimé');
    }
}