<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use Ocolin\MacLookup\Exceptions\DataNotInitializedException;
use Ocolin\MacLookup\Exceptions\StorageException;
use stdClass;

class FileDriver implements LookupInterface, UpdateableInterface
{

    use JsonlUpdateTrait;


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
    }



/* LOOK UP MAC ADDRESS
----------------------------------------------------------------------------- */

    /**
     * @param string $mac MAC address to lookup.
     * @return Vendor Vendor object.
     * @throws StorageException Unable to open file.
     */
    public function lookup( string $mac ) : Vendor
    {
        $normalized = Mac::normalize( mac: $mac );

        // Get invalid and private
        if( !Mac::isValid( mac: $mac )) {
            return Vendor::invalid( mac: $mac );
        }
        if( Mac::isPrivate( mac: $mac )) {
            return Vendor::privateAddress( mac: $normalized );
        }

        $stripped = Mac::strip( mac: $mac );

        // Read from file.
        $handle = fopen( filename: $this->vendorFile, mode: 'r' );
        if( $handle === false ) {
            throw new StorageException(
                message: "Unable to open file '{$this->vendorFile}'"
            );
        }

        try {
            while(( $line = fgets( stream: $handle )) !== false )
            {
                /** @var stdClass $vendor */
                $vendor = json_decode( $line );
                /** @phpstan-ignore-next-line */
                if( $vendor === null ) { continue; }

                if( str_starts_with(
                    haystack: $stripped,
                      needle: $vendor->assignment
                )) {
                    return new Vendor(
                               mac: $normalized,
                          registry: $vendor->registry,
                        assignment: $vendor->assignment,
                              name: $vendor->name,
                           address: $vendor->address,
                    );
                }
            }
        }
        finally { fclose( $handle ); }

        // Not found MAC.
        return Vendor::notFound( mac: $normalized );
    }



/* LOOK UP BULK MAC ADDRESSES
----------------------------------------------------------------------------- */

    /**
     * @param string[] $macs List of MAC addresses to query.
     * @return Vendor[] List of Vendors found.
     * @throws StorageException Unable to open file.
     */
    public function bulkLookup( array $macs ) : array
    {
        $results = [];
        $remaining = [];

        // Get invalid and private
        foreach( $macs as $mac ) {
            if( !Mac::isValid( mac: $mac )) {
                $results[ $mac ] = Vendor::invalid( mac: $mac );
                continue;
            }

            $normalized = Mac::normalize( mac: $mac );
            if( Mac::isPrivate( mac: $mac )) {
                $results[ $normalized ] = Vendor::privateAddress( mac: $normalized );
                continue;
            }
            $remaining[ Mac::strip( mac: $mac ) ] = $normalized;
        }

        // Read from file.
        $handle = fopen( filename: $this->vendorFile, mode: 'r' );
        if( $handle === false ) {
            throw new StorageException(
                message: "Unable to open file '{$this->vendorFile}'"
            );
        }
        try {
             while(( $line = fgets( stream: $handle )) !== false )
             {
                 /** @var stdClass $vendor */
                 $vendor = json_decode( $line );
                 /** @phpstan-ignore-next-line */
                 if( $vendor === null ) { continue; }
                 foreach( $remaining as $stripped => $normalized )
                 {
                     if( str_starts_with( haystack: $stripped, needle: $vendor->assignment )) {
                         $results[ $normalized ] = new Vendor(
                                    mac: $normalized,
                               registry: $vendor->registry,
                             assignment: $vendor->assignment,
                                   name: $vendor->name,
                                address: $vendor->address,
                         );
                         unset( $remaining[ $stripped ] );
                     }
                 }

                 if( empty( $remaining )) { break; }
             }
        }
        finally { fclose( $handle ); }

        // Check not found MACs.
        foreach( $remaining as $stripped => $normalized ) {
            $results[ $normalized ] = Vendor::notFound( mac: $normalized );
        }

        return $results;
    }
}