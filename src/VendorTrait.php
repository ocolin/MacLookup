<?php

/**
 * VendorTrait.php
 *
 * Trait contains the functions related to working with
 * the list of MAC vendors.
 *
 * @package Ocolin/MacLookup
 * @author Colin Miller <ocolin@staff.cruzio.com>
 */

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use Generator;

trait VendorTrait
{


/* LOAD VENDORS FROM FILE
----------------------------------------------------------------------------- */

    /**
     * @return Generator List of vendor entries.
     */
    public static function load_Vendors() : Generator
    {
        $handle = fopen( filename: self::VENDOR_FILE, mode: 'r' );

        if( $handle === false ) { return; }
        while(( $line = fgets( stream: $handle )) !== false )
        {
            yield json_decode( json: $line );
        }

        fclose( $handle );
    }



/* UPDATE LIST OF MAC VENDORS
----------------------------------------------------------------------------- */

    /**
     * @return bool True for success, false for failure.
     */
    public static function update() : bool
    {
        if( self::init_Vendor_File() === false ) { return false; }

        if( self::write_Block( block: self::OUI36 ) === false ) { return false; }
        if( self::write_Block( block: self::OUI28 ) === false ) { return false; }
        if( self::write_Block( block: self::OUI )   === false ) { return false; }

        return true;
    }



/* SAVE A MAC VENDOR ADDRESS BLOCK TO FILE
----------------------------------------------------------------------------- */

    /**
     * @param string $block Name of address block to save.
     * @return bool
     */
    public static function write_Block( string $block ) : bool
    {
        foreach( self::download_Vendors( url: $block ) AS $line )
        {
            if( $line[0] !== 'Registry' ) {
                $object = new Row(
                      registry: (string)$line[0],
                    assignment: (string)$line[1],
                          name: (string)$line[2],
                       address: (string)$line[3],
                );

                if( file_put_contents(
                    filename: self::VENDOR_FILE,
                        data: json_encode( value: $object ) . "\n",
                       flags: FILE_APPEND
                ) === false ) {  return false; }
            }
        }

        return true;
    }



/* DOWNLOAD A MAC VENDOR ADDRESS BLOCK
----------------------------------------------------------------------------- */

    /**
     * @param string $url URL to download file from.
     * @return Generator<int, list<string|null>> Vendor row.
     */
    public static function download_Vendors( string $url ) : Generator
    {
        $url = self::IEEE . $url;
        if(( $handle = fopen( filename: $url, mode: 'r' )) !== FALSE ) {
            while((
                $data = fgetcsv(
                    stream: $handle,
                    length: 1000,
                    escape: '\\'
                )) !== FALSE
            ) { yield $data; }

            fclose( stream: $handle );
        }
    }



/* INITIALIZE VENDOR FILE
----------------------------------------------------------------------------- */

    /**
     * @return bool|int True if successful, false if not.
     */
    public static function init_Vendor_File() : bool|int
    {
        return file_put_contents( filename: self::VENDOR_FILE, data: '' );
    }
}