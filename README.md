# MacLookup

A small tool for looking up the Vendor of a given MAC address. 

# Requirements

PHP 8.3

# Usage

Example code using the lookup function. 

```
$vendor_info = MacLookup::lookup( 
    mac: '30:23:03:3A:F3:55' 
);
```

## Output Example

Example of the output of a MAC lookup query.

```
stdClass Object
(
    [organization] => Belkin International Inc.
    [mac] => 30:23:03
    [company_id] => 302303
    [address] => 12045 East Waterfront Drive
Playa Vista  null  90094
US
)
```

## Updating Vendor list

This will download and parse a new vendor list from the IEEE website.

```
MacLookup::update();
```