<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use Exception;
use Ocolin\MacLookup\Exceptions\DataNotInitializedException;
use Ocolin\MacLookup\Exceptions\StorageException;
use PDO;
use PDOStatement;

class DatabaseDriver implements LookupInterface, UpdateableInterface
{
    /**
     * @var string Directory to store vendor data in.
     */
    private string $vendorFile;

    /**
     * @var Downloader Class to download data from IEEE.
     */
    private Downloader $downloader;

    /**
     * @var PDO Database handler.
     */
    private PDO $db;


    /**
     * @var PDOStatement Database query statement.
     */
    private PDOStatement $query;

    // SQLite has a 999 parameter limit. With 4 params per row,
    // max batch size is 249. 200 is used as a safe round number.
    private const BATCH_SIZE = 200;


/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    /**
     * @param string $dataPath Path to data storage folder.
     * @param bool $autoUpdate Automatically update storage if missing.
     * @param ?Downloader $downloader Downloader DI for mocking.
     * @throws DataNotInitializedException Missing vendor data.
     */
    public function __construct(
             string $dataPath,
               bool $autoUpdate = true,
        ?Downloader $downloader = null
    )
    {
        $this->vendorFile = $dataPath . DIRECTORY_SEPARATOR . 'vendors.db';
        $this->downloader = $downloader ?? new Downloader( dataPath: $dataPath );
        $this->db = new PDO( dsn: 'sqlite:' . $this->vendorFile );
        if( !$this->hasData()) {
            if( $autoUpdate ) { $this->update(); }
            else {
                throw new DataNotInitializedException(
                    message: 'Database needs to be updated.'
                );
            }
        }

        /** @var PDOStatement|false $query */
        $query = $this->db->prepare( query: "
            SELECT registry, assignment, name, address
              FROM vendors  
             WHERE assignment = ? 
             LIMIT 1
        ");
        if( $query === false ) {
            throw new StorageException(
                message: 'Unable to prepare lookup statement.'
            );
        }
        $this->query = $query;
    }



/* LOOK UP MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * Look up a single MAC address.
     *
     * @param string $mac MAC address to look for.
     * @return Vendor Vendor of MAC address.
     */
    public function lookup( string $mac ) : Vendor
    {
        $normalized = Mac::normalize( mac: $mac );

        if( !Mac::isValid( $mac )) { return Vendor::invalid( mac: $mac ); }
        if( Mac::isPrivate( mac:$mac )) {
            return Vendor::privateAddress( mac: $normalized );
        }

        $stripped = Mac::strip( mac: $mac );

        $match = $this->queryAssignment( assignment: substr( $stripped, 0, 9 )) // MA-S first
            ?? $this->queryAssignment( assignment: substr( $stripped, 0, 7 ))   // MA-M second
            ?? $this->queryAssignment( assignment: substr( $stripped, 0, 6 ));  // MA-L last

        if( $match === null ) { return Vendor::notFound( mac: $normalized ); }

        /** @var array<string, string> $match */
        return new Vendor(
                   mac: $normalized,
              registry: $match['registry'],
            assignment: $match['assignment'],
                  name: $match['name'],
               address: $match['address']
        );
    }



/* BULK LOOKUP OF MAC ADDRESSES
----------------------------------------------------------------------------- */

    /**
     * Lookup multiple MAC addresses at once.
     *
     * @param string[] $macs List of MAC addresses to search for.
     * @return Vendor[] List of vendors.
     */
    public function bulkLookup( array $macs ): array
    {
        $results = [];

        foreach( $macs as $mac ) {
            $normalized = Mac::normalize( mac: $mac );

            if( !Mac::isValid( mac: $mac )) {
                $results[ $mac ] = Vendor::invalid( mac: $mac );
                continue;
            }
            if( Mac::isPrivate( mac: $mac )) {
                $results[ $normalized ] = Vendor::privateAddress( mac: $normalized );
                continue;
            }

            $stripped = Mac::strip( mac: $mac );

            $match = $this->queryAssignment( assignment: substr( $stripped, 0, 9 )) // MA-S first
                ?? $this->queryAssignment( assignment: substr( $stripped, 0, 7 ))   // MA-M second
                ?? $this->queryAssignment( assignment: substr( $stripped, 0, 6 ));  // MA-L last

            if( $match === null ) {
                $results[ $normalized ]  = Vendor::notFound( mac: $normalized );
                continue;
            }

            /** @var array<string, string> $match */
            $results[ $normalized ] = new Vendor(
                       mac: $normalized,
                  registry: $match['registry'],
                assignment: $match['assignment'],
                      name: $match['name'],
                   address: $match['address']
            );
        }

        return $results;
    }



/* QUERY ASSIGNMENT
----------------------------------------------------------------------------- */

    /**
     * Because assignments are different lengths for different registries, this
     * searches a registry based on the assignment length. This prevents returning
     * wrong results from larger registries.
     *
     * @param string $assignment
     * @return array<mixed>|null
     */
    private function queryAssignment( string $assignment ) : ?array
    {
        $this->query->execute([ $assignment ]);
        $response = $this->query->fetch( mode: PDO::FETCH_ASSOC );

        return is_array( $response ) ? $response : null;
    }



/* UPDATE VENDOR LIST
----------------------------------------------------------------------------- */

    /**
     * Update vendor list with latest data from IEEE.
     *
     * @return void
     * @throws StorageException Unable to update Database.
     */
    public function update() : void
    {
        $this->createTable();
        $this->db->beginTransaction();

        try {
            $batch = [];
            foreach( $this->downloader->update() as $vendor ) {
                $batch[] = [
                    $vendor->registry,
                    $vendor->assignment,
                    $vendor->name,
                    $vendor->address
                ];
                if( count( $batch ) >= self::BATCH_SIZE ) {
                    $this->insertBatch( batch: $batch );
                    $batch = [];
                }
            }

            if( !empty( $batch ) ) {
                $this->insertBatch( batch: $batch );
            }
            $this->db->commit();
        }
        catch( Exception $e ) {
            $this->db->rollBack();
            throw new StorageException(
                message: 'Unable to update vendor database.'
            );
        }
    }



/* INSERT VENDORS INTO DATABASE
----------------------------------------------------------------------------- */

    /**
     * Insert IEEE MAC vendor data into datanase.
     *
     * @param array<int, array<int, string>> $batch
     * @return void
     * @throws StorageException Unable to prepare or execute insert command.
     */
    private function insertBatch( array $batch ) : void
    {
        $placeholders = str_repeat(
            string: '(?, ?, ?, ?), ',
            times: count( $batch ) - 1
        ) . '(?, ?, ?, ?)';
        $sql = "INSERT INTO vendors( registry, assignment, name, address ) 
                VALUES {$placeholders}
        ";
        $query = $this->db->prepare( $sql );
        if( $query === false ) {
            throw new StorageException( message: 'Unable to prepare INSERT statement.' );
        }
        if( $query->execute( array_merge( ...$batch )) === false ) {
            throw new StorageException( message: 'Unable to execute INSERT statement.' );
        }
    }



/* CREATE NEW DATABASE TABLE
----------------------------------------------------------------------------- */

    /**
     * Delete ay existing database and create a new one.
     *
     * @return void
     * @throws StorageException Unable to drop table or create table.
     */
    private function createTable() : void
    {
        if(
            $this->db->exec( statement: "DROP TABLE IF EXISTS vendors" ) === false
        ) {
            throw new StorageException(
                message: "Cannot drop table '{$this->vendorFile}'"
            );
        }

        if( $this->db->exec( statement:
            "DROP TABLE IF EXISTS vendors;
             CREATE TABLE vendors(
              registry VARCHAR(8),
            assignment VARCHAR(16),
                  name VARCHAR(255),
               address VARCHAR(255))
        ") === false ) {
            throw new StorageException(
                message: "Cannot create table '{$this->vendorFile}'"
            );
        }

        if( $this->db->exec( statement:
            "CREATE INDEX idx_assignment ON vendors( assignment )"
        ) === false ) {
            throw new StorageException(
                message: "Cannot create index for '{$this->vendorFile}'"
            );
        }
    }



/* DOES DATABASE HAVE DATA?
----------------------------------------------------------------------------- */

    /**
     * Check to see if database has content in it.
     *
     * @return bool If Database has data in it.
     */
    private function hasData(): bool
    {
        $result = $this->db->query(
            query: "SELECT COUNT(*) FROM sqlite_master 
         WHERE type='table' AND name='vendors'"
        );
        if( $result === false || $result->fetchColumn() === 0 ) {
            return false;
        }
        $count = $this->db->query( query: "SELECT COUNT(*) FROM vendors" );
        return $count !== false && $count->fetchColumn() > 0;
    }
}