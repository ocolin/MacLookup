<?php

/**
 * MacLookup: A simple tool to lookup MAC vendor from a MAC addres.
 *
 * @author  Colin Miller <ocolin@staff.cruzio.com>
 * @copyright Copyright(c) 2025 Colin Miller
 * @license MIT (opensource.org)
 * @version 3.0
 */

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

class Row
{
    /**
     * @var string Registry type.
     */
    public string $registry;

    /**
     * @var string Address assignment.
     */
    public string $assignment;

    /**
     * @var string Name of vendor.
     */
    public string $name;

    /**
     * @var string Mailing address of vendor.
     */
    public string $address;


/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    public function __construct(
        ?string $registry   = null,
        ?string $assignment = null,
        ?string $name       = null,
        ?string $address    = null,
    ){
        if( $registry   !== null ) { $this->registry = $registry; }
        if( $assignment !== null ) { $this->assignment = $assignment; }
        if( $name       !== null ) { $this->name = $name; }
        if( $address    !== null ) { $this->address = $address; }
    }
}