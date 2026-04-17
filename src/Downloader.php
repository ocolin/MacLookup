<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use Generator;
use Ocolin\MacLookup\Exceptions\StorageException;
use Ocolin\MacLookup\Exceptions\DownloadException;

class Downloader
{
    /**
     * IEEE Base URL that contains libraries.
     */
    private const IEEE_BASE  = 'https://standards-oui.ieee.org/';

    /**
     * IEEE Main vendor library.
     */
    private const OUI = 'oui/oui.csv';

    /**
     * IEEE Medium vendor library.
     */
    private const OUI28 = 'oui28/mam.csv';

    /**
     * IEEE Small vendor library.
     */
    private const OUI36 = 'oui36/oui36.csv';

    /**
     * Name of lock file.
     */
    private const LOCK_FILE = 'maclookup.lock';

    /**
     * List of OUI registries for looping.
     */
    private const REGISTRIES = [ self::OUI36, self::OUI28, self::OUI ];


    /**
     * @var string Name of lock file with path.
     */
    private string $lockFile;


/* CONSTRUCTOR
----------------------------------------------------------------------------- */

    /**
     * @param string $dataPath Path to store file data.
     * @throws StorageException Failed to create storage dir or already loading.
     */
    public function __construct( string $dataPath )
    {
        $this->lockFile = $dataPath . DIRECTORY_SEPARATOR . self::LOCK_FILE;

        // Setup directory
        if( !is_dir( $dataPath )) {
            if( !mkdir(
                  directory: $dataPath,
                permissions: 0755,
                  recursive: true )
            ) {
                throw new StorageException(
                    message: 'Failed to create directory "' . $dataPath . '"'
                );
            }
        }

        // Setup lock file. Checking in case there was a crash leaving lock behind.
        if( file_exists( $this->lockFile )) {
            if( filemtime( $this->lockFile ) < time() - 600 ) {
                unlink( $this->lockFile );
            } else {
                throw new StorageException(
                    message: 'Update already in progress'
                );
            }
        }
    }



/* UPDATE IEEE DATA
----------------------------------------------------------------------------- */

    /**
     * @return Generator<int, Vendor> Vendor from IEEE site.
     */
    public function update(): Generator
    {
        $this->acquireLock();
        try { yield from $this->fetch(); }
        finally { $this->releaseLock(); }
    }



/* FETCH DATA FROM IEEE
----------------------------------------------------------------------------- */

    /**
     * @return Generator<int, Vendor> Vendor from IEEE site.
     * @throws DownloadException missing setting in php.ini or failed download.
     */
    private function fetch(): Generator
    {
        if( !ini_get( option: 'allow_url_fopen' )) {
            throw new DownloadException(
                message: 'allow_url_fopen must be enabled in php.ini'
            );
        }

        foreach( self::REGISTRIES as $registry ) {
            $handle = fopen( filename: self::IEEE_BASE . $registry, mode: 'r' );
            if( $handle === false ) {
                throw new DownloadException(
                    message: "Failed to download: {$registry}"
                );
            }

            $check = fgetcsv( stream: $handle, escape: '' );
            if( $check === false ) {
                throw new DownloadException(
                    message: "Failed to download row: {$registry}"
                );
            }

            try {
                while (($row = fgetcsv(stream: $handle, escape: '')) !== false) {
                    if ($row[0] === null) {
                        continue;
                    }
                    yield Vendor::fromArray(data: $row);
                }
            }
            finally { fclose( stream: $handle ); }
        }
    }



/* ACQUIRE FILE LOCK
----------------------------------------------------------------------------- */

    /**
     * @return void
     * @throws StorageException Could not set lock file.
     */
    private function acquireLock(): void
    {
        $handle = fopen( filename: $this->lockFile, mode: 'x' );
        if( $handle === false ) {
            throw new StorageException( message: 'Could not acquire lock' );
        }
        fclose( stream: $handle );
    }



/* RELEASE FILE LOCK
----------------------------------------------------------------------------- */

    /**
     * @return void
     */
    private function releaseLock(): void
    {
        if( file_exists( filename: $this->lockFile )) {
            unlink( filename: $this->lockFile );
        }
    }
}