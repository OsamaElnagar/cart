<?php

namespace OsamaElnagar\Cart\Facades;

use Illuminate\Support\Facades\Facade;
use OsamaElnagar\Cart\Interfaces\CartRepositoryInterface;

/**
 * @method static \Illuminate\Support\Collection get(string $calledBy = null)
 * @method static \OsamaElnagar\Cart\Models\Cart add(\Illuminate\Database\Eloquent\Model $cartable, int $quantity = 1)
 * @method static void update($id, int $quantity)
 * @method static void delete($id)
 * @method static void clean()
 * @method static float total()
 * @method static int itemsCount()
 * @method static int totalQuantity()
 * @method static void clearAbandoned(int $hours)
 *
 * @see \OsamaElnagar\Cart\Repositories\CartRepository
 */
class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CartRepositoryInterface::class;
    }
}
