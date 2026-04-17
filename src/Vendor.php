<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

readonly class Vendor
{

/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address. Used in lookups.
     * @param string $registry Name of IEEE registry.
     * @param string $assignment Assignment block.
     * @param string $name Name of vendor/organization.
     * @param string $address Address of vendor/organization.
     */
    public function __construct(
        public string $mac,
        public string $registry,
        public string $assignment,
        public string $name,
        public string $address,
    ) {}



/* CONVERT ARRAY INTO VENDOR OBJECT
----------------------------------------------------------------------------- */

    /**
     * @param array<int, string|null> $data Array of a vendor's info.
     * @return self Vendor object.
     */
    public static function fromArray( array $data ) : self
    {
        return new self(
            mac:        '',
            registry:   $data[0] ?? '',
            assignment: $data[1] ?? '',
            name:       $data[2] ?? '',
            address:    $data[3] ?? '',
        );
    }



/* PRIVATE MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac Mac address being queried.
     * @return self Vendor object.
     */
    public static function privateAddress( string $mac ) : self
    {
        return new self(
            mac:        $mac,
            registry:   'Private',
            assignment: '',
            name:       '',
            address:    '',
        );
    }



/* MAC ADDRESS NOT FOUND
----------------------------------------------------------------------------- */

    /**
     * @param string $mac Mac address being queried.
     * @return self Vendor object.
     */
    public static function notFound( string $mac ) : self
    {
        return new self(
            mac:        $mac,
            registry:   'Not Found',
            assignment: '',
            name:       '',
            address:    '',
        );
    }



/* INVALID MAC FORMAT
----------------------------------------------------------------------------- */

    /**
     * @param string $mac Mac address being queried.
     * @return self Vendor object.
     */
    public static function invalid( string $mac ) : self
    {
        return new self(
            mac:        $mac,
            registry:   'Invalid',
            assignment: '',
            name:       '',
            address:    '',
        );
    }
}