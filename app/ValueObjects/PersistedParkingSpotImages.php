<?php

namespace App\ValueObjects;

class PersistedParkingSpotImages
{
    /**
     * @param  list<string>  $paths
     * @param  list<string>  $createdPaths
     * @param  list<string>  $temporaryPaths
     */
    public function __construct(
        public readonly array $paths,
        public readonly array $createdPaths,
        public readonly array $temporaryPaths,
    ) {}
}
