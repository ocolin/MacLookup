<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

use Ocolin\MacLookup\Exceptions\StorageException;


trait JsonlUpdateTrait
{
    /**
     * @var string Location of vendor storage file.
     */
    private string $vendorFile;

    /**
     * @var Downloader Handler for downloader object.
     */
    private Downloader $downloader;

    /**
     * @var int Number of rows to process at a time.
     */
    private int $flushSize = 1000;


/* UPDATE VENDOR DATA
----------------------------------------------------------------------------- */

    /**
     * @return void
     * @throws StorageException Unable to open file or write to stream.
     */
    public function update() : void
    {
        $buffer = '';
        $bufferSize = 0;
        $handle = fopen( filename: $this->vendorFile, mode: 'w' );
        if( $handle === false ) {
            throw new StorageException(
                message: 'Unable to open file: ' . $this->vendorFile
            );
        }

        try {
            foreach( $this->downloader->update() as $vendor )
            {
                $json = json_encode( $vendor );
                if( $json === false ) { continue; }
                $buffer .= $json . "\n";
                $bufferSize++;

                if( $bufferSize >= $this->flushSize ) {
                    $write = fwrite( stream: $handle, data: $buffer );
                    if( $write === false ) {
                        throw new StorageException(
                            message: 'Unable to write to stream: ' . $this->vendorFile
                        );
                    }

                    $bufferSize = 0;
                    $buffer = '';
                }
            }

            if( $buffer !== '' ) {
                $write = fwrite( stream: $handle, data: $buffer );
                if( $write === false ) throw new StorageException(
                    message: 'Unable to write to stream: ' . $this->vendorFile
                );
            }
        }

        finally { fclose( stream: $handle ); }
    }
}