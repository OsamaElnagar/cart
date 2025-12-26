# Osama Cart Package

A flexible, polymorphic cart system for Laravel applications. This package allows you to add any model to a cart (Products, Services, etc.) and handles both authenticated and guest (cookie-based) shopping carts seamlessly.

## Features

- 🔄 **Polymorphic**: Add any Eloquent model to the cart.
- 🍪 **Guest Support**: Automatically handles guest carts using UUID-based cookies.
- 👤 **Auth Integration**: Links guest carts to users upon creation/authentication.
- 🛠 **Configurable**: Customize table names, models, and cookie settings.
- 🧠 **Smart Scoping**: Automatically filters carts by the current session/cookie.
- ⚡ **Auto-Caching**: Intelligent caching system that refreshes on updates for optimal performance.
- 🔌 **Ready-to-use Traits**: Simple integration with `User` and `Product` models.

## Installation

You can install the package via composer:

```bash
composer require osamaelnagar/cart
```

## Setup

### 1. Publish Configuration

```bash
php artisan vendor:publish --tag=cart-config
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Prepare Your Models

#### The User Model

Add the `Carter` trait to your User model to enable the relationship:

```php
use OsamaElnagar\Cart\Traits\Carter;

class User extends Authenticatable {
    use Carter;
}
```

#### The Cartable Model (e.g., Product)

Add the `Cartable` trait to any model you want to add to the cart:

```php
use OsamaElnagar\Cart\Traits\Cartable;

class Product extends Model {
    use Cartable;
}
```

## Basic Usage

### Using the Facade

```php
use OsamaElnagar\Cart\Facades\Cart;

// Add an item to the cart
Cart::add($product, $quantity = 1);

// Get all items in the current cart (automatically scoped by session/cookie)
$items = Cart::get();

// Update quantity
Cart::update($cartItemId, $newQuantity);

// Get total price (assumes cartable model has a 'price' attribute)
$total = Cart::total();

// Get the number of unique items types in cart
$count = Cart::itemsCount();

// Get the total quantity of all items combined
$qty = Cart::totalQuantity();

// Remove specific item
Cart::delete($cartItemId);

// Clear entire cart
Cart::clean();
```

### Automatic Scoping

The package uses a `CartScope` that automatically filters results by the `cart_id` cookie. You don't need to worry about manually filtering by user or session ID for basic retrieval.

```php
// This will only return items belonging to the current visitor's session
$myCartItems = \OsamaElnagar\Cart\Models\Cart::all();
```

## Hooks & Events (Professional Control)

The package dispatches events throughout the cart lifecycle, allowing you to hook into any action:

- `OsamaElnagar\Cart\Events\ItemAdding`: Before an item is added.
- `OsamaElnagar\Cart\Events\ItemAdded`: After an item is added/incremented.
- `OsamaElnagar\Cart\Events\ItemUpdated`: After an item quantity is updated.
- `OsamaElnagar\Cart\Events\ItemDeleted`: After an item is removed.
- `OsamaElnagar\Cart\Events\CartCleared`: After the cart is emptied.

### Example: Listening for Cart Additions

In your `EventServiceProvider`:

```php
protected $listen = [
    \OsamaElnagar\Cart\Events\ItemAdded::class => [
        \App\Listeners\SyncCartToMarketingTool::class,
    ],
];
```

## Extensions & Macros

The `Cart` repository uses Laravel's `Macroable` trait, meaning you can add your own methods to the `Cart` facade at runtime.

```php
// In a ServiceProvider boot method
Cart::macro('getWeight', function () {
    return $this->get()->sum(fn($item) => $item->cartable->weight * $item->quantity);
});

// Use it anywhere
$totalWeight = Cart::getWeight();
```

## Configuration

Check `config/cart.php` for customization options:

- `log_enabled`: Enable or disable package-wide logging (useful for debugging).
- `user_model`: The model used for user authentication.
- `table_name`: The database table name for cart items.
- `cache`: Configure caching behavior (enable/disable, lifetime, prefix).
- `cookie`: Settings for the guest cart cookie (name, lifetime).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
