<?php

namespace App\Contracts;

use Luxid\Sentinel\Contracts\Authenticatable;

interface UserInterface extends Authenticatable
{
    /**
     * Convert user to array for API responses.
     *
     * @return array
     */
    public function toArray(): array;
}
