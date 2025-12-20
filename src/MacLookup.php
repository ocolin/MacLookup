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

use Exception;

set_time_limit( seconds: 300 );

class MacLookup
{
    /**
     * IEEE standards website URL.
     */
    private const string IEEE = 'https://standards-oui.ieee.org/';

    /**
     * MAC Address Block Large (MA-L)
     */
    private const string OUI = 'oui/oui.csv';

    /**
     * MAC Address Block Medium (MA-M)
     */
    private const string OUI28 = 'oui28/mam.csv';

    /**
     * MAC Address Block Small (MA-S)
     */
    public const string OUI36 = 'oui36/oui36.csv';

    /**
     * Name of file to store vendors content.
     */
    public const string VENDOR_FILE =  __DIR__ . '/' . 'vendors';

    use VendorTrait;
    use MacTrait;


/* LOOKUP MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac
     * @return Row
     * @throws Exception
     */
    public static function lookup( string $mac ) : Row
    {
        $mac = self::format_Raw_MAC( mac: self::format_Mac( mac: $mac ));

        # CHECK IF IT IS A PRIVATE ADDRESS
        if( self::is_Private( mac: $mac )) {
            return new Row(
                  registry: 'Private',
                assignment: strtoupper( string: $mac ),
                      name: 'Private',
                   address: 'Private',
            );
        }

        if( !file_exists( filename: self::VENDOR_FILE )) {
            self::update();
        }

        foreach( self::load_Vendors() as $vendor )
        {
            if(
                is_object( value: $vendor ) AND
                isset( $vendor->assignment ) AND
                str_starts_with( haystack: $mac, needle: $vendor->assignment )
            ) {
                return new Row(
                      registry: $vendor->registry ?? '',
                    assignment: $vendor->assignment,
                          name: $vendor->name ?? '',
                       address: $vendor->address ?? '',
                );
            }
        }

        return self::not_Found();
    }
}