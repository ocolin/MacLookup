<?php

declare( strict_types = 1 );

namespace Ocolin\MacLookup;

final class MacLookup
{

/* FILE BASED LOOKUP
----------------------------------------------------------------------------- */

    /**
     * The file based driver stores the IEEE data in a file and queries
     * that file for vendors. This option uses the least memory, but is
     * the slowest.
     *
     * @param string $path Optional path for data storage.
     * @param bool $autoUpdate Opt to not update storage when missing.
     * @return FileDriver
     */
    public static function file(
        string $path = '',
          bool $autoUpdate = true
    ) : FileDriver
    {
        $path = self::resolvePath( path: $path );

        return new FileDriver( dataPath: $path, autoUpdate: $autoUpdate );
    }



/* MEMORY BASED LOOKUP
----------------------------------------------------------------------------- */

    /**
     * This driver stores the IEEE data in a file, but loads the complete
     * library into memory. This is faster than file based, but uses a lot
     * more memory.
     *
     * @param string $path Optional path for data storage.
     * @param bool $autoUpdate Opt to not update storage when missing.
     * @return MemoryDriver
     */
    public static function memory(
        string $path = '',
          bool $autoUpdate = true
    ) : MemoryDriver
    {
        $path = self::resolvePath( path: $path );

        return new MemoryDriver( dataPath: $path, autoUpdate: $autoUpdate );
    }



/* DATABASE BASED LOOKUP
----------------------------------------------------------------------------- */

    /**
     * The database driver stores the IEEE data in a database. This driver
     * gets both speed and low memory usage, but requires that users have
     * the SQLite drivers installed to use. See composer.json suggest.
     *
     * @param string $path Optional path for data storage.
     * @param bool $autoUpdate Opt to not update storage when missing.
     * @return DatabaseDriver
     */
    public static function database(
        string $path = '',
          bool $autoUpdate = true
    ) : DatabaseDriver
    {
        $path = self::resolvePath( path: $path );

        return new DatabaseDriver( dataPath: $path, autoUpdate: $autoUpdate );
    }



/* RESOLVE STORAGE PATH DIRECTORY
----------------------------------------------------------------------------- */

    /**
     * @param string $path Optional path for data storage.
     * @return string Path that data will be stored under.
     */
    private static function resolvePath( string $path = '' ): string
    {
        if( $path !== '' ) { return $path; }

        $cwd = getcwd();
        if( $cwd !== false ) {
            return $cwd . DIRECTORY_SEPARATOR . '.maclookup';
        }

        trigger_error(
            message: 'MacLookup: Could not determine working directory, ' .
            'falling back to temp directory. Pass a dataPath to avoid ' .
            're-downloading on system restart.',
            error_level: E_USER_WARNING
        );

        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'maclookup';
    }
}