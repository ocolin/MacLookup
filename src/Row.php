<?php

declare( strict_types = 1 );

namespace Ocolin\Maclookup;

readonly class Row
{

    /**
     * @param string $registry IEE registry type (MA-L, MA-M, MA-S)
     * @param string $assignment MAC assignment range.
     * @param string $name Name of organization.
     * @param string $address Address of organization.
     */

    public function __construct(
        public string $registry,
        public string $assignment,
        public string $name,
        public string $address,
    ){}
}