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

use Ocolin\MacLookup\Exceptions\FileDownloadException;
use Ocolin\MacLookup\Exceptions\FileLoadException;
use Ocolin\MacLookup\Exceptions\JsonParseException;

class MacMem
{
    /**
     * IEEE standards website URL.
     */
    public const string IEEE = 'https://standards-oui.ieee.org/';

    /**
     * MAC Address Block Large (MA-L)
     */
    public const string OUI = 'oui/oui.csv';

    /**
     * MAC Address Block Medium (MA-M)
     */
    public const string OUI28 = 'oui28/mam.csv';

    /**
     * MAC Address Block Small (MA-S)
     */
    public const string OUI36 = 'oui36/oui36.csv';

    /**
     * Name of file to store vendors content.
     */
    public const string VENDOR_FILE =  __DIR__ . '/' . 'vendors.json';

    /**
     * @var Row[] List of MAC vendors.
     */
    public array $vendors;


/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    /**
     * @param int $timeout Batch processing could take more time than
     * PHP INI default.
     * @throws FileLoadException Unable to load vendors file.
     * @throws JsonParseException Unable to parse vendors file.
     * @throws FileDownloadException Unable to download vendors data.
     */
    public function __construct( int $timeout = 300 )
    {
        set_time_limit( seconds: $timeout );
        if(
            !file_exists( filename: self::VENDOR_FILE ) OR
                filesize( filename: self::VENDOR_FILE ) == 0
        ) { self::update(); }

        $this->vendors = self::load_Vendors();
    }

    use MacTrait;


/* LOOKUP MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address to search for.
     * @return Row Vendor row object.
     */
    public function lookup( string $mac ) : Row
    {
        # VALIDATE MAC
        if( !filter_var( value: $mac, filter: FILTER_VALIDATE_MAC )) {
            return self::not_Found();
        }

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

        # SEARCH TABLE
        foreach( $this->vendors as $vendor ) {
            if( str_starts_with( haystack: $mac, needle: $vendor->assignment)) {
                return $vendor;
            }
        }

        return self::not_Found();
    }



/* UPDATE VENDOR DATA
----------------------------------------------------------------------------- */

    /**
     * @return bool If update was successful or not.
     * @throws FileDownloadException|JsonParseException
     */
    public static function update() : bool
    {
        $vendors = json_encode( value: self::download_Vendors());
        if( $vendors === false ) {
            throw new JsonParseException(
                message: 'Unable to parse vendors from IEEE.'
            );
        }

        return (bool)file_put_contents(
            filename: self::VENDOR_FILE, data: $vendors
        );
    }



/* LOAD VENDOR LIST
----------------------------------------------------------------------------- */

    /**
     * Load vendors into memory.
     *
     * @return Row[] List of MAC vendors.
     * @throws JsonParseException Unable to read vendor file.
     * @throws FileLoadException Unable to load vendors file.
     */
    public static function load_Vendors() : array
    {
        $vendors = [];
        $string = file_get_contents( filename: self::VENDOR_FILE );
        if( $string === false ) {
            throw new FileLoadException( message: 'Unable to read vendors file' );
        }

        $json = json_decode( json: $string  );
        if( !is_array( value: $json ) ) {
            throw new JsonParseException( message: 'Unable to parse json vendor list.' );
        }

        foreach( $json as $vendor ) {
            $vendors[] = new Row(                // We know these exist
                  registry: $vendor->registry,   // @phpstan-ignore property.nonObject
                assignment: $vendor->assignment, // @phpstan-ignore property.nonObject
                      name: $vendor->name,       // @phpstan-ignore property.nonObject
                   address: $vendor->address,    // @phpstan-ignore property.nonObject
            );
        }

        return $vendors;
    }


/*
----------------------------------------------------------------------------- */

    /**
     * @return Row[] List of vendor rows.
     * @throws FileDownloadException Unable to download file(s) from IEEE.
     */
    public static function download_Vendors() : array
    {
        return array_merge(
            self::download_Vendor_File( uri: self::OUI36 ),
            self::download_Vendor_File( uri: self::OUI28 ),
            self::download_Vendor_File( uri: self::OUI )
        );
    }



/*
----------------------------------------------------------------------------- */

    /**
     * @param string $uri URI of CSV file at IEEE
     * @return Row[] List of vendor rows.
     * @throws FileDownloadException Unable to load file.
     */
    public static function download_Vendor_File( string $uri ) : array
    {
        $output = [];
        $csv = file_get_contents( filename: self::IEEE . $uri );
        if( $csv === false ) {
            throw new FileDownloadException(
                message: "Failed to download vendor file: {$uri}"
            );
        }
        $csv = trim( string: $csv );

        $rows = explode( separator: "\n", string: $csv );
        foreach( $rows as $row )
        {
            $array = str_getcsv( string: $row, escape: '' );
            $output[] = new Row(
                  registry: $array[0] ?? '',
                assignment: $array[1] ?? '',
                      name: $array[2] ?? '',
                   address: $array[3] ?? '',
            );

        }

        return $output;
    }
}