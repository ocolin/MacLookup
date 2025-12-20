<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests;

require_once __DIR__ . '/../src/MacLookup.php';

use Ocolin\MacLookup\MacLookup;
use PHPUnit\Framework\TestCase;

final class MacLookupTest extends TestCase
{

    public function testLookup() : void
    {
        $output = MacLookup::lookup( mac: '30:23:03:3A:F3:55' );

        $this->assertObjectHasProperty(  'name', $output );
        $this->assertObjectHasProperty( 'assignment', $output );
        $this->assertObjectHasProperty( 'address', $output );
        $this->assertEquals( "Belkin International Inc.", $output->name );
        $this->assertEquals( '302303', $output->assignment );
    }

    public function testLookupPrivate() : void
    {
        $output = MacLookup::lookup( mac: '32:23:03:3A:F3:55' );
        $this->assertObjectHasProperty( 'name', $output );
        $this->assertObjectHasProperty( 'assignment', $output );
        $this->assertObjectHasProperty( 'address', $output );
        $this->assertEquals( "Private", $output->name );
    }

    public function testLookupNotFound() : void
    {
        $output = MacLookup::lookup( mac: '15:00:00:00:00:00' );
        $this->assertObjectHasProperty( 'name', $output );
        $this->assertObjectHasProperty( 'assignment', $output );
        $this->assertObjectHasProperty( 'address', $output );
        $this->assertEquals( "Not Found", $output->name );
    }



    public function testFormatMac() : void
    {
        $output = MacLookup::format_MAC( mac: '30:f3:3:3A:f3:01' );
        $this->assertEquals( '30:F3:03:3A:F3:01', $output );
    }

    public function testFormatPairsGood() : void
    {
        $output = MacLookup::format_Pairs( mac: '30:23:03:3A:F3' );
        $this->assertEquals( '30:23:03:3A:F3', $output );
    }

    public function testFormatPairsBad() : void
    {
        $output = MacLookup::format_Pairs( mac: '30:23:3:3A:F3' );
        $this->assertEquals( '30:23:03:3A:F3', $output );
    }

    public function testIsPrivateGood() : void
    {
        $output = MacLookup::is_private( mac: '3A:23:3:3A:F3' );
        $this->assertTrue( $output );
    }

    public function testIsPrivateBad() : void
    {
        $output = MacLookup::is_private( mac: '30:23:3:3A:F3' );
        $this->assertFalse( $output );
    }


    public function testUpdate() : void
    {
        $output = MacLookup::update();
        //var_dump( $output );
        self::assertTrue( $output );
    }

}