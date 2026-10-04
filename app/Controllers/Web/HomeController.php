<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\CartService;
use Throwable;

/**
 * Pages publiques : accueil, catalogue, fiche produit, avis.
 */
final class HomeController extends Controller
{
    private CartService $cartService;

    public function __construct()
    {
        parent::__construct();
        $this->cartService = new CartService();
    }

    public function index(): never
    {
        $userId = $this->userId();

        $this->view('web/home', [
            'title'          => 'Accueil',
            'featured'       => Product::featured(8),
            'newArrivals'    => Product::newArrivals(8),
            'bestSellers'    => Product::bestSellers(8),
            'promoProducts'  => Product::paginate(['sort' => 'recent'], 0, 6)->items,
            'coupons'        => $userId === null ? [] : Coupon::availableFor($userId),
            'wishlistIds'    => $userId === null ? [] : Wishlist::productIds($userId),
        ]);
    }

    public function shop(): never
    {
        $filters = $this->filtersFromRequest();
        $page = max(1, $this->request->int('page', 1));

        $paginator = Product::paginate($filters, $page - 1, $this->pageSize());
        $paginator->page = $page - 1;

        $this->view('web/shop', [
            'title'    => 'Boutique',
            'paginator' => $paginator,
            'filters'  => $filters,
            'brands'   => Product::brands(),
            'category' => $this->request->int('categorie', 0) > 0
                ? Category::find($this->request->int('categorie', 0))
                : null,
            'sorts'    => [
                'recent'     => 'Nouveautés',
                'price_asc'  => 'Prix croissant',
                'price_desc' => 'Prix décroissant',
                'name_asc'   => 'Nom (A-Z)',
                'best'       => 'Meilleures ventes',
                'rating'     => 'Mieux notés',
            ],
        ]);
    }

    public function search(): never
    {
        $keyword = trim((string) $this->request->input('q', ''));
        $page = max(1, $this->request->int('page', 1));

        $paginator = Product::paginate(
            ['keyword' => $keyword, 'sort' => $this->request->input('sort', 'recent')],
            $page - 1,
            $this->pageSize()
        );
        $paginator->page = $page - 1;

        $this->view('web/shop', [
            'title'     => 'Recherche : ' . ($keyword !== '' ? $keyword : 'tous les produits'),
            'paginator' => $paginator,
            'filters'   => ['keyword' => $keyword],
            'brands'    => Product::brands(),
            'category'  => null,
            'sorts'     => ['recent' => 'Nouveautés', 'price_asc' => 'Prix croissant', 'rating' => 'Mieux notés'],
        ]);
    }

    public function category(int $id): never
    {
        $category = Category::find($id);
        if ($category === null) {
            $this->abort('Catégorie introuvable', 404);
        }

        $filters = $this->filtersFromRequest();
        $filters['categoryId'] = $id;
        $page = max(1, $this->request->int('page', 1));

        $paginator = Product::paginate($filters, $page - 1, $this->pageSize());
        $paginator->page = $page - 1;

        $this->view('web/shop', [
            'title'     => $category->path(),
            'paginator' => $paginator,
            'filters'   => $filters,
            'brands'    => Product::brands(),
            'category'  => $category,
            'sorts'     => [
                'recent'     => 'Nouveautés',
                'price_asc'  => 'Prix croissant',
                'price_desc' => 'Prix décroissant',
                'name_asc'   => 'Nom (A-Z)',
                'best'       => 'Meilleures ventes',
            ],
        ]);
    }

    public function product(int $id): never
    {
        $product = Product::find($id);
        if ($product === null) {
            $this->abort('Produit introuvable', 404);
        }

        $product->incrementViews();
        $userId = $this->userId();

        $this->view('web/product', [
            'title'       => (string) $product->name,
            'product'     => $product,
            'related'     => Product::related($product, 4),
            'reviews'     => Review::forProduct($id),
            'inWishlist'  => $userId !== null && Wishlist::contains($userId, $id),
            'userReview'  => $userId !== null && Review::userHasReviewed($id, $userId),
        ]);
    }

    /** Dépôt d'un avis (modération obligatoire). */
    public function storeReview(int $id): never
    {
        $this->requireAuth();

        $product = Product::find($id);
        if ($product === null) {
            $this->abort('Produit introuvable', 404);
        }
        if (Review::userHasReviewed($id, (int) $this->userId())) {
            $this->failure('Vous avez déjà publié un avis sur ce produit.', product_url($id));
        }

        $rating = (float) $this->request->input('rating', 0);
        $comment = trim((string) $this->request->input('comment', ''));

        if ($rating < 1 || $rating > 5) {
            $this->failure('Veuillez choisir une note entre 1 et 5.', product_url($id));
        }
        if (mb_strlen($comment) < 5) {
            $this->failure('Votre commentaire doit contenir au moins 5 caractères.', product_url($id));
        }

        Review::create([
            'product_id'           => $id,
            'user_id'              => $this->userId(),
            'rating'               => $rating,
            'comment'              => $comment,
            'images'               => [],
            'status'               => Review::STATUS_PENDING,
            'is_verified_purchase' => Review::isVerifiedPurchase($id, (int) $this->userId()),
        ]);

        $this->success('Merci ! Votre avis sera publié après modération.', product_url($id));
    }

    /** Ajout / retrait d'un produit des favoris. */
    public function toggleWishlist(int $id): never
    {
        $this->requireAuth();

        if (Product::find($id) === null) {
            $this->abort('Produit introuvable', 404);
        }

        try {
            $added = Wishlist::toggle((int) $this->userId(), $id);
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/compte/favoris');
        }

        $this->success($added ? 'Produit ajouté à vos favoris.' : 'Produit retiré de vos favoris.', '/compte/favoris');
    }

    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    private function filtersFromRequest(): array
    {
        $filters = [
            'keyword'    => trim((string) $this->request->input('keyword', '')),
            'categoryId' => $this->request->input('categorie', null),
            'minPrice'   => $this->request->input('min', ''),
            'maxPrice'   => $this->request->input('max', ''),
            'brand'      => $this->request->input('marque', ''),
            'sort'       => (string) $this->request->input('tri', 'recent'),
            'inStock'    => $this->request->bool('disponible', false),
        ];

        if ($filters['categoryId'] !== null && $filters['categoryId'] !== '') {
            $filters['categoryId'] = (int) $filters['categoryId'];
        } else {
            $filters['categoryId'] = null;
        }

        return array_filter($filters, static fn ($value): bool => $value !== null && $value !== '');
    }

    private function pageSize(): int
    {
        return max(1, min(48, $this->request->int('limit', (int) config('shop.default_page_size', 12))));
    }
}