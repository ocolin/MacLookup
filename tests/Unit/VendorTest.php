<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests\Unit;

use Error;
use Ocolin\MacLookup\Vendor;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function test_Constructor_Values() : void
    {
        $vendor = new Vendor(
                   mac: 'mac',
              registry: 'registry',
            assignment: 'assignment',
                  name: 'name',
               address: 'address',
        );
        $this->assertObjectHasProperty( 'mac', $vendor );
        $this->assertSame( 'mac', $vendor->mac );
        $this->assertObjectHasProperty( 'registry', $vendor );
        $this->assertSame( 'registry', $vendor->registry );
        $this->assertObjectHasProperty( 'assignment', $vendor );
        $this->assertSame( 'assignment', $vendor->assignment );
        $this->assertObjectHasProperty( 'name', $vendor );
        $this->assertSame( 'name', $vendor->name );
        $this->assertObjectHasProperty( 'address', $vendor );
        $this->assertSame( 'address', $vendor->address );
    }


    public function test_Constructor_Readonly() : void
    {
        $this->expectException( Error::class );
        $vendor = new Vendor(
                   mac: 'mac',
              registry: 'registry',
            assignment: 'assignment',
                  name: 'name',
               address: 'address',
        );
        $vendor->mac = 'change';
        $this->assertSame( 'mac', $vendor->mac );
    }

    public function test_FromArray() : void
    {
        $vendor = Vendor::fromArray([
            'registry',
            'assignment',
            'name',
            '',
        ]);
        $this->assertObjectHasProperty( 'mac', $vendor );
        $this->assertSame( '', $vendor->mac );
        $this->assertObjectHasProperty( 'registry', $vendor );
        $this->assertSame( 'registry', $vendor->registry );
        $this->assertObjectHasProperty( 'assignment', $vendor );
        $this->assertSame( 'assignment', $vendor->assignment );
        $this->assertObjectHasProperty( 'name', $vendor );
        $this->assertSame( 'name', $vendor->name );
        $this->assertObjectHasProperty( 'address', $vendor );
        $this->assertSame( '', $vendor->address );
    }

    public function test_FromArray_NotFound() : void
    {
        $vendor = Vendor::notFound( mac: '54:91:AF:B2:02:3A' );
        $this->assertSame( '54:91:AF:B2:02:3A', $vendor->mac );
        $this->assertSame( 'Not Found', $vendor->registry );
        $this->assertSame( '', $vendor->assignment );
        $this->assertSame( '', $vendor->name );
        $this->assertSame( '', $vendor->address );
    }

    public function test_FromArray_Invalid() : void
    {
        $vendor = Vendor::notFound( mac: '54:91:ZZ' );
        $this->assertSame( '54:91:ZZ', $vendor->mac );
        $this->assertSame( 'Not Found', $vendor->registry );
        $this->assertSame( '', $vendor->assignment );
        $this->assertSame( '', $vendor->name );
        $this->assertSame( '', $vendor->address );
    }

    public function test_FromArray_Private() : void
    {
        $vendor = Vendor::privateAddress( mac: '52:91:AF:B2:02:3A' );
        $this->assertSame( '52:91:AF:B2:02:3A', $vendor->mac );
        $this->assertSame( 'Private', $vendor->registry );
        $this->assertSame( '', $vendor->assignment );
        $this->assertSame( '', $vendor->name );
        $this->assertSame( '', $vendor->address );
    }
}