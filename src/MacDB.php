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
use PDO;


class MacDB
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
    public const string VENDOR_FILE = __DIR__ . '/' . 'vendors.db';


    private PDO $db;

/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    /**
     * @param int $timeout PHP Timeout in seconds. Adjustable for heavy
     * use cases.
     * @throws FileDownloadException Error downloading vendor files.
     */
    public function __construct( int $timeout = 300 )
    {
        set_time_limit( seconds: $timeout );
        $this->db = new PDO( dsn: 'sqlite:' . self::VENDOR_FILE );

        if( !file_exists( filename: self::VENDOR_FILE )) {
            $this->update();
        }
    }

    use MacTrait;


/* LOOKUP MAC VENDOR
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address to query.
     * @return Row Vendor row object.
     */
    public function lookup( string $mac ) : Row
    {
        # VALIDATE MAC
        if( !filter_var( value: $mac, filter: FILTER_VALIDATE_MAC )) {
            return self::not_Found();
        }

        $mac = self::format_Raw_MAC( mac: self::format_Mac( mac: $mac ));

        if( self::is_Private( mac: $mac )) {
            return new Row(
                  registry: 'Private',
                assignment: strtoupper( string: $mac ),
                      name: 'Private',
                   address: 'Private',
            );
        }

        $output = $this->search_DB( mac: $mac);
        if( $output === false ) { return self::not_Found(); }

        return $output;
    }



/* UPDATE VENDOR DATA
----------------------------------------------------------------------------- */

    /**
     * @return bool Vendor update status
     * @throws FileDownloadException Unable to download vendor files.
     */
    public function update() : bool
    {
        // CHECK FOR DB
        if( !file_exists( filename: self::VENDOR_FILE )) {
            $status = $this->create_Table();
        }
        else { $status = $this->clear_Table(); }
        if( $status === false ) { return false; }

        // GET S BLOCK
        $status = $this->insert_Registry(
            vendors: self::download_Vendor_File( uri: MacDB::OUI36 )
        );
        if( $status === false ) { return false; }

        // GET M BLOCK
        $status = $this->insert_Registry(
            vendors:  self::download_Vendor_File( uri: MacDB::OUI28 )
        );
        if( $status === false ) { return false; }

        // GET L BLOCK
        return $this->insert_Registry(
            vendors: self::download_Vendor_File( uri: MacDB::OUI )
        );
    }



/* SEARCH DATABASE
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address with no colons.
     * @return Row|false Vendor object or false if not found.
     */
    public function search_DB( string $mac ) : Row | false
    {
        $query = $this->db->prepare( query: "
            SELECT registry, assignment, name, address
              FROM vendors
             WHERE assignment = SUBSTRING( :mac, 0, LENGTH( assignment ) + 1)
             LIMIT 1
        ");
        $query->bindValue( param: ':mac', value: $mac );
        $query->execute();
        $query->setFetchMode( PDO::FETCH_CLASS, Row::class );

        $response = $query->fetch();

        if( !($response instanceof Row )) { return false; }

        return $response;
    }



/* CLEAR DATABASE
----------------------------------------------------------------------------- */

    /**
     * Erase current contents of vendor database.
     *
     * @return int|false Status of emptying DB table.
     */
    public function clear_Table() : int | false
    {
        return $this->db->exec( statement: 'DELETE FROM vendors' );
    }



/* INSERT REGISTRY TO DB
----------------------------------------------------------------------------- */

    /**
     * Insert a vendor registry into the SQL database.
     *
     * @param Row[] $vendors List of vendor rows.
     * @return bool Status of DB insertion.
     */
    public function insert_Registry( array $vendors ) : bool
    {
        $data = [];
        foreach ( $vendors as $vendor )
        {
            $data[] = [
                $vendor->registry,
                $vendor->assignment,
                $vendor->name,
                $vendor->address
            ];
        }

        $sql = 'INSERT INTO vendors( registry, assignment, name, address ) VALUES ' .
            str_repeat( string: '(?, ?, ?, ?), ', times: count( value: $data ) - 1 )
            . '(?, ?, ?, ?)';

        $query = $this->db->prepare( query: $sql );

        return $query->execute( params: array_merge( ...$data ));
    }



/* DOWNLOAD A VENDOR REGISTRY
----------------------------------------------------------------------------- */

    /**
     * Download CSV file for a specified registry from the IEEE website.
     *
     * @param string $uri URI of CSV file at IEEE
     * @return Row[] List of Vendor rows.
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
        array_shift( array: $rows ); // REMOVE COLUMN TITLES

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



/* CREATE DB TABLE
----------------------------------------------------------------------------- */

    /**
     * @return int|false Confirm table creation.
     */
    public function create_Table() : int | false
    {
        $sql = "CREATE TABLE IF NOT EXISTS vendors(
              registry VARCHAR(8),
            assignment VARCHAR(16),
                  name VARCHAR(255),
               address VARCHAR(255)
        )";

        return $this->db->exec( statement: $sql );
    }
}