<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests;

use Ocolin\MacLookup\Exceptions\FileDownloadException;
use Ocolin\MacLookup\MacDB;
use PHPUnit\Framework\TestCase;

class MacDbTest extends TestCase
{
    public static MacDB $mac;


    public function testDownloadVendor() : void
    {
        $output = MacDB::download_Vendor_File( uri: MacDB::OUI36 );
        TestCase::assertNotEmpty( $output );
        //print_r( $output );
    }


    /**
     * @throws FileDownloadException
     */
    public function testUpdateVendors() : void
    {
        $output = self::$mac->update_Vendors();
        var_dump( $output );
        TestCase::assertTrue( $output );
    }


    public function testLookupGood() : void
    {
        $output = self::$mac->lookup( mac: '30:23:03:3A:F3:55' );
        //var_dump( $output );
        TestCase::assertEquals( '302303', $output->assignment );
    }

    public function testLookupPrivate() : void
    {
        $output = self::$mac->lookup( mac: '32:23:03:3A:F3:55' );
        //print_r( $output );
        TestCase::assertEquals( 'Private', $output->registry );
    }


    public function testLookupNotFound() : void
    {
        $output = self::$mac->lookup( mac: 'F0:23:03:3A:F3:00' );
        // print_r( $output );
        TestCase::assertEquals( 'Not Found', $output->registry );
    }


    public function testInvalid() : void
    {
        $output = self::$mac->lookup( mac: 'asdfsd' );
        // print_r( $output );
        TestCase::assertEquals( 'Not Found', $output->registry );
    }


    public function testDbSearch() : void
    {
        $output = self::$mac->search_DB( mac: '3023033AF355' );
        //print_r($output);
        TestCase::assertIsObject( $output );
        TestCase::assertEquals( '302303', $output->assignment );
    }


    public static function setUpBeforeClass(): void
    {
        self::$mac = new MacDB();
    }
}