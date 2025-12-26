<?php

namespace OsamaElnagar\Cart\Interfaces;

use Illuminate\Support\Collection;

interface CartRepositoryInterface
{
    public function get(): Collection;

    public function add(\Illuminate\Database\Eloquent\Model $cartable, int $quantity);

    public function update($id, int $quantity);

    public function delete($id);

    public function clean();

    public function total(): float;

    public function itemsCount(): int;

    public function totalQuantity(): int;

    public function clearAbandoned(int $hours);
}
