<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

interface LookupInterface
{
    /**
     * @param string $mac MAC address to look up.
     * @return Vendor MAC vendor/organization of address.
     */
    public function lookup( string $mac ): Vendor;

    /**
     * @param string[] $macs List of MAC addresses to query.
     * @return Vendor[] List of vendors to match with addresses.
     */
    public function bulkLookup( array $macs ): array;
}