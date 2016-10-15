# PunkApi
PHP wrapper to query the PunkAPI https://punkapi.com by [Sam Mason](https://twitter.com/samjbmason)

Full API docs for this project available at https://billythekid.github.io/PunkApi

##Installation
via composer `composer require billythekid/punk-api:dev-master`

##Usage

Create a new instance of the client
```php
$punkApi = new billythekid\PunkApi('YOUR_API_KEY');
```
or
```php
$punkApi = billythekid\PunkApi::create('YOUR_API_KEY');
```
(grab your api key at https://punkapi.com)

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
*        `abv_gt`        number	Returns all beers with ABV greater than the number
*        `abv_lt`        number	Returns all beers with ABV less than the number
*        `ibu_gt`        number	Returns all beers with IBU greater than the number
*        `ibu_lt`        number	Returns all beers with IBU less than the number
*        `ebc_gt`        number	Returns all beers with EBC greater than the number
*        `ebc_lt`        number	Returns all beers with EBC less than the number
*        `beer_name`     string	Returns all beers matching the supplied name (this will match partial strings as well so e.g punk will return Punk IPA)
*        `yeast`         string	Returns all beers matching the supplied yeast name, this also matches partial strings
*        `brewed_before` date(string)	Returns all beers brewed before this date, the date format is mm-yyyy e.g 10-2011
*        `brewed_after`  date(string)	Returns all beers brewed after this date, the date format is mm-yyyy e.g 10-2011
*        `hops`          string	Returns all beers matching the supplied hops name, this also matches partial strings
*        `malt`          string	Returns all beers matching the supplied malt name, this also matches partial strings
*        `food`          string	Returns all beers matching the supplied food string, this also matches partial strings

####Example
```php
//get all beers with an ABV between 4 and 9, called *punk*
$punkApi = \billythekid\PunkApi::create("PUNK_API_KEY")
  ->addParams(['abv_gt' => 4, 'abv_lt' => 9])
  ->addParams(['beer_name' => "punk"])
  ->getEndpoint(); // https://punkapi.com/api/v1/beers?abv_gt=4&abv_lt=9&beer_name=punk
```

-
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
    ->getEndpoint(); // https://punkapi.com/api/v1/beers?abv_lt=9&ibu_lt=100
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
    ->getEndpoint(); //https://punkapi.com/api/v1/beers
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
    ->getBeers(); // returns a PHP array of beers - see the Example JSON Response at https://punkapi.com/documentation
```
-
