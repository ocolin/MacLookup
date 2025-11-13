# MacLookup

A small tool for looking up the Vendor of a given MAC address. 

# Requirements

PHP 8.3

If you want to update the vendor list, you will need the correct
file permissions to write to the src directory. Will try to think of a better solution, but it won't need to be updated often.

# Usage

Example code using the lookup function. 

```php
$vendor_info = MacLookup::lookup( 
    mac: '54:91:AF:B2:02:5B' 
);
```

## Output Example

Example of the output of a MAC lookup query.

```
stdClass Object
(
    [registry] => MA-M
    [assignment] => 5491AFB
    [name] => Hyperconn Pte. ltd
    [address] => 128 Tanjong Pagar Road Singapore(088535) Singapore  SG 088535 
)
```

## Updating Vendor list

This will download and parse a new vendor list from the IEEE website. For this to work you will need ownership permissions to write to the folder. This may not be feasible for all situation.s

```php
$maclookup = new MacLookup();
$macLookup->update();
```

