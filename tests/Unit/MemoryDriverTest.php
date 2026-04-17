<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests\Unit;

use Ocolin\MacLookup\Downloader;
use Ocolin\MacLookup\Exceptions\DataNotInitializedException;
use Ocolin\MacLookup\MemoryDriver;
use Ocolin\MacLookup\Vendor;
use PHPUnit\Framework\TestCase;
use Generator;

final class MemoryDriverTest extends TestCase
{
    public static string $temp_dir;

    public static array $vendors;


/*
----------------------------------------------------------------------------- */

    public function test_construct_no_file_auto_true(): void
    {
        $mock = $this->createMock(Downloader::class );

        $mock->expects( $this->once())
            ->method('update' )
            ->willReturn( $this->vendorGenerator( self::$vendors ));

        new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $mock,
        );
        $this->assertFileExists( self::$temp_dir . DIRECTORY_SEPARATOR . 'vendors.jsonl' );
    }

    public function test_construct_no_file_auto_false(): void
    {
        $stub = $this->createStub( Downloader::class );
        $this->expectException( DataNotInitializedException::class );

        new MemoryDriver(
              dataPath: self::$temp_dir . DIRECTORY_SEPARATOR .  'nonexistent_path',
            autoUpdate: false,
            downloader: $stub,
        );
    }

    public function test_construct_file_exists(): void
    {
        $mock = $this->createMock(Downloader::class );
        file_put_contents(
            filename: self::$temp_dir . DIRECTORY_SEPARATOR . 'vendors.jsonl',
            data: json_encode( new Vendor(
                mac: '', registry: 'MA-L', assignment: '5491AF', name: 'Belkin', address: ''
            )) . "\n"
        );

        $mock->expects( $this->never())->method( 'update' );

        new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $mock,
        );
    }


/*
----------------------------------------------------------------------------- */

    public function test_lookup_invalid() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update')->willReturn( $this->vendorGenerator([]));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );
        $result = $lookup->lookup( mac: '54:91:AF:B2:02:ZZ' );
        $this->assertSame( 'Invalid', $result->registry );
        $this->assertSame( '54:91:AF:B2:02:ZZ', $result->mac );
        $this->assertSame( '', $result->assignment );
    }

    public function test_lookup_private() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator([]));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );
        $result = $lookup->lookup( mac: '52:91:aF:B2:02:00' );
        $this->assertSame( 'Private', $result->registry );
        $this->assertSame( '52:91:AF:B2:02:00', $result->mac );
        $this->assertSame( '', $result->assignment );
    }

    public function test_lookup_large_vendor() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->lookup( mac: '54:91:aF:B2:02:00' );
        $this->assertSame( 'MA-L', $result->registry );
        $this->assertSame( '54:91:AF:B2:02:00', $result->mac );
        $this->assertSame( '5491AF', $result->assignment );
        $this->assertSame( 'Belkin', $result->name );
    }

    public function test_lookup_medium_vendor() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->lookup( mac: '00:17:F2:A2:02:00' );
        $this->assertSame( 'MA-M', $result->registry );
        $this->assertSame( '00:17:F2:A2:02:00', $result->mac );
        $this->assertSame( '0017F2A', $result->assignment );
        $this->assertSame( 'Apple', $result->name );
    }

    public function test_lookup_small_vendor() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->lookup( mac: '70:B3:D5:E5:F2:00' );
        $this->assertSame( 'MA-S', $result->registry );
        $this->assertSame( '70:B3:D5:E5:F2:00', $result->mac );
        $this->assertSame( '70B3D5E5F', $result->assignment );
        $this->assertSame( 'Test Vendor', $result->name );
    }

    public function test_lookup_not_found() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->lookup( mac: '00:91:aF:B2:02:00' );
        $this->assertSame( 'Not Found', $result->registry );
        $this->assertSame( '00:91:AF:B2:02:00', $result->mac );
        $this->assertSame( '', $result->assignment );
        $this->assertSame( '', $result->name );
    }

    public function test_normalization() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->lookup( mac: '54-91-aF-B2-2:00' );
        $this->assertSame( 'MA-L', $result->registry );
        $this->assertSame( '54:91:AF:B2:02:00', $result->mac );
        $this->assertSame( '5491AF', $result->assignment );
        $this->assertSame( 'Belkin', $result->name );
    }

