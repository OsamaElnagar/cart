<?php

return [
    /*
     * Enable or disable logging for the package actions.
     */
    'log_enabled' => env('CART_LOG_ENABLED', false),

    /*
     * The model that represents the user.
     */
    'user_model' => \App\Models\User::class,

    /*
     * The table name for the carts.
     */
    'table_name' => 'carts',

    /*
     * Cookie settings for guest carts.
     */
    'cookie' => [
        'name' => 'cart_id',
        'lifetime' => 30 * 24 * 60, // 30 days
    ],

    /*
     * Cache settings.
     */
    'cache' => [
        'enabled' => env('CART_CACHE_ENABLED', true),
        'key_prefix' => 'cart_cache_',
        'lifetime' => 60, // Minutes
    ],
];
