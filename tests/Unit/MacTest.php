<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests\Unit;

use Ocolin\MacLookup\Mac;
use PHPUnit\Framework\TestCase;

class MacTest extends TestCase
{


/*
----------------------------------------------------------------------------- */

    public function test_isValid_colon_uppercase() : void
    {
        $output = Mac::isValid( mac: '54:91:AF:B2:02:3A' );
        $this->assertTrue( $output );
    }

    public function test_isValid_colon_lowercase() : void
    {
        $output = Mac::isValid( mac: '54:91:af:b2:02:3a' );
        $this->assertTrue( $output );
    }

    public function test_isValid_Dashes() : void
    {
        $output = Mac::isValid( mac: '54-91-AF-B2-02-3A' );
        $this->assertTrue( $output );
    }

    public function test_isValid_Dots() : void
    {
        $output = Mac::isValid( mac: '5491.AFB2.023A' );
        $this->assertTrue( $output );
    }

    public function test_isValid_Hex_Upper() : void
    {
        $output = Mac::isValid( mac: '5491AFB2023A' );
        $this->assertTrue( $output );
    }

    public function test_isValid_Hex_Lower() : void
    {
        $output = Mac::isValid( mac: '5491afb2023a' );
        $this->assertTrue( $output );
    }

    public function test_isValid_No_Leading_Zeros() : void
    {
        $output = Mac::isValid( mac: '54:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isValid_Empty_String() : void
    {
        $output = Mac::isValid( mac: '' );
        $this->assertFalse( $output );
    }

    public function test_isValid_Short() : void
    {
        $output = Mac::isValid( mac: '54:91:AF' );
        $this->assertFalse( $output );
    }

    public function test_isValid_Long() : void
    {
        $output = Mac::isValid( mac: '54:91:AF:B2:02:3A:FF' );
        $this->assertFalse( $output );
    }

    public function test_isValid_Invalid_Chars() : void
    {
        $output = Mac::isValid( mac: '54:91:AF:B2:02:ZZ' );
        $this->assertFalse( $output );
    }

    public function test_isValid_Random() : void
    {
        $output = Mac::isValid( mac: 'this-is-not_a-mac' );
        $this->assertFalse( $output );
    }

    public function test_isValid_Almost_valid() : void
    {
        $output = Mac::isValid( mac: '54:91:AF:B2:02:3G' );
        $this->assertFalse( $output );
    }

/*
----------------------------------------------------------------------------- */

    public function test_isPrivate_Two() : void
    {
        $output = Mac::isPrivate( mac: '52:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_Six() : void
    {
        $output = Mac::isPrivate( mac: '56:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_A() : void
    {
        $output = Mac::isPrivate( mac: '5A:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_E() : void
    {
        $output = Mac::isPrivate( mac: '5E:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_3() : void
    {
        $output = Mac::isPrivate( mac: '53:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_7() : void
    {
        $output = Mac::isPrivate( mac: '57:91:AF:B2:2:3A' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_Lower() : void
    {
        $output = Mac::isPrivate( mac: '0a:91:af:b2:02:3a' );
        $this->assertTrue( $output );
    }

    public function test_isPrivate_Dashes() : void
    {
        $output = Mac::isPrivate( mac: '0A-91-AF-B2-02-3A' );
        $this->assertTrue( $output );
    }


    public function test_isPrivate_Public() : void
    {
        $output = Mac::isPrivate( mac: '54:91:AF:B2:02:3A' );
        $this->assertFalse( $output );
    }

    public function test_isPrivate_0() : void
    {
        $output = Mac::isPrivate( mac: '00:91:AF:B2:02:3A' );
        $this->assertFalse( $output );
    }

    public function test_isPrivate_4() : void
    {
        $output = Mac::isPrivate( mac: '04:91:AF:B2:02:3A' );
        $this->assertFalse( $output );
    }

    public function test_isPrivate_8() : void
    {
        $output = Mac::isPrivate( mac: '08:91:AF:B2:02:3A' );
        $this->assertFalse( $output );
    }

    public function test_isPrivate_C() : void
    {
        $output = Mac::isPrivate( mac: '0C:91:AF:B2:02:3A' );
        $this->assertFalse( $output );
    }

    public function test_isPrivate_Empty() : void
    {
        $output = Mac::isPrivate( mac: '' );
        $this->assertFalse( $output );
    }

/*
----------------------------------------------------------------------------- */

    public function test_Normalize_Colon_Lower() : void
    {
        $output = Mac::normalize( mac: '54:91:af:b2:02:3a' );
        $this->assertSame( '54:91:AF:B2:02:3A', $output );
    }

    public function test_Normalize_Dashes() : void
    {
        $output = Mac::normalize( mac: '54-91-AF-B2-02-3A' );
        $this->assertSame( '54:91:AF:B2:02:3A', $output );
    }

    public function test_Normalize_Dots() : void
    {
        $output = Mac::normalize( mac: '5491.AFB2.023A' );
        $this->assertSame( '54:91:AF:B2:02:3A', $output );
    }

    public function test_Normalize_Hex_Lower() : void
    {
        $output = Mac::normalize( mac: '5491afb2023a' );
        $this->assertSame( '54:91:AF:B2:02:3A', $output );
    }

    public function test_Normalize_Leading_Zeros() : void
    {
        $output = Mac::normalize( mac: '54:91:AF:B2:2:3A' );
        $this->assertSame( '54:91:AF:B2:02:3A', $output );
    }

    public function test_Normalize_Normal() : void
    {
        $output = Mac::normalize( mac: '54:91:AF:B2:02:3A' );
        $this->assertSame( '54:91:AF:B2:02:3A', $output );
    }

    public function test_Normalize_Empty() : void
    {
        $output = Mac::normalize( mac: '' );
        $this->assertSame( '', $output );
    }

    public function test_Normalize_Short() : void
    {
        $output = Mac::normalize( mac: '54:91:AF' );
        $this->assertSame( '54:91:AF', $output );
    }

    public function test_Normalize_Invalid_Chars() : void
    {
        $output = Mac::normalize( mac: '54:91:AF:B2:02:ZZ' );
        $this->assertSame( '54:91:AF:B2:02:ZZ', $output );
    }

    public function test_Normalize_Random() : void
    {
        $output = Mac::normalize( mac: 'not-a-mac' );
        $this->assertSame( 'not-a-mac', $output );
    }

/*
----------------------------------------------------------------------------- */

    public function test_Strip_Colon_Lower() : void
    {
        $output = Mac::strip( mac: '54:91:af:b2:02:3a' );
        $this->assertSame( '5491AFB2023A', $output );
    }

    public function test_Strip_Dashes() : void
    {
        $output = Mac::strip( mac: '54-91-AF-B2-02-3A' );
        $this->assertSame( '5491AFB2023A', $output );
    }

    public function test_Strip_Dots() : void
    {
        $output = Mac::strip( mac: '5491.AFB2.023A' );
        $this->assertSame( '5491AFB2023A', $output );
    }

    public function test_Strip_Hex_Lower() : void
    {
        $output = Mac::strip( mac: '5491afb2023a' );
        $this->assertSame( '5491AFB2023A', $output );
    }

    public function test_Strip_Lead_Zero() : void
    {
        $output = Mac::strip( mac: '54:91:AF:B2:2:3A' );
        $this->assertSame( '5491AFB2023A', $output );
    }

    public function test_Strip_Stripped() : void
    {
        $output = Mac::strip( mac: '5491AFB2023A' );
        $this->assertSame( '5491AFB2023A', $output );
    }

    public function test_Strip_Empty() : void
    {
        $output = Mac::strip( mac: '' );
        $this->assertSame( '', $output );
    }

    public function test_Strip_Invalid_Char() : void
    {
        $output = Mac::strip( mac: '54:91:AF:B2:02:ZZ' );
        $this->assertSame( '5491AFB202ZZ', $output );
    }

}
