<?php

namespace billythekid;

use GuzzleHttp\Client;

class PunkApi
{
    private $apiKey;
    private $apiRoot = 'https://punkapi.com/api/v1/beers?';
    private $params = [];
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

    public static function create($apiKey)
    {
        return new self($apiKey);
    }

    public function getEndpoint()
    {
        return $this->apiRoot . http_build_query($this->params);

    }

    public function clearParams()
    {
        $this->params = [];

        return $this;
    }

    public function addParam(Array $params)
    {
        $this->params = $this->cleanParams($params);

        return $this;
    }

    public function removeParam($badParam)
    {
        $this->params = array_filter(array_keys($this->params),function($paramName) use ($badParam){
            return ($paramName !== $badParam);
        });
    }

    public function getBeers()
    {
        $response = $this->client->get($this->getEndpoint(),
            [
                'auth' => [$this->apiKey,$this->apiKey],
            ]
        );

        return $response->getBody();
    }

    private function cleanParams($params)
    {
        return array_filter(array_keys($params), function ($key)
        {
            return in_array($this->allowedParams, $key);
        });
    }


}
