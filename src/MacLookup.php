<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use stdClass;


class MacLookup
{
    public static string $url = 'https://standards-oui.ieee.org';
    public static string $raw_file = __DIR__ . '/vendorRaw.txt';
    public static string $json_file = __DIR__ . '/vendor.json';



/* LOOK UP VENDOR BY MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Lookup a MAC address and get information about the vendor it belongs to.
     *
     * @param string $mac MAC address to search for.
     * @return object Vendor MAC address belongs to.
     */
    public static function Lookup( string $mac ) :  object
    {
        if( !self::validate_MAC( $mac ) ) {
            return new stdClass();
        }

        $vendor_mac = self::format_MAC( mac: $mac );
        if( self::is_Private( mac: $vendor_mac ) ) {
            $vendor = new stdClass();
            $vendor->mac = $vendor_mac;
            $vendor->organization = 'Private';

            return $vendor;
        }

        if( !file_exists( self::$json_file ) ) {
            self::update();
        }

        $vendors = self::load_JSON_Data();

        return self::find_Vendor( mac: $vendor_mac, vendors: $vendors );
    }



/* UPDATE VENDOR LIST
----------------------------------------------------------------------------- */

    /**
     * Update vendor data from website. Data gets periodically updated at IEEE.
     * This downloads the newest version of the vendor data.
     *
     * @return void
     */
    public static function update() : void
    {
        $raw = self::download_Raw_Data();
        $array = self::parse_Raw_Vendor_List( raw: $raw );
        self::save_JSON_Data( data: $array );
    }



/* VALIDATE MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Check that a MAC address is valid. This allows for MAC addresses that
     * leave out leading zeros in address pairs.
     *
     * @param string $mac MAC address to validate.
     * @return bool Whether MAC address is valid.
     */
    public static function validate_MAC( string $mac ) : bool
    {
        return (bool)preg_match(
            pattern: "#^([0-9A-Fa-f]{1,2}[:-]){5}([0-9A-Fa-f]{2})$#",
            subject: $mac
        );
    }



/* FIND VENDOR FROM LIST BY MAC
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address to search for.
     * @param array<object> $vendors List of MAC vendors to search in.
     * @return object
     */
    public static function find_Vendor( string $mac, array $vendors ) : object
    {
        foreach( $vendors as $vendor ) {
            if( $mac == $vendor->mac ) {
                return $vendor;
            }
        }

        return new stdClass();
    }



/* FORMAT MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address.
     * @return string Formatted MAC address.
     */
    public static function format_MAC( string $mac ) : string
    {
        $mac = self::format_Pairs( mac: $mac );

        return strtoupper( string: substr( string: $mac, offset: 0, length: 8 ));
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
        if( strlen( $mac ) === 17 ) { return $mac; }

        $pairs = explode( separator: ':', string: $mac );
        foreach( $pairs as $key => $pair ) {
            if( strlen( $pair ) < 2 ) {
                $pairs[$key] = '0' . $pair;
            }
        }

        return implode( separator: ':', array: $pairs );
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



/* PARSE RAW VENDOR LIST DATA
----------------------------------------------------------------------------- */

    /**
     * Convert raw IEEE vendor data into an array of objects
     *
     * @param string $raw Raw vendor data text.
     * @return array<object> Array of objects containing vendor data.
     */
    public static function parse_Raw_Vendor_List( string $raw ) : array
    {
        $output = [];
        $list = self::split_Raw_Vendor_List( raw: $raw );
        array_shift( array: $list ); // REMOVE HEADER
        foreach( $list as $entry )
        {
            $output[] = self::parse_Raw_Vendor( raw: $entry );
        }

        return $output;
    }



/* PARSE RAW VENDOR DATA
----------------------------------------------------------------------------- */

    /**
     * Parse an individual vendor text into an object.
     *
     * @param string $raw Raw data text for an individual vendor.
     * @return object Object containing vendor data.
     */
    public static function parse_Raw_Vendor( string $raw ) : object
    {
        $vendor = new stdClass();
        $rows = explode( separator: "\n", string: $raw);
        $first_row = array_shift( array: $rows );
        list( $mac, $dnu, $organization ) = preg_split(
            pattern: "#\s{2,}#", subject: $first_row
        );
        $vendor->organization = trim( string: $organization );
        $vendor->mac = str_replace( search: '-', replace: ':', subject: $mac );
        $second_row = array_shift( array: $rows );
        list( $vendor->company_id, $dnu, $dnu ) = preg_split(
            pattern: "#\s{2,}#", subject: $second_row
        );
        $vendor->address = self::parse_Address( $rows );

        return $vendor;
    }



/* PARSE VENDOR ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Format the address of a vendor.
     *
     * @param array<string> $raw Raw vendor address text.
     * @return string Formatted address string.
     */
    public static function parse_Address( array $raw ) : string
    {
        $output = '';
        foreach( $raw as $line )
        {
            $line = trim( string: $line );
            if( !empty( $line )) {
                $output .= $line . "\n";
            }
        }

        return trim( string: $output );
    }



/* SPLIT RAW VENDOR LIST INTO ARRAY OF VENDORS
----------------------------------------------------------------------------- */

    /**
     * Split raw IEEE vendor text into and array of individual vendor strings.
     *
     * @param string $raw Raw vendor data text.
     * @return array<string> Array of vendor data strings.
     */
    public static function split_Raw_Vendor_List( string $raw ) : array
    {
        $output = [];
        $lines = explode( separator: "\n", string: $raw );
        $end = count( value: $lines ) - 1;
        $vendor = '';

        for( $number = 0; $number <= $end; $number++ )
        {
            $line = $lines[$number];
            if(
                str_contains( haystack: $line, needle: '(hex)' ) AND
                $vendor !== ''
            ) {
                $output[] = trim( string: $vendor );
                $vendor = '';
                $number--;
            }
            else {
                $vendor .= $line. "\n";
                if( $number == $end ) {
                    $output[] = trim( string: $vendor );
                }
            }
        }

        return $output;
    }



/* UPDATE RAW DATA TO FILE
----------------------------------------------------------------------------- */

    /**
     * If raw data is saved as a file, this can update tht file with
     * the newest IEEE vendor data.
     *
     * @param ?string $filepath Optional path to save raw text file.
     * @param ?string $url Optional URL to download raw vendor text from.
     * @return void
     */
    public static function update_Raw_Data(
        ?string $filepath = null ,
        ?string $url = null
    ) : void
    {
        $data = self::download_Raw_Data( url: $url );
        self::save_Raw_Data( data: $data, filepath: $filepath );
    }



/* DOWNLOAD RAW VENDOR DATA FROM WEBSITE
----------------------------------------------------------------------------- */

    /**
     * Download raw IEEE vendor data from website.
     *
     * @param ?string $url Optional URL to download vendor data
     * @return string Text of downloaded vendor data
     */
    public static function download_Raw_Data( ?string $url = null ) : string
    {
        $url = $url ?? self::$url;

        return (string)file_get_contents( filename: $url );
    }



/* LOAD RAW DATA FROM A FILE
----------------------------------------------------------------------------- */

    /**
     * Load file containing raw IEEE vendor data.
     *
     * @param ?string $filepath Optional path to load raw vendor text file.
     * @return string Vendor data raw text
     */
    public static function load_Raw_Data( ?string $filepath = null ) : string
    {
        $path = $filepath ?? self::$raw_file;

        return (string)file_get_contents( filename: $path );
    }



/* SAVE RAW DATA AS A FILE
----------------------------------------------------------------------------- */

    /**
     * Save raw vendor data to a file.
     *
     * @param string $data Raw IEEE Vendor text.
     * @param ?string $filepath Optional path to save text file.
     * @return int|false Success/failure of file save.
     */
    public static function save_Raw_Data(
         string $data,
        ?string $filepath = null
    ) : int|false
    {
        $path = $filepath ?? self::$raw_file;

        return file_put_contents( filename: $path, data: $data );
    }



/* SAVE JSON DATA TO FILE
----------------------------------------------------------------------------- */

    /**
     * Save list of vendor objects as a JSON file.
     *
     * @param array<object> $data Array of vendor objects.
     * @param ?string $filepath Optional path to save JSON file.
     * @return int|false Indicating success/failure of file save.
     */
    public static function save_JSON_Data(
          array $data,
        ?string $filepath = null
    ) : int|false
    {
        $path = $filepath ?? self::$json_file;
        $json = json_encode( value: $data );

        return file_put_contents( filename: $path, data: $json );
    }



/* LOAD JSON DATA FROM FILE
----------------------------------------------------------------------------- */

    /**
     * Load vendor JSON data stored in a file
     *
     * @param ?string $filepath Optional file path to load.
     * @return array<object> Array of vendor objects.
     */
    public static function load_JSON_Data( ?string $filepath = null ) : array
    {
        $path = $filepath ?? self::$json_file;

        return json_decode(
            (string)file_get_contents( filename: $path )
        ) ?? [];
    }
}