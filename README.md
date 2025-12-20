# Mac Lookup

This was an experimental project to look for different ways to look up MAC address vendors for other tools. Now that some people are using it I will try to be mindful of not breaking it while experimenting. 

Currently trying 3 methods to see which works best and will likely end up with options to use any of them. The methods I am trying are:

- Streaming the vendor content from a text file for low memory usage.
- Loading all vendor content into memory to increase speed up lookups.
- Loading from a database to see how it compares memory and speed wise.

!!! Version 4 will not be compatible with this one. !!!

Example results:

```php
FILE LOOKUP
Memory used: 0.03 MB
Time: 0.0022971153259277

MEMORY LOOKUP
Memory used: 17.67 MB
Time: 0.0010538339614868

DB LOOKUP
Memory used: 0.02 MB
Time: 0.00094333489735921
```

To not interfere with the first implementation which is streaming from file I have made two new classes for the other methods. I will likely make a version 4 which will incorporate them all into one call since that will not be compatible with this current setup.

## Installation

```php
composer require ocolin/maclookup
```

## Requirements

PDO - Which should come built in to PHP.
PHP 8.2 or higher. 

## Instantiation

Using the MacLookup class does not need instantiation and is a completely static. However the memory and DB classes need instantiation for efficiency.

### Example

```php
$lookup = Ocolin\MacLookup\MacMem();
```

```php
$lookup = Ocolin\MacLookup\MacDB();
```

These will load what is needed and download the vendor content if missing and before being used.

## Models

### DB version MacDB

```php
$maclookup = Ocolin\MacLookup\MacDB();
$vendor = $lookup->lookup( mac: '54:91:AF:B2:02:3A' );
```

Example output:

```
Ocolin\MacLookup\Row Object
(
    [registry] => MA-L
    [assignment] => 302303
    [name] => Belkin International Inc.
    [address] => 12045 East Waterfront Drive Playa Vista null US 90094 
)
```

### Memory version MacMem

```php
$maclookup = Ocolin\MacLookup\MacMem();
$vendor = $lookup->lookup( mac: '54:91:AF:B2:02:3A' );
```

### File version MacLookup

```php
$vendor = MacLookup::lookup( mac: '54:91:AF:B2:02:3A' );
```

## Updating vendor list. 

All three versions have an update() function. However the DB version needs to be instantiated to use the database.

Example:

```php
// FILE
$status = Ocolin\MacLookup\MacLookup::update();

// MEMORY
$status = Ocolin\MacLookup\MacMem::update();

// DB
$maclookup = Ocolin\MacLookup\MacDB();
$status = Ocolin\MacLookup\MacDB->update();
```