<?php

namespace App\Exceptions;

use RuntimeException;

class UnavailableCartItemsException extends RuntimeException
{
    /**
     * @param array<int, int> $listingIds
     */
    public function __construct(
        private readonly array $listingIds,
        string $message = 'One or more items in your basket are no longer available.'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int, int>
     */
    public function listingIds(): array
    {
        return $this->listingIds;
    }
}
