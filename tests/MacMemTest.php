<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests;

use Ocolin\MacLookup\Exceptions\FileDownloadException;
use Ocolin\MacLookup\Exceptions\FileLoadException;
use Ocolin\MacLookup\Exceptions\JsonParseException;
use Ocolin\MacLookup\MacMem;
use PHPUnit\Framework\TestCase;

class MacMemTest extends TestCase
{
    public static MacMem $mac;

    /**
     * @throws FileDownloadException
     */
    public function testDownloadVendor() : void
    {
        $output = MacMem::download_Vendor_File( uri: MacMem::OUI36 );
        TestCase::assertNotEmpty( $output );
        //print_r( $output );
    }


    /**
     * @throws FileDownloadException
     */
    public function testDownloadVendors() : void
    {
        $output = MacMem::download_Vendors();
        //print_r( $output );
        TestCase::assertNotEmpty( $output );
    }


    /**
     * @throws FileDownloadException
     * @throws JsonParseException
     */
    public function testUpdateVendors() : void
    {
        $output = MacMem::update_Vendors();
        TestCase::assertTrue( $output );
    }


    /**
     * @throws FileLoadException
     * @throws JsonParseException
     */
    public function testLoadVendors() : void
    {
        $output = MacMem::load_Vendors();
        //print_r( $output );
        TestCase::assertNotEmpty( $output );
    }


    public function testLookupGood() : void
    {
        $output = self::$mac->lookup( mac: '30:23:03:3A:F3:55' );
        //print_r( $output );
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


    public static function setUpBeforeClass(): void
    {
        self::$mac = new MacMem();
    }

}
