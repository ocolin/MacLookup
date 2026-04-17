<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use Ocolin\MacLookup\Exceptions\DataNotInitializedException;
use Ocolin\MacLookup\Exceptions\StorageException;

class MemoryDriver implements LookupInterface, UpdateableInterface
{

    /** @var array<string, Vendor|null> List of large OUI lib*/
    private array $ouiL = [];

    /** @var array<string, Vendor|null> List of medium OUI lib*/
    private array $ouiM = [];

    /** @var array<string, Vendor|null> List of small OUI lib*/
    private array $ouiS = [];

    use JsonlUpdateTrait;


/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    /**
     * @param string $dataPath Path to storage file.
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
        $this->vendorFile = $dataPath . DIRECTORY_SEPARATOR . 'vendors.jsonl';
        $this->downloader = $downloader ?? new Downloader( dataPath: $dataPath );

        if( !file_exists( $this->vendorFile ) OR filesize( $this->vendorFile ) < 1 ) {
            if( $autoUpdate === true ) { $this->update(); }
            else {
                throw new DataNotInitializedException(
                    message: 'Missing Vendor data. Vendor list needs to be updated.'
                );
            }
        }

        $this->load();
    }



/* LOOKUP MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address to query.
     * @return Vendor Vendor of MAC address.
     */
    public function lookup( string $mac ) : Vendor
    {
        if( !Mac::isValid( mac: $mac ))  { return Vendor::invalid( mac: $mac ); }
        $normalized = Mac::normalize( mac: $mac );
        if( Mac::isPrivate( mac: $mac )) {
            return Vendor::privateAddress( mac: $normalized );
        }

        $stripped = Mac::strip( mac: $mac );

        $match =  ( $this->ouiS[ substr( $stripped, offset: 0, length: 9 ) ] ?? null ) // MA-S First
               ?? ( $this->ouiM[ substr( $stripped, offset: 0, length: 7 ) ] ?? null ) // MA-M Second
               ?? ( $this->ouiL[ substr( $stripped, offset: 0, length: 6 ) ] ?? null );// MA-L Last


        return $match === null ? Vendor::notFound( mac: $normalized ) : new Vendor(
                   mac: $normalized,
              registry: $match->registry,
            assignment: $match->assignment,
                  name: $match->name,
               address: $match->address,
        );
    }



/* BULK MAC ADDRESS LOOKUP
----------------------------------------------------------------------------- */

    /**
     * @param string[] $macs List of MAC addresses to query.
     * @return Vendor[] List of found vendors.
     */
    public function bulkLookup( array $macs ) : array
    {
        $results = [];

        // Get invalid and private
        foreach( $macs as $mac ) {
            if( !Mac::isValid( mac: $mac )) {
                $results[ $mac ] = Vendor::invalid( mac: $mac );
                continue;
            }
            $normalized = Mac::normalize( mac: $mac );
            if( Mac::isPrivate( mac: $mac )) {
                $results[ $normalized ]
                    = Vendor::privateAddress( mac: $normalized );
                continue;
            }

            $stripped = Mac::strip( mac: $mac );
            $match =  ( $this->ouiS[ substr( $stripped, offset: 0, length: 9 ) ] ?? null ) // MA-S First
                   ?? ( $this->ouiM[ substr( $stripped, offset: 0, length: 7 ) ] ?? null ) // MA-M Second
                   ?? ( $this->ouiL[ substr( $stripped, offset: 0, length: 6 ) ] ?? null )// MA-L Last
                   ?? null;

            if( $match === null ) {
                $results[ $normalized ] = Vendor::notFound( mac: $normalized );
            } else {
                $results[ $normalized ] = new Vendor(
                           mac: $normalized,
                      registry: $match->registry,
                    assignment: $match->assignment,
                          name: $match->name,
                       address: $match->address,
                );
            }
        }

        return $results;
    }



/* LOAD DATA INTO MEMORY
----------------------------------------------------------------------------- */

    /**
     * @return void
     * @throws StorageException Unable to open file.
     */
    private function load() : void
    {
        $handle = fopen( filename: $this->vendorFile, mode: 'r' );
        if( $handle === false ) {
            throw new StorageException(
                message: "Unable to open file '{$this->vendorFile}'"
            );
        }

        try {
            while(( $line = fgets( stream: $handle )) !== false )
            {
                /** @var Vendor $vendor */
                $vendor = json_decode( $line );
                /** @phpstan-ignore-next-line */
                if( $vendor === null ) { continue; }

                $length = strlen( $vendor->assignment );
                match( $length ) {
                    6 => $this->ouiL[ $vendor->assignment ] = $vendor,
                    7 => $this->ouiM[ $vendor->assignment ] = $vendor,
                    9 => $this->ouiS[ $vendor->assignment ] = $vendor,
                    default => null
                };
            }
        }
        finally { fclose( stream: $handle ); }
    }
}