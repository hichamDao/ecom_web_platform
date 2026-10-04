<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Pagination : stocke une page de résultats et produit
 * - le format JSON compatible Spring Boot (content/totalElements/number/size)
 * - les liens HTML de la boutique.
 */
final class Paginator
{
    /** @var array<int, mixed> */
    public array $items;
    public int $total;
    public int $page;
    public int $size;

    /** @param array<int, mixed> $items */
    public function __construct(array $items, int $total, int $page = 0, int $size = 12)
    {
        $this->items = array_values($items);
        $this->total = max(0, $total);
        $this->page = max(0, $page);
        $this->size = max(1, $size);
    }

    /** @param array<int, mixed> $items */
    public static function make(array $items, int $total, int $page, int $size): self
    {
        return new self($items, $total, $page, $size);
    }

    /** Retourne une nouvelle pagination avec les items transformés. */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items), $this->total, $this->page, $this->size);
    }

    public function totalPages(): int
    {
        return (int) max(1, ceil($this->total / $this->size));
    }

    public function hasPages(): bool
    {
        return $this->totalPages() > 1;
    }

    public function offset(): int
    {
        return $this->page * $this->size;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content'       => $this->items,
            'totalElements' => $this->total,
            'totalPages'    => $this->totalPages(),
            'number'        => $this->page,
            'size'          => $this->size,
            'first'         => $this->page === 0,
            'last'          => $this->page >= $this->totalPages() - 1,
            'empty'         => $this->isEmpty(),
        ];
    }

    // -----------------------------------------------------------------
    // Liens HTML (la boutique affiche des pages en base 1)
    // -----------------------------------------------------------------

    public function currentPage(): int
    {
        return $this->page + 1;
    }

    public function url(int $page, array $query = [], ?string $path = null): string
    {
        $path ??= Request::instance()->path();
        $current = $this->query();

        $query = array_merge($current, $query, ['page' => max(1, $page)]);

        return url($path) . '?' . http_build_query($query);
    }

    /** @return array<int, int> */
    public function window(int $radius = 2): array
    {
        $current = $this->currentPage();
        $start = max(1, $current - $radius);
        $end = min($this->totalPages(), $current + $radius);

        return range($start, max($start, $end));
    }

    /** @return array<string, mixed> */
    private function query(): array
    {
        $query = $_GET;
        unset($query['page']);

        return array_filter($query, static fn ($value): bool => $value !== null && $value !== '');
    }
}
