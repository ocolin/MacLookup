<?php

/**
 * MacTrait.php
 *
 * Trait contains the functions related to working with
 * MAC address functionality.
 *
 * @package Ocolin/MacLookup
 * @author Colin Miller <ocolin@staff.cruzio.com>
 */

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

trait MacTrait
{

/* FORMAT MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address.
     * @return string Formatted MAC address.
     */
    public static function format_MAC( string $mac ) : string
    {
        return strtoupper( string: self::format_Pairs( mac: $mac ));
    }



/* FORMAT PAIRS IN MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Some devices may not include preceding zeros in pair values. This will
     * append leading zeros if they are missing
     *
     * @param string $mac MAC Address to format.
     * @return string Formatted MAC address.
     */
    public static function format_Pairs( string $mac ) : string
    {
        $mac = trim( string: $mac );
        if( strlen( string: $mac ) === 17 ) { return $mac; }

        $pairs = explode( separator: ':', string: $mac );
        foreach( $pairs as $key => $pair ) {
            if( strlen( string: $pair ) < 2 ) {
                $pairs[$key] = '0' . $pair;
            }
        }

        return implode( separator: ':', array: $pairs );
    }



/* NOT FOUND OBJECT
----------------------------------------------------------------------------- */

    /**
     * @return Row Not found object.
     */
    private static function not_Found() : Row
    {
        return new Row(
              registry: 'Not Found',
            assignment: 'Not Found',
                  name: 'Not Found',
               address: 'Not Found',
        );
    }



/* CHECK IF MAC IS PRIVATE
----------------------------------------------------------------------------- */

    /**
     * Check to see if a MAC address is a private one by checking
     * second character of address.
     *
     * @param string $mac MAC address
     * @return bool Whether the MAC address is private or not.
     */
    public static function is_Private( string $mac ) : bool
    {
        $private = [ '2', '6', 'A', 'E' ];
        $check_char = substr( string: $mac, offset: 1, length: 1 );

        return in_array( needle: $check_char, haystack: $private );
    }



/* FORMAT RAW MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Because the vendor list uses raw MAC formatting, we need to format the
     * query address to match against vendors.
     *
     * @param string $mac Mac address to format.
     * @return string Formatted address.
     */
    public static function format_Raw_MAC( string $mac ) : string
    {
        return str_replace( search: ':', replace: '', subject: $mac );
    }
}