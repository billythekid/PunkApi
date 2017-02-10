<?php

namespace billythekid;

use GuzzleHttp\Client;

/**
 * Class PunkApi
 * Wrapper class for querying the PunkAPI https://punkapi.com
 * Docs: https://punkapi.com/documentation
 *
 * @package billythekid
 */
class PunkApi
{
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $apiRoot = 'https://punkapi.com/api/v1/beers';
    /**
     * @var array
     */
    private $params = [];
    /**
     * @var array
     */
    private $allowedParams = [
        'abv_gt',        //number   Returns all beers with ABV greater than the supplied number
        'abv_lt',        //number   Returns all beers with ABV less than the supplied number
        'ibu_gt',        //number   Returns all beers with IBU greater than the supplied number
        'ibu_lt',        //number   Returns all beers with IBU less than the supplied number
        'ebc_gt',        //number   Returns all beers with EBC greater than the supplied number
        'ebc_lt',        //number   Returns all beers with EBC less than the supplied number
        'beer_name',     //string   Returns all beers matching the supplied name (this will match partial strings as well so e.g punk will return Punk IPA)
        'yeast',         //string   Returns all beers matching the supplied yeast name, this also matches partial strings
        'brewed_before', //date     Returns all beers brewed before this date, the date format is mm-yyyy e.g 10-2011
        'brewed_after',  //date     Returns all beers brewed after this date, the date format is mm-yyyy e.g 10-2011
        'hops',          //string   Returns all beers matching the supplied hops name, this also matches partial strings
        'malt',          //string   Returns all beers matching the supplied malt name, this also matches partial strings
        'food',          //string   Returns all beers matching the supplied food string, this also matches partial strings
        'page',          //number   Return the beers from the page given (responses are paginated)
        'per_page',      //number   Change the number of beers returned per page (default - 25)
        'ids',           //string   A list of ID numbers, separated by a pipe | (PunkAPI v2)
    ];

    /**
     * PunkApi constructor.
     *
     * @param $apiKey
     */
    public function __construct($apiKey = 'v2')
    {
        if($apiKey === 'v2')
        {
            $this->apiRoot = 'https://api.punkapi.com/v2/beers';
        }
        $this->apiKey = $apiKey;
        $this->client = new Client;
    }

    /**
     * Static constructor, not really needed since PHP 5.4 but it's nice to have, right?
     *
     * @param $apiKey
     * @return PunkApi
     */
    public static function create($apiKey)
    {
        return new self($apiKey);
    }

