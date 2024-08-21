<?php

declare( strict_types = 1 );

namespace Ocolin\Tests;

require_once __DIR__ . '/../src/MacLookup.php';

use Ocolin\MacLookup\MacLookup;
use PHPUnit\Framework\TestCase;

final class MacLookupTest extends TestCase
{
    public function testLookup() : void
    {
        $mac = '30:23:03:3A:F3:55';
        $output = MacLookup::lookup( $mac );

        $this->assertIsObject( actual: $output );
        $this->assertObjectHasProperty( propertyName: 'organization',object: $output );
        $this->assertObjectHasProperty( propertyName: 'mac',object: $output );
        $this->assertObjectHasProperty( propertyName: 'company_id',object: $output );
        $this->assertObjectHasProperty( propertyName: 'address',object: $output );
        $this->assertEquals( expected: "Belkin International Inc.", actual: $output->organization );
        $this->assertEquals( expected: '30:23:03', actual: $output->mac );
        $this->assertEquals( expected: '302303', actual: $output->company_id );
    }

    public function testValidateMacGood() : void
    {
        $mac = '30:23:03:3A:F3:55';
        $output = MacLookup::validate_MAC( mac: $mac );

        $this->assertIsBool( actual: $output );
        $this->assertTrue( condition: $output );
    }

    public function testValidateMacBad() : void
    {
        $mac = '30:23:03:3A:F3';
        $output = MacLookup::validate_MAC( mac: $mac );

        $this->assertIsBool( actual: $output );
        $this->assertFalse( condition: $output );
    }

    public function testFormatMac() : void
    {
        $mac = '30:f3:3:3A:f3:01';
        $output = MacLookup::format_MAC( $mac );

        $this->assertIsString( actual: $output );
        $this->assertEquals( expected: '30:F3:03', actual: $output );
    }

    public function testFormatPairsGood() : void
    {
        $mac = '30:23:03:3A:F3';
        $output = MacLookup::format_Pairs( $mac );

        $this->assertIsString( actual: $output );
        $this->assertEquals( expected: '30:23:03:3A:F3', actual: $output );
    }

    public function testFormatPairsBad() : void
    {
        $mac = '30:23:3:3A:F3';
        $output = MacLookup::format_Pairs( $mac );

        $this->assertIsString( actual: $output );
        $this->assertEquals( expected: '30:23:03:3A:F3', actual: $output );
    }

    public function testIsPrivateGood() : void
    {
        $mac = '3A:23:3:3A:F3';
        $output = MacLookup::is_private( $mac );

        $this->assertIsBool( actual: $output );
        $this->assertTrue( condition: $output );
    }

    public function testIsPrivateBad() : void
    {
        $mac = '30:23:3:3A:F3';
        $output = MacLookup::is_private( $mac );

        $this->assertIsBool( actual: $output );
        $this->assertFalse( condition: $output );
    }

    public function testParseRawVendorList() : void
    {
        $raw = file_get_contents( filename: __DIR__ . '/vendorsRaw.txt' );
        $output = MacLookup::parse_raw_vendor_list( raw: $raw );

        $this->assertIsArray( actual: $output );
        $this->assertIsObject( actual: $output[0] );
    }

    public function testSplitRawVendorList() : void
    {
        $raw = file_get_contents( filename: __DIR__ . '/vendorsRaw.txt' );
        $output = MacLookup::split_Raw_Vendor_List( raw: $raw );

        $this->assertIsArray( actual: $output );
        $this->assertIsString( actual: $output[0] );
    }


    public function testParseRawVendor() : void
    {
        $raw = file_get_contents( filename: __DIR__ . '/vendorsRaw.txt' );
        $vendors = MacLookup::split_Raw_Vendor_List( raw: $raw );
        $output = MacLookup::parse_Raw_Vendor( raw: $vendors[1] );

        $this->assertIsObject( actual: $output );
        $this->assertObjectHasProperty( propertyName: 'organization',object: $output );
        $this->assertObjectHasProperty( propertyName: 'mac',object: $output );
        $this->assertObjectHasProperty( propertyName: 'company_id',object: $output );
        $this->assertObjectHasProperty( propertyName: 'address',object: $output );

    }
}