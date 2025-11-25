<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests;

require_once __DIR__ . '/../src/MacLookup.php';

use Ocolin\Maclookup\MacLookup;
use PHPUnit\Framework\TestCase;

final class MacLookupTest extends TestCase
{

    public function testLookup() : void
    {
        $mac = '30:23:03:3A:F3:55';
        $output = MacLookup::lookup( $mac );

        $this->assertIsObject( actual: $output );
        $this->assertObjectHasProperty( propertyName: 'name',object: $output );
        $this->assertObjectHasProperty( propertyName: 'assignment',object: $output );
        $this->assertObjectHasProperty( propertyName: 'address',object: $output );
        $this->assertEquals( expected: "Belkin International Inc.", actual: $output->name );
        $this->assertEquals( expected: '302303', actual: $output->assignment );
    }

    public function testLookupPrivate() : void
    {
        $mac = '32:23:03:3A:F3:55';
        $output = MacLookup::lookup( $mac );

        $this->assertIsObject( actual: $output );
        $this->assertObjectHasProperty( propertyName: 'name',object: $output );
        $this->assertObjectHasProperty( propertyName: 'assignment',object: $output );
        $this->assertObjectHasProperty( propertyName: 'address',object: $output );
        $this->assertEquals( expected: "Private", actual: $output->name );
    }

    public function testLookupNotFound() : void
    {
        $mac = '15:00:00:00:00:00';
        $output = MacLookup::lookup( $mac );
        $this->assertIsObject( actual: $output );
        $this->assertObjectHasProperty( propertyName: 'name',object: $output );
        $this->assertObjectHasProperty( propertyName: 'assignment',object: $output );
        $this->assertObjectHasProperty( propertyName: 'address',object: $output );
        $this->assertEquals( expected: "Not Found", actual: $output->name );
    }



    public function testFormatMac() : void
    {
        $mac = '30:f3:3:3A:f3:01';
        $output = MacLookup::format_MAC( $mac );

        $this->assertIsString( actual: $output );
        $this->assertEquals( expected: '30:F3:03:3A:F3:01', actual: $output );
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

}