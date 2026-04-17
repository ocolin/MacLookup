# MacLookup

## Table of Contents

- [What is it?](#What-is-it)
- [How does it work?](#How-does-it-work)
- [Note from Author](#Note-from-Author)
- [Installation](#Installation)
- [Instantiation](#Instantiation)
  - [Arguments](#Arguments)
  - [File Driver](#File-Driver)
  - [Memory Driver](#Memory-Driver)
  - [Database Driver](#Database-Driver)
- [Lookups](#Lookups)
  - [Lookup](#Lookup)
  - [BulkLookup](#BulkLookup)
- [Special cases](#Special-cases)
  - [Invalid MAC](#Invalid-MAC)
  - [Private MAC](#Private-MAC)
  - [Not Found](#Not-Found)
- [Formatting](#Formatting)
- [Updating](#Updating)
- [Benchmarks](#Benchmarks)

---
## What is it?

This is a small tool for looking up MAC vendor information of a give MAC address or group of MAC addresses. 

---

## How does it work?

Upon first instantiation it downloads the library of MAC vendors from IEEE from which it can then do local lookups directly rather than connecting to an external server.

---

## Note from Author

This version is not compatible with previous versions. The reason for this is that the previous versions were part of testing and it was not expected that they would get used by anyone. Apologies for the incompatibility but they were done in a hurry and not written for a userbase. Any further versions will be done so in a more compatible way. 

---

## Installation

composer ocolin/mac-lookup

---

## Instantiation

MacLookup has 3 static functions for instantiation depending on the intended use. Keep in mind that upon first use, this plugin will download the IEEE database which may take a few seconds. Once downloaded it will read from the local copy.

### Arguments

Each instantiation can take two optional arguments. It was designed to not be used with these options, but they exist for special circumstances.

| Name       | Type    | Default                              | Description                                                                       |
|------------|---------|--------------------------------------|-----------------------------------------------------------------------------------|
| dataPath   | string  | System temp files dir or project dir | Allows you so specify a folder to store the Vendor data                           |
| autoUpdate | boolean | true                                 | If the vendor data is not found on instantiation, aotumatically download new data |

### File Driver

This instantiation stores the data in a local file and does lookups by parsing through that file. This is the slowest method, but also the most memory efficient. Use this method if saving memory is your biggest concern.

#### Basic Example

```php
$maclookup = Ocolin\MacLookup::file();
```

#### Advanced Example

```php
$maclookup = Ocolin\MacLookup::file( dataPath: __DIR__ . '/files', autoUpdate: false );
```

### Memory Driver

This instantiation stores all the vendor data in memory. This driver is the fastest, but uses the most memory. Use this version if you don't care about memory usage.

#### Basic Example

```php
$maclookup = Ocolin\MacLookup::memory();
```

#### Advanced Example

```php
$maclookup = Ocolin\MacLookup::memory( dataPath: __DIR__ . '/files', autoUpdate: false );
```

### Database Driver

This instantiation stores the vendor data in an SQLite database. It's a compromise between two methods. It gives you the low memory usage of the file driver, and close to the same speed as the memory driver. However, it requires that you have the SQLite extension installed in PHP.

#### Basic Example

```php
$maclookup = Ocolin\MacLookup::database();
```

#### Advanced Example

```php
$maclookup = Ocolin\MacLookup::database( dataPath: __DIR__ . '/files', autoUpdate: false );
```

---

## Lookups

There are two methods of looking up mac addresses. One for looking up an individual mac addres, and another for doing bulk lookups.

### Lookup

This function allows you to look up a single mac address. 

#### Example:

```php

$vendor = $maclookup->lookup( mac: '54:91:AF:B2:02:3A' );
print_r( $vendor );

/*
Ocolin\MacLookup\Vendor Object
(
    [mac] => 54:91:AF:B2:02:3A
    [registry] => MA-M
    [assignment] => 5491AFB
    [name] => Hyperconn Pte. ltd
    [address] => 128 Tanjong Pagar Road Singapore(088535) Singapore  SG 088535 
)
*/
```

### BulkLookup

This method allows you to look up an array of MAC addresses. When searching for multiple addresses you can send them in a single request rather than make repeated single requests. This speeds up lookups when you know you have multiple lookups to make.

The output is an array using the MAC address as an array index so you can lookup a particular MAC address by array index name.

#### Example:

```php
$vendors = $maclookup->bulkLookup( macs: [ '54:91:AF:B2:02:3A', '[B8:27:EB:00:00:01' ] );
print_r( $vendors )

/*
Array
(
    [54:91:AF:B2:02:3A] => Ocolin\MacLookup\Vendor Object
        (
            [mac] => 54:91:AF:B2:02:3A
            [registry] => MA-M
            [assignment] => 5491AFB
            [name] => Hyperconn Pte. ltd
            [address] => 128 Tanjong Pagar Road Singapore(088535) Singapore  SG 088535 
        )

    [B8:27:EB:00:00:01] => Ocolin\MacLookup\Vendor Object
        (
            [mac] => B8:27:EB:00:00:01
            [registry] => MA-L
            [assignment] => B827EB
            [name] => Raspberry Pi Foundation
            [address] => Mitchell Wood House Caldecote Cambridgeshire US CB23 7NU 
        )
)
*/
```

---

## Special cases

Sometimes as MAC address may be invalid, private, or not found in the IEEE database. In these cases the registry value of the returned vendor object will inform you of the status if not something in an IEEE registry.

### Invalid MAC

MacLookup will detect any invalid mac addresses and return an invalid address to save time from a long lookup.

```php
Ocolin\MacLookup\Vendor Object
(
    [mac] => ZZ:91:AF:B2:02:3A 
    [registry] => Invalid
    [assignment] => 
    [name] => 
    [address] => 
)
```

### Private MAC

MAC addresses in the reserved private space will also be spared a long lookup and returned indicating they are private.

```php
Ocolin\MacLookup\Vendor Object
(
    [mac] => 52:91:AF:B2:02:00 
    [registry] => Private
    [assignment] => 
    [name] => 
    [address] => 
)
```

### Not Found

Some MAC addresses are valid, public, but not registered with IEEE. These will have a "Not Found" indication for the resigstry.

```php
// Don't have a known Not Found MAC to use in example!
Ocolin\MacLookup\Vendor Object
(
    [mac] => 99:91:AF:B2:99:99 
    [registry] => Not Found
    [assignment] => 
    [name] => 
    [address] => 
)
```

---

## Formatting

MacLookup will accept MAC addresses that are coma separated or dash separated, dot separated or raw Hex. Leader zeros for will also be automatically added if needed.

---

## Updating

Each driver has an update function for manually updating. This is not intended to be used much, however it is available should anyone want to refresh the vendor list from IEEE. The same can also be done by deleting the existing vendor file.

```php
$macLookup->update();
// Returns void. Will throw an error if update is unsuccessful.

```

---

## Benchmarks 

There is a benchmark utility that can be used to compare times of lookups if needed. You can provide a MAC address for an argument and it will use all 3 drivers and compare the memory usage and speed.

```
================================
  MacLookup Benchmark Results
================================

MAC Address: 54:91:AF:B2:02:3A

FILE DRIVER
    Memory : 4 MB
    Time   : 11.0120773315 ms

MEMORY DRIVER
    Memory : 46.5 MB
    Time   : 0.3309249878 ms

DATABASE DRIVER
    Memory : 4 MB
    Time   : 0.4739761353 ms
================================
```