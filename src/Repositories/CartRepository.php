<?php

namespace OsamaElnagar\Cart\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;
use OsamaElnagar\Cart\Events\CartCleared;
use OsamaElnagar\Cart\Events\ItemAdded;
use OsamaElnagar\Cart\Events\ItemAdding;
use OsamaElnagar\Cart\Events\ItemDeleted;
use OsamaElnagar\Cart\Events\ItemUpdated;
use OsamaElnagar\Cart\Interfaces\CartRepositoryInterface;
use OsamaElnagar\Cart\Models\Cart;

class CartRepository implements CartRepositoryInterface
{
    use Macroable;

    protected Collection $items;

    public function __construct()
    {
        $this->items = collect();
    }

    /**
     * Get the cache key for the current session.
     */
    protected function getCacheKey(): string
    {
        $identifier = auth('web')->id() ?? Cart::getCookieID();
        return config('cart.cache.key_prefix', 'cart_cache_') . $identifier;
    }

    /**
     * Clear the cart cache.
     */
    protected function refreshCache(): void
    {
        if (config('cart.cache.enabled', true)) {
            Cache::forget($this->getCacheKey());
        }
        $this->items = collect();
    }

    protected function log(string $message, array $context = []): void
    {
        if (config('cart.log_enabled')) {
            Log::info('[OsamaCart] '.$message, $context);
        }
    }

    public function add(\Illuminate\Database\Eloquent\Model $cartable, int $quantity = 1)
    {
        $this->log('Adding item to cart', [
            'cartable_id' => $cartable->getKey(),
            'cartable_type' => $cartable->getMorphClass(),
            'quantity' => $quantity,
        ]);

        ItemAdding::dispatch($cartable, $quantity);

        $item = Cart::query()
            ->where('cartable_id', $cartable->getKey())
            ->where('cartable_type', $cartable->getMorphClass())
            ->first();

        if (! $item) {
            $this->log('Creating new cart item');
            $cart = Cart::query()->create([
                'user_id' => auth('web')?->id(),
                'cartable_id' => $cartable->getKey(),
                'cartable_type' => $cartable->getMorphClass(),
                'quantity' => $quantity,
            ]);
            $this->refreshCache();

            ItemAdded::dispatch($cart, $cartable);

            return $cart;
        }

        $this->log('Incrementing existing cart item quantity', ['item_id' => $item->id]);
        $item->increment('quantity', $quantity);
        $this->refreshCache();

        ItemAdded::dispatch($item, $cartable);

        return $item;
    }

    public function get(?string $calledBy = null): Collection
    {
        // Return local property if already populated in this request lifecycle to avoid repeated calls
        if ($this->items->count()) {
            return $this->items;
        }

        $this->log('Fetching cart items', ['called_by' => $calledBy]);

        if (config('cart.cache.enabled', true)) {
            $this->items = Cache::remember(
                $this->getCacheKey(),
                now()->addMinutes(config('cart.cache.lifetime', 60)),
                fn () => $this->fetchFromDatabase()
            );
        } else {
            $this->items = $this->fetchFromDatabase();
        }

        return $this->items;
    }

    protected function fetchFromDatabase(): Collection
    {
        $items = Cart::with('cartable')->get();
        $this->log('Fetched from DB', ['count' => $items->count()]);
        
        // Cast UUIDs to strings for consistent frontend usage
        $items->each(function ($item) {
            $item->id = (string) $item->id;
        });

        return $items;
    }

    public function update($id, int $quantity): void
    {
        $this->log('Updating cart item', ['id' => $id, 'quantity' => $quantity]);

        $item = Cart::query()->find($id);

        if (! $item) {
            $this->log('Cart item not found for update', ['id' => $id]);

            return;
        }

        $item->update([
            'quantity' => $quantity,
        ]);

        $this->refreshCache();

        ItemUpdated::dispatch($item, $quantity);
    }

    public function clean(): void
    {
        $cookieId = Cart::getCookieID();
        $this->log('Cleaning cart', ['cookie_id' => $cookieId]);

        Cart::where('cookie_id', $cookieId)->delete();
        $this->refreshCache();

        CartCleared::dispatch($cookieId);
    }

    public function delete($id): void
    {
        $this->log('Deleting cart item', ['id' => $id]);
        $id = (string) $id;

        $item = Cart::query()->where('id', $id)->first();

        if (! $item) {
            $this->log('Cart item not found for deletion', ['id' => $id]);

            return;
        }

        $item->delete();
        $this->refreshCache();

        ItemDeleted::dispatch($id);
    }

    public function total(): float
    {
        return $this->get()->sum(function ($item) {
            return $item->quantity * ($item->cartable->price ?? 0);
        });
    }

    public function itemsCount(): int
    {
        return $this->get()->count();
    }

    public function totalQuantity(): int
    {
        return $this->get()->sum('quantity');
    }

    public function clearAbandoned(int $hours): void
    {
        Cart::withoutGlobalScope(\OsamaElnagar\Cart\Scopes\CartScope::class)
            ->where('updated_at', '<', now()->subHours($hours))
            ->delete();
    }
}
