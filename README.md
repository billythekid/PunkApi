# PunkApi
[![Latest Stable Version](https://poser.pugx.org/billythekid/punk-api/version)](https://packagist.org/packages/billythekid/punk-api)
[![Total Downloads](https://poser.pugx.org/billythekid/punk-api/downloads)](https://packagist.org/packages/billythekid/punk-api)
[![Latest Unstable Version](https://poser.pugx.org/billythekid/punk-api/v/unstable)](//packagist.org/packages/billythekid/punk-api)
[![License](https://poser.pugx.org/billythekid/punk-api/license)](https://packagist.org/packages/billythekid/punk-api)
[![Monthly Downloads](https://poser.pugx.org/billythekid/punk-api/d/monthly)](https://packagist.org/packages/billythekid/punk-api)
[![Daily Downloads](https://poser.pugx.org/billythekid/punk-api/d/daily)](https://packagist.org/packages/billythekid/punk-api)
[![composer.lock available](https://poser.pugx.org/billythekid/punk-api/composerlock)](https://packagist.org/packages/billythekid/punk-api)
[![Build Status](https://travis-ci.org/billythekid/PunkApi.svg?branch=master)](https://travis-ci.org/billythekid/PunkApi)

PHP wrapper to query the PunkAPI https://punkapi.com by [Sam Mason](https://twitter.com/samjbmason)

Full API docs for this project available at https://billythekid.github.io/PunkApi/class-billythekid.PunkApi.html

##Installation
via composer `composer require billythekid/punk-api`

##Usage

Create a new instance of the client
```php
$punkApi = new billythekid\PunkApi();
```
or
```php
$punkApi = billythekid\PunkApi::create();
```

###Methods

```php
getEndpoint()
```
Returns the current endpoint that will be hit based on the options provided. Good to check what'll be hit without actually hitting it.
This method is not chainable.

-
```php
addParams(Array $params)
```
This method is chainable.

Add parameters to the search. The following parameter keys are supported:
* `abv_gt`        number        Returns all beers with ABV greater than the number
* `abv_lt`        number        Returns all beers with ABV less than the number
* `ibu_gt`        number        Returns all beers with IBU greater than the number
* `ibu_lt`        number        Returns all beers with IBU less than the number
* `ebc_gt`        number        Returns all beers with EBC greater than the number
* `ebc_lt`        number        Returns all beers with EBC less than the number
* `beer_name`     string        Returns all beers matching the supplied name (this will match partial strings as well so e.g punk will return Punk IPA)
* `yeast`         string        Returns all beers matching the supplied yeast name, this also matches partial strings
* `brewed_before` date(string)  Returns all beers brewed before this date, the date format is mm-yyyy e.g 10-2011
* `brewed_after`  date(string)  Returns all beers brewed after this date, the date format is mm-yyyy e.g 10-2011
* `hops`          string        Returns all beers matching the supplied hops name, this also matches partial strings
* `malt`          string        Returns all beers matching the supplied malt name, this also matches partial strings
* `food`          string        Returns all beers matching the supplied food string, this also matches partial strings
* `page`          number        Return the beers from the page given (responses are paginated)
* `per_page`      number        Change the number of beers returned per page (default - 25)
* `ids`           string        New for V2 - pipe separated string of ID numbers (192|224 etc) 
###The following chainable methods can be used to alter the parameters if you prefer

```php
abvAbove($number)
abvBelow($number)
ibuAbove($number)
ibuBelow($number)
ebcAbove($number)
ebcBelow($number)
named($beerName)
yeast($yeastName)
brewedBefore($date)
brewedAfter($date)
hops($hopsName)
malt($maltName)
food($foodName)
page($pageNumber)
perPage($number)
ids($ids) // can pass an array of ids instead of piping them into a string here.
```

####Examples
```php
//get all beers with an ABV between 4 and 9, called *punk*
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
  ->addParams(['abv_gt' => 4, 'abv_lt' => 9])
  ->addParams(['beer_name' => "punk"])
  ->getEndpoint(); // https://api.punkapi.com/v2/beers?abv_gt=4&abv_lt=9&beer_name=punk

//Chained method for same result
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
  ->abvAbove(4)
  ->abvBelow(9)
  ->named("punk")
  ->getEndpoint(); // https://api.punkapi.com/v2/beers?abv_gt=4&abv_lt=9&beer_name=punk
```

---
```php
removeParams($param1 [, $param2, ..., $paramN])
```
Removes parameters from the search. This method is chainable
####Example
```php
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
    ->addParams(['abv_gt' => 4, 'abv_lt' => 9])
    ->addParams(['beer_name' => "punk"])
    ->removeParams('beer_name', 'abv_gt')
    ->addParams(['ibu_lt'=> 100])
    ->getEndpoint(); // https://api.punkapi.com/v2/beers?abv_lt=9&ibu_lt=100
```

-

```php
clearParams()
```
Empties all the parameters. This method is chainable.
####Example
```php
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
    ->addParams(['abv_gt' => 4, 'abv_lt' => 9])
    ->addParams(['beer_name' => "punk"])
    ->clearParams()
    ->getEndpoint(); //https://api.punkapi.com/v2/beers
```
-
```php
getBeers()
```
Perform a query on the API, returns an array of beers.

####Example
```php
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
    ->addParams(['abv_gt' => 4, 'abv_lt' => 9])
    ->addParams(['beer_name' => "punk"])
    ->removeParams('beer_name', 'abv_gt')
    ->addParams(['ibu_lt'=> 100])
    ->getBeers(); // returns a PHP array of beer objects - see the Example JSON Response at https://punkapi.com/documentation
```
-

```php
getRandomBeer()
getBeerById($beerId)
```
Pull a random beer from the API or pull a specific beer from the API by it's ID number 
####Example
```php
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
    ->getRandomBeer(); // returns an array with a single beer object (StdObject) 
```
---
###Changelog

#####v 1.1.1 - Feb 10, 2017
* Bugfix - perPage() wasn't working properly.
* Added more tests

#####v 1.1.0 - Feb 10, 2017
* Non-breaking update to use version 2 of the Punk Api by default
* Updated docs and readme
* Added `->ids()` endpoint and `ids` paramater
* Added tests

#####v 1.0.0 - Oct 15, 2016
* Initial release