# PunkApi
[![Latest Stable Version](https://poser.pugx.org/billythekid/punk-api/version)](https://packagist.org/packages/billythekid/punk-api)
[![Total Downloads](https://poser.pugx.org/billythekid/punk-api/downloads)](https://packagist.org/packages/billythekid/punk-api)
[![License](https://poser.pugx.org/billythekid/punk-api/license)](https://packagist.org/packages/billythekid/punk-api)

A PHP wrapper for the [PunkAPI](https://punkapi-alxiw.amvera.io/v3/) — all 415 BrewDog DIY Dog recipes.

The original PunkAPI by [Sam Mason](https://github.com/sammdec/punkapi) is no longer online. This wrapper now defaults to the **v3 API** hosted by [alxiw](https://github.com/alxiw/punkapi), and includes **bundled offline data** as a fallback so your code always gets results — even if the API is unreachable.

## Requirements

- PHP >= 8.3
- Guzzle ^7.0 (installed automatically via Composer)

## Installation

```bash
composer require billythekid/punk-api
```

## Quick Start

```php
use billythekid\PunkApi;

$punkApi = PunkApi::create();

// Get beers (hits the live API, falls back to local data automatically)
$beers = $punkApi->getBeers();

// Check if the fallback was used
if ($punkApi->usedFallback()) {
    echo "Results came from bundled local data";
}
```

## API Versions

The wrapper defaults to **v3** (recommended). The legacy v2 API is still supported but may be offline.

```php
// v3 (default) — https://punkapi-alxiw.amvera.io/v3/
$punkApi = PunkApi::create();

// v2 (legacy) — https://api.punkapi.com/v2/
$punkApi = PunkApi::create('v2');
```

## Offline Fallback

All 415 beers from DIY Dog v8 are bundled in `data/beers.json`. When the live API can't be reached (connection timeout, DNS failure, etc.), the wrapper automatically filters and paginates from this local dataset. The same query parameters work identically whether hitting the API or the fallback.

Use `usedFallback()` to check which source was used:

```php
$beers = $punkApi->named('punk')->getBeers();

if ($punkApi->usedFallback()) {
    // data came from local bundle
}
```

## Methods

### Creating an Instance

```php
$punkApi = new PunkApi();          // v3 by default
$punkApi = PunkApi::create();      // static constructor
$punkApi = PunkApi::create('v2');   // use legacy v2 API
```

### Querying Beers

```php
getBeers()        // Returns an array of beer objects matching current params
getBeerById($id)  // Returns an array with a single beer by ID
getRandomBeer()   // Returns an array with a single random beer
```

### Building the Endpoint

```php
getEndpoint()     // Returns the URL that would be hit (does not make a request)
```

### Parameters

Use `addParams()` with an associative array, or the chainable helper methods:

| Parameter | Type | Description |
|-----------|------|-------------|
| `abv_gt` | number | Beers with ABV greater than this |
| `abv_lt` | number | Beers with ABV less than this |
| `ibu_gt` | number | Beers with IBU greater than this |
| `ibu_lt` | number | Beers with IBU less than this |
| `ebc_gt` | number | Beers with EBC greater than this |
| `ebc_lt` | number | Beers with EBC less than this |
| `beer_name` | string | Partial name match (e.g. "punk" matches Punk IPA) |
| `yeast` | string | Partial yeast name match |
| `hops` | string | Partial hops name match |
| `malt` | string | Partial malt name match |
| `food` | string | Partial food pairing match |
| `brewed_before` | string | Beers brewed before this date (mm-yyyy) |
| `brewed_after` | string | Beers brewed after this date (mm-yyyy) |
| `page` | number | Page number for pagination |
| `per_page` | number | Results per page (default: 25 for v2, 30 for v3) |
| `ids` | string | Comma-separated IDs for v3, pipe-separated for v2 |

### Chainable Helper Methods

```php
$punkApi->abvAbove(4)
    ->abvBelow(9)
    ->named('punk')
    ->getBeers();
```

All helpers: `abvAbove()`, `abvBelow()`, `ibuAbove()`, `ibuBelow()`, `ebcAbove()`, `ebcBelow()`, `named()`, `yeast()`, `brewedBefore()`, `brewedAfter()`, `hops()`, `malt()`, `food()`, `page()`, `perPage()`, `ids()`

The `ids()` method accepts an array of IDs or a string. It automatically formats them for the active API version (commas for v3, pipes for v2).

### Managing Parameters

```php
addParams(['abv_gt' => 4, 'beer_name' => 'punk'])  // Add params (chainable)
removeParams('beer_name', 'abv_gt')                  // Remove specific params (chainable)
clearParams()                                         // Remove all params (chainable)
```

## Examples

```php
use billythekid\PunkApi;

$punkApi = PunkApi::create();

// Get all beers with ABV between 4 and 9, named "punk"
$beers = $punkApi
    ->abvAbove(4)
    ->abvBelow(9)
    ->named('punk')
    ->getBeers();

// Same thing with addParams
$beers = $punkApi
    ->addParams(['abv_gt' => 4, 'abv_lt' => 9, 'beer_name' => 'punk'])
    ->getBeers();

// Get a specific beer
$beer = $punkApi->getBeerById(1);

// Get a random beer
$beer = $punkApi->getRandomBeer();

// Check the endpoint without making a request
$url = $punkApi->named('ipa')->getEndpoint();
// https://punkapi-alxiw.amvera.io/v3/beers?beer_name=ipa&page=1

// Get beers by multiple IDs
$beers = $punkApi->ids([1, 5, 10])->getBeers();
```

## Changelog

#### v 2.0.1
* Bugfix - `ids()` now normalizes separators automatically (pipes → commas on v3, commas → pipes on v2) so existing code passing pipe-separated strings continues to work

#### v 2.0.0
* **Breaking**: Removed API key parameter from constructor (v3 API requires no auth)
* **Breaking**: Requires PHP >= 8.3
* Default API changed to v3 (`punkapi-alxiw.amvera.io`)
* Added offline fallback with bundled data (415 beers from DIY Dog v8)
* Added `usedFallback()` method
* `ids()` now uses commas for v3 (pipes still used for v2)
* Updated to Guzzle ^7.0 and PHPUnit ^13.0

#### v 1.1.2 - Mar 23, 2017
* Bugfix - not passing a param to `create()` threw an error

#### v 1.1.1 - Feb 10, 2017
* Bugfix - `perPage()` wasn't working properly
* Added more tests

#### v 1.1.0 - Feb 10, 2017
* Non-breaking update to use version 2 of the Punk API by default
* Added `ids()` method and `ids` parameter
* Added tests

#### v 1.0.0 - Oct 15, 2016
* Initial release

## License

[MIT](LICENSE)