    /**
     * Returns the URL that would be hit at the current state of this object.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return rtrim($this->apiRoot . '?' . http_build_query($this->params), '?');
    }

    /**
     * Queries the PunkAPI with the current parameters of this object.
     *
     * @return array of beer \StdClass objects
     * @throws \InvalidArgumentException (via GuzzleHttp\json_decode()
     */
    public function getBeers()
    {
        $response = $this->client->get($this->getEndpoint(),
            [
                'auth' => [$this->apiKey, $this->apiKey],
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }

    /**
     * Empties the parameters of this object.
     *
     * @return $this
     */
    public function clearParams()
    {
        $this->params = [];

        return $this;
    }

    /**
     * Adds parameter options to this object.
     *
     * @param array $params
     * @return $this
     */
    public function addParams(Array $params)
    {
        $this->params = array_merge($this->params, $this->cleanParams($params));

        return $this;
    }

    /**
     * Removes given parameters from this object.
     *
     * @param array ...$badParams
     * @return $this
     */
    public function removeParams(...$badParams)
    {
        $this->params = array_filter($this->params,
            function ($paramName) use ($badParams)
            {
                return (!in_array($paramName, $badParams));
            },
            ARRAY_FILTER_USE_KEY
        );

        return $this;
    }

    /**
     * Get a random beer from the API
     *
     * @return \StdClass beer  object
     */
    public function getRandomBeer()
    {
        $response = $this->client->get($this->apiRoot . '/random',
            [
                'auth' => [$this->apiKey, $this->apiKey],
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }

    /**
     * Get a beer from the API by it's ID number
     *
     * @return \StdClass beer object
     */
    public function getBeerById($beerId)
    {
        $response = $this->client->get($this->apiRoot . '/' . $beerId,
            [
                'auth' => [$this->apiKey, $this->apiKey],
            ]
        );

        return \GuzzleHttp\json_decode($response->getBody());
    }

    /**
     * Set the parameters to return the given page of results
     *
     * @param $pageNumber
     * @return $this
     */
    public function page($pageNumber)
    {
        $this->addParams(['page' => $pageNumber]);

        return $this;
    }

    /**
     * Set the number of beers to return per page
     *
     * @param $number
     * @return $this
     */
    public function perPage($number)
    {
        $this->addParams(['per_page', $number]);

        return $this;
    }

    /**
     * Sets the abv_gt parameter to the given number.
     *
     * @param $number
     * @return $this
     */
    public function abvAbove($number)
    {
        $this->addParams(['abv_gt' => $number]);

        return $this;
    }

    /**
     * Sets the abv_lt parameter to the given number.
     *
     * @param $number
     * @return $this
     */
    public function abvBelow($number)
    {
        $this->addParams(['abv_lt' => $number]);

        return $this;
    }

    /**
     * Sets the ibu_gt parameter to the given number.
     *
     * @param $number
     * @return $this
     */
    public function ibuAbove($number)
    {
        $this->addParams(['ibu_gt' => $number]);

        return $this;
    }

    /**
     * Sets the ibu_lt parameter to the given number.
     *
     * @param $number
     * @return $this
     */
    public function ibuBelow($number)
    {
        $this->addParams(['ibu_lt' => $number]);

        return $this;
    }

    /**
     * Sets the ebc_gt parameter to the given number.
     *
     * @param $number
     * @return $this
     */
    public function ebcAbove($number)
    {
        $this->addParams(['ebc_gt' => $number]);

        return $this;
    }

    /**
     * Sets the ebc_lt parameter to the given number.
     *
     * @param $number
     * @return $this
     */
    public function ebcBelow($number)
    {
        $this->addParams(['ebc_lt' => $number]);

        return $this;
    }

    /**
     * Sets the beer_name parameter to the given beer name.
     *
     * @param $beerName
     * @return $this
     */
    public function named($beerName)
    {
        $this->addParams(['beer_name' => $beerName]);

        return $this;
    }

    /**
     * Sets the yeast parameter to the given yeast name
     *
     * @param $yeastName
     * @return $this
     */
    public function yeast($yeastName)
    {
        $this->addParams(['yeast' => $yeastName]);

        return $this;
    }

    /**
     * Sets the hops parameter to the given hops name
     *
     * @param $hopsName
     * @return $this
     */
    public function hops($hopsName)
    {
        $this->addParams(['hops' => $hopsName]);

        return $this;
    }

    /**
     * Sets the malt parameter to the given malt name
     *
     * @param $maltName
     * @return $this
     */
    public function malt($maltName)
    {
        $this->addParams(['malt' => $maltName]);

        return $this;
    }

    /**
     * Sets the brewed_before parameter to the given date
     *
     * @param $date
     * @return $this
     */
    public function brewedBefore($date)
    {
        $this->addParams(['brewed_before' => $date]);

        return $this;
    }

    /**
     * Sets the brewed_after parameter to the given date
     * @param $date
     * @return $this
     */
    public function brewedAfter($date)
    {
        $this->addParams(['brewed_after' => $date]);

        return $this;
    }

    /**
     * Sets the food parameter to the given food name
     *
     * @param $foodName
     * @return $this
     */
    public function food($foodName)
    {
        $this->addParams(['food' => $foodName]);

        return $this;
    }

    /**
     * Sets the ids paramater to the given ids
     * @param mixed $ids (array of ID numbers or piped string)
     * @return $this
     */
    public function ids($ids)
    {
        if (is_array($ids))
        {
            $ids = join("|",$ids);
        }

        $this->addParams(['ids' => $ids]);

        return $this;
    }

    /**
     * Helper method, parameter validation-ish.
     *
     * @param $params
     * @return array
     */
    private function cleanParams($params)
    {

        return array_filter(
            $params,
            function ($key)
            {
                return in_array($key, $this->allowedParams);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

}