/*
----------------------------------------------------------------------------- */

    public function test_bulk_mixture() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
            dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->bulkLookup( macs: [
            '54:91:aF:B2:02:00', // FOUND
            '00:91:aF:B2:02:00', // NOT FOUND
            'invalid',           // INVALID
            '52-91-aF-B2-2-00'  // PRIVATE
        ]);
        $this->assertCount( 4, $result );
        $this->assertArrayHasKey( '54:91:AF:B2:02:00', $result );
        $this->assertArrayHasKey( '00:91:AF:B2:02:00', $result );
        $this->assertArrayHasKey( 'invalid', $result );
        $this->assertArrayHasKey( '52:91:AF:B2:02:00', $result );

        // FOUND
        $this->assertSame( '54:91:AF:B2:02:00', $result['54:91:AF:B2:02:00']->mac );
        $this->assertSame( 'MA-L', $result['54:91:AF:B2:02:00']->registry );
        $this->assertSame( 'Belkin', $result['54:91:AF:B2:02:00']->name );
        $this->assertSame( '5491AF', $result['54:91:AF:B2:02:00']->assignment );

        // NOT FOUND
        $this->assertSame( '00:91:AF:B2:02:00', $result['00:91:AF:B2:02:00']->mac );
        $this->assertSame( 'Not Found', $result['00:91:AF:B2:02:00']->registry );
        $this->assertSame( '', $result['00:91:AF:B2:02:00']->assignment );
        $this->assertSame( '', $result['00:91:AF:B2:02:00']->name );

        // PRIVATE
        $this->assertSame( '52:91:AF:B2:02:00', $result['52:91:AF:B2:02:00']->mac );
        $this->assertSame( 'Private', $result['52:91:AF:B2:02:00']->registry );
        $this->assertSame( '', $result['52:91:AF:B2:02:00']->assignment );
        $this->assertSame( '', $result['52:91:AF:B2:02:00']->name );

        // INVALID
        $this->assertSame( 'invalid', $result['invalid']->mac );
        $this->assertSame( 'Invalid', $result['invalid']->registry );
        $this->assertSame( '', $result['invalid']->assignment );
        $this->assertSame( '', $result['invalid']->name );
    }

/*
----------------------------------------------------------------------------- */

    public function test_bulk_empty() : void
    {
        $stub = $this->createStub( Downloader::class );
        $stub->method('update' )->willReturn( $this->vendorGenerator( self::$vendors ));
        $lookup = new MemoryDriver(
              dataPath: self::$temp_dir,
            autoUpdate: true,
            downloader: $stub,
        );

        $result = $lookup->bulkLookup( macs: []);
        $this->assertEmpty( $result );
    }


/*
----------------------------------------------------------------------------- */

    public static function setUpBeforeClass(): void
    {
        self::$temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maclookup_test';
        mkdir( directory: self::$temp_dir, permissions: 0755, recursive: true );
        self::$vendors = [
            new Vendor( mac: '', registry: 'MA-L', assignment: '5491AF', name: 'Belkin', address: '...' ),
            new Vendor( mac: '', registry: 'MA-M', assignment: '0017F2A', name: 'Apple', address: '...' ),
            new Vendor( mac: '', registry: 'MA-S', assignment: '70B3D5E5F', name: 'Test Vendor', address: '...' )
        ];
    }

    public static function tearDownAfterClass(): void
    {
        if( is_dir( self::$temp_dir )) {
            $files = glob( pattern: self::$temp_dir . DIRECTORY_SEPARATOR . '*' );
            if( $files !== false ) {
                foreach( $files as $file ) { unlink( $file ); }
            }
            rmdir( self::$temp_dir );
        }
    }

    public function tearDown(): void
    {
        $file = self::$temp_dir . DIRECTORY_SEPARATOR . 'vendors.jsonl';
        if( file_exists( $file )) { unlink( $file ); }
    }



    /**
     * @param array $vendors
     * @return Generator<int, Vendor> Vendor from IEEE site.
     */
    private function vendorGenerator( array $vendors ): Generator
    {
        yield from $vendors;
    }

}