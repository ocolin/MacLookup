<?php

declare( strict_types = 1 );

namespace Ocolin\Maclookup;

use Exception;

class MacLookup
{
    /**
     * @var string URL of IEEE MA-L List.
     */
    private static string $ma_l_url = 'https://standards-oui.ieee.org/oui/oui.csv';

    /**
     * @var string URL of IEEE MA-M List.
     */
    private static string $ma_m_url = 'https://standards-oui.ieee.org/oui28/mam.csv';

    /**
     * @var string URL of IEEE MA-S list.
     */
    private static string $ma_s_url = 'https://standards-oui.ieee.org/oui36/oui36.csv';

    /**
     * @var string Default JSON file location.
     */
    private static string $vendor_file = __DIR__ . '/vendor.json';


/* LOOK UP MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address to look up.
     * @param string|null $file Optional json file of vendors.
     * @return Row Vendor object.
     * @throws Exception Throw error if mac address is invalid.
     */
    public static function lookup( string $mac, ?string $file = null ) : Row
    {
        # CHECK IF IT IS A PRIVATE ADDRESS
        if( self::is_Private( mac: $mac )) {
            return new Row(
                  registry: 'Private',
                assignment: strtoupper( substr( string: $mac, offset: 0, length: 1 )),
                      name: 'Private',
                   address: 'Private'
            );
        }

        $o = new self();
        $file = $file ?? self::$vendor_file;

        # GET VENDORS IF MISSING
        if( !file_exists( filename: $file )) {
            $result = $o->update( file: $file );
            if( gettype( $result ) === 'string' ) {
                throw new Exception( $result );
            }
        }

        # LOAD VENDOR DATA
        $vendors = $o->load_JSON( file: $file );

        # SEARCH VENDOR LIST
        return self::find_Vendor( mac: $mac, vendors: $vendors );
    }



/* UPDATE VENDOR LIST
----------------------------------------------------------------------------- */

    /**
     * Refresh list of vendors from IEEE.
     *
     * @param string|null $file Path of an alternate storage file.
     * @return true|string Return true if successful, or error string if not.
     */
    public function update( ?string $file = null ) : true|string
    {
        $file = $file ?? self::$vendor_file;

        # CHECK TO SEE IF THE FILE EXISTS.
        if( !file_exists( filename: $file )) {
            $handle = fopen( filename: $file, mode: 'w' );
            if( !$handle ) {
                return "Unable to create file for vendor list. Check permissions.";
            }
        }

        # CHECK TO SEE IF FILE IS WRITABLE
        if( !is_writable( filename: $file )) {
            return "Unable to write to file for vendor list. Check permissions.";
        }

        # DOWNLOAD THE # REGISTRY LISTS
        $data = $this->download_All();

        # MAKE SURE THERE IS DATA BEFORE OVERWRITING EXISTING FILE
        if( empty( $data )) { return "Unable to load vendor lists."; }

        # SAVE DATA AS A JSON FILE
        return self::save_JSON( data: $data, file: $file ) ?: "Unable to save JSON data";
    }



/* FIND VENDOR
----------------------------------------------------------------------------- */

    /**
     * Search through list of vendors for a MAC address.
     *
     * @param string $mac Mac address to search for.
     * @param array<object> $vendors List of vendors to search through.
     * @return Row Return the vendor.
     */
    public static function find_Vendor( string $mac, array $vendors ) : Row
    {
        $mac = self::format_Raw_MAC( mac: self::format_Mac( mac: $mac ));
        foreach( $vendors as $vendor ) {
            if(
                str_starts_with( haystack: $mac, needle: $vendor->assignment ) // @phpstan-ignore property.notFound
            ) {
                return new Row(
                    registry: $vendor->registry,  // @phpstan-ignore property.notFound
                  assignment: $vendor->assignment,// @phpstan-ignore property.notFound
                        name: $vendor->name,      // @phpstan-ignore property.notFound
                     address: $vendor->address    // @phpstan-ignore property.notFound
                );
            }
        }

        return self::not_Found();
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

        $mac = self::format_MAC( mac: $mac );
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



/* LOAD VENDOR LIST FROM FILE
----------------------------------------------------------------------------- */

    /**
     * The vendor list gets stored as a file so as to not have to download
     * with every query.
     *
     * @param string|null $file Optional vendor file.
     * @return array<object> List of vendors.
     */
    public function load_JSON( ?string $file = null ) : array
    {
        $file = $file ?? self::$vendor_file;
        set_time_limit( seconds: 300 );

        if(( $text = file_get_contents( $file )) !== false ) {
            $json = json_decode( json: $text );
            if( gettype( $json ) === 'array' ) {
                return $json;
            }
        }

        return [];
    }



/* DOWNLOAD ALL IEEE VENDOR LISTS
----------------------------------------------------------------------------- */

    /**
     *  Get all 3 vendor lists (MA-S, MA-M, MA-L)
     * @return array<Row> List of IEEE vendors.
     */
    private function download_All() : array
    {
        return array_merge(
            $this->download_CSV( url: self::$ma_s_url ),
            $this->download_CSV( url: self::$ma_m_url ),
            $this->download_CSV( url: self::$ma_l_url )
        );
    }



/* SAVE VENDOR LIST TO A JSON FILE
----------------------------------------------------------------------------- */

    /**
     * Store the IEEE vendor lists to a JSON file.
     *
     * @param array<Row> $data List of registry objects.
     * @param string|null $file Path of json file to store data in.
     * @return bool Whether save was successful or not.
     */
    private static function save_JSON( array $data, ?string $file = null ) : bool
    {
        return (bool)file_put_contents(
            filename: $file ?? self::$vendor_file,
            data: json_encode( value: $data )
        );
    }



/* DOWNLOAD A CSV FILE FROM REMOTE SITE
----------------------------------------------------------------------------- */

    /**
     * @param string $url URL of IEEE registry.
     * @return array<Row> List of vendors.
     */
    private function download_CSV( string $url ) : array
    {
        $output = [];
        if(( $handle = fopen( filename: $url, mode: 'r' )) !== FALSE ) {
            while(
                ( $data = fgetcsv( stream: $handle, length: 1000, escape: '\\' )) !== FALSE
            ) {
                if( $data[0] !== "Registry" ) {
                    $output[] = new Row(
                          registry: (string)$data[0],
                        assignment: (string)$data[1],
                              name: (string)$data[2],
                           address: (string)$data[3],
                    );
                }
            }
        }

        return $output;
    }



/* NOT FOUND OBJECT
----------------------------------------------------------------------------- */

    private static function not_Found() : Row
    {
        return new Row(
              registry: 'Not Found',
            assignment: 'Not Found',
                  name: 'Not Found',
               address: 'Not Found',
        );
    }
}