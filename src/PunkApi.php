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
    private $apiRoot = 'https://punkapi.com/api/v1/beers?';
    /**
     * @var array
     */
    private $params = [];
    /**
     * @var array
     */
    private $allowedParams = [
        'abv_gt',        //number	Returns all beers with ABV greater than the supplied number
        'abv_lt',        //number	Returns all beers with ABV less than the supplied number
        'ibu_gt',        //number	Returns all beers with IBU greater than the supplied number
        'ibu_lt',        //number	Returns all beers with IBU less than the supplied number
        'ebc_gt',        //number	Returns all beers with EBC greater than the supplied number
        'ebc_lt',        //number	Returns all beers with EBC less than the supplied number
        'beer_name',     //string	Returns all beers matching the supplied name (this will match partial strings as well so e.g punk will return Punk IPA)
        'yeast',         //string	Returns all beers matching the supplied yeast name, this also matches partial strings
        'brewed_before', //date	Returns all beers brewed before this date, the date format is mm-yyyy e.g 10-2011
        'brewed_after',  //date	Returns all beers brewed after this date, the date format is mm-yyyy e.g 10-2011
        'hops',          //string	Returns all beers matching the supplied hops name, this also matches partial strings
        'malt',          //string	Returns all beers matching the supplied malt name, this also matches partial strings
        'food',          //string	Returns all beers matching the supplied food string, this also matches partial strings
    ];

    /**
     * PunkApi constructor.
     *
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
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
        return rtrim($this->apiRoot . http_build_query($this->params), '?');
    }

    /**
     * Queries the PunkAPI with the current parameters of this object.
     *
     * @return array of beer objects
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
