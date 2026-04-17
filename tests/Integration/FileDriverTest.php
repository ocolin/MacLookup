<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup\Tests\Integration;

use Ocolin\MacLookup\FileDriver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

class FileDriverTest extends TestCase
{
    private static $temp_dir;
    private static FileDriver $driver;

    #[Group('integration')]
    public function test_lookup_known_mac() : void
    {
        $result = self::$driver->lookup( mac: '54:91:AF:B2:02:3A' );
        $this->assertSame( 'MA-M', $result->registry );
        $this->assertSame( '54:91:AF:B2:02:3A', $result->mac );
        $this->assertSame( '5491AFB', $result->assignment );
        $this->assertSame( 'Hyperconn Pte. ltd', $result->name );
    }

    #[Group('integration')]
    public function test_bulklookup_known_mac() : void
    {
        $results = self::$driver->bulkLookup([
            '54:91:AF:B2:02:3A',
            'B8:27:EB:00:00:01',
            '8C:1F:64:CA:91:23'
        ]);
        $this->assertCount( 3, $results );
        $this->assertArrayHasKey( '54:91:AF:B2:02:3A', $results );
        $this->assertArrayHasKey( 'B8:27:EB:00:00:01', $results );
        $this->assertArrayHasKey( '8C:1F:64:CA:91:23', $results );

        $this->assertSame( '54:91:AF:B2:02:3A', $results['54:91:AF:B2:02:3A']->mac );
        $this->assertSame( 'MA-M', $results['54:91:AF:B2:02:3A']->registry );
        $this->assertSame( 'Hyperconn Pte. ltd', $results['54:91:AF:B2:02:3A']->name );
        $this->assertSame( '5491AFB', $results['54:91:AF:B2:02:3A']->assignment );

        $this->assertSame( 'B8:27:EB:00:00:01', $results['B8:27:EB:00:00:01']->mac );
        $this->assertSame( 'MA-L', $results['B8:27:EB:00:00:01']->registry );
        $this->assertSame( 'Raspberry Pi Foundation', $results['B8:27:EB:00:00:01']->name );
        $this->assertSame( 'B827EB', $results['B8:27:EB:00:00:01']->assignment );

        $this->assertSame( '8C:1F:64:CA:91:23', $results['8C:1F:64:CA:91:23']->mac );
        $this->assertSame( 'MA-S', $results['8C:1F:64:CA:91:23']->registry );
        $this->assertSame( 'Avant Technologies', $results['8C:1F:64:CA:91:23']->name );
        $this->assertSame( '8C1F64CA9', $results['8C:1F:64:CA:91:23']->assignment );
    }

    public static function setUpBeforeClass(): void
    {
        self::$temp_dir = sys_get_temp_dir() . '/maclookup_integration_test';
        if( !file_exists( self::$temp_dir )) {
            mkdir( directory: self::$temp_dir, permissions: 0755, recursive: true );
        }
        self::$driver = new FileDriver( dataPath: self::$temp_dir );
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
}