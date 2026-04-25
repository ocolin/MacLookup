<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

final class Mac
{

/* NORMALIZE MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Format MAC address.
     *
     * @param string $mac Original MAC address.
     * @return string Colon formatted MAC address. Returns original MAC if
     * it is not valid. MAC should be checked for invalid first.
     */
    public static function normalize( string $mac ) : string
    {
        $clean = self::clean( mac: $mac );
        if( strlen( $clean ) !== 12 || !ctype_xdigit( $clean )) { return $mac; }
        $pairs = str_split( string: $clean, length: 2 );

        return implode( separator: ':', array: $pairs );
    }



/* CHECK IF ADDRESS IS PRIVATE SPACE
----------------------------------------------------------------------------- */

    /**
     * Check if a MAC address is private.
     *
     * @param string $mac Original MAC address.
     * @return bool If MAC address is private.
     */
    public static function isPrivate( string $mac ) : bool
    {
        $mac = self::clean( mac: $mac );
        if( strlen( $mac ) < 2 ) { return false; }

        return ( hexdec( $mac[1] ) & 0x2 ) === 0x2;
    }





/* CHECK IF ADDRESS IS VALID
----------------------------------------------------------------------------- */

    /**
     * Check if MAC address is valid.
     *
     * @param string $mac Original MAC address.
     * @return bool If MAC address is a valid address.
     */
    public static function isValid( string $mac ) : bool
    {
        $mac = self::clean( mac: $mac );

        return strlen( $mac ) === 12 && ctype_xdigit( $mac );
    }



/* STRIP SEPARATORS FROM MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Strip characters from MAC address.
     *
     * @param string $mac Original MAC address.
     * @return string MAC address stripped of separators.
     */
    public static function strip( string $mac ) : string
    {
        return self::clean( mac: $mac );
    }



/* CLEAN MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Convert MAC to plain hex.
     *
     * @param string $mac Original MAC address.
     * @return string Cleaned MAC address.
     */
    private static function clean( string $mac ): string
    {
        $mac = strtoupper( $mac );

        if(
            str_contains( haystack: $mac, needle: ':' ) OR
            str_contains( haystack: $mac, needle: '-' )
        ) {
            $mac = str_replace( search: '-', replace: ':', subject: $mac );
            $pairs = explode( separator: ':', string: $mac );
            foreach( $pairs as &$pair ) {
                if( strlen( $pair ) < 2 ) { $pair = '0' . $pair; }
            }
            unset( $pair );

            return implode( separator: '', array: $pairs );
        }

        if( str_contains( haystack: $mac, needle: '.' )) {
            return str_replace( search: '.', replace: '', subject: $mac );
        }

        return $mac;
    }
}