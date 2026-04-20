<?php

namespace billythekid;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class PunkApi
 * Wrapper class for querying the PunkAPI.
 *
 * Supports the v3 API at https://punkapi-alxiw.amvera.io/v3/ (default)
 * and the legacy v2 API at https://api.punkapi.com/v2/.
 * Falls back to bundled local data (415 beers from DIY Dog v8) when the API is unreachable.
 *
 * @package billythekid
 */
class PunkApi
{
    private const IMAGE_BASE_URL = 'https://punkapi-alxiw.amvera.io/v3/images/';
    private const IMAGE_FALLBACK_BASE_URL = 'https://raw.githubusercontent.com/alxiw/punkapi/master/img/';

    private string $apiVersion;
    private string $apiRoot;
    private Client $client;
    private array $params = [];
    private bool $usedFallback = false;
    private static ?array $localData = null;

    private array $allowedParams = [
        'abv_gt',        //number   Returns all beers with ABV greater than the supplied number
        'abv_lt',        //number   Returns all beers with ABV less than the supplied number
        'ibu_gt',        //number   Returns all beers with IBU greater than the supplied number
        'ibu_lt',        //number   Returns all beers with IBU less than the supplied number
        'ebc_gt',        //number   Returns all beers with EBC greater than the supplied number
        'ebc_lt',        //number   Returns all beers with EBC less than the supplied number
        'beer_name',     //string   Returns all beers matching the supplied name (partial match)
        'yeast',         //string   Returns all beers matching the supplied yeast name (partial match)
        'brewed_before', //date     Returns all beers brewed before this date (mm-yyyy or yyyy)
        'brewed_after',  //date     Returns all beers brewed after this date (mm-yyyy or yyyy)
        'hops',          //string   Returns all beers matching the supplied hops name (partial match)
        'malt',          //string   Returns all beers matching the supplied malt name (partial match)
        'food',          //string   Returns all beers matching the supplied food string (partial match)
        'page',          //number   Return the beers from the page given (responses are paginated)
        'per_page',      //number   Change the number of beers returned per page (default: 25 for v2, 30 for v3)
        'ids',           //string   A list of ID numbers (comma-separated for v3, pipe-separated for v2)
    ];

    /**
     * PunkApi constructor.
     *
     * @param string $apiVersion API version to use: 'v3' (default, recommended) or 'v2' (legacy)
     * @param Client|null $client Optional Guzzle client for dependency injection / testing
     */
    public function __construct(string $apiVersion = 'v3', ?Client $client = null)
    {
        $this->apiVersion = $apiVersion;

        if ($apiVersion === 'v3')
        {
            $this->apiRoot = 'https://punkapi-alxiw.amvera.io/v3/beers';
        } elseif ($apiVersion === 'v2')
        {
            $this->apiRoot = 'https://api.punkapi.com/v2/beers';
        } else
        {
            trigger_error("Only v2 and v3 of the API are supported. v3 is recommended.", E_USER_WARNING);
            $this->apiRoot = 'https://punkapi-alxiw.amvera.io/v3/beers';
            $this->apiVersion = 'v3';
        }

        $this->client = $client ?? new Client(['timeout' => 5, 'connect_timeout' => 3]);
    }

    /**
     * Static constructor.
     *
     * @param string $apiVersion
     * @param Client|null $client
     * @return PunkApi
     */
    public static function create(string $apiVersion = 'v3', ?Client $client = null): self
    {
        return new self($apiVersion, $client);
    }

    /**
     * Returns true if the last request used the bundled fallback data instead of the live API.
     *
     * @return bool
     */
    public function usedFallback(): bool
    {
        return $this->usedFallback;
    }

    /**
     * Returns the URL that would be hit at the current state of this object.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        $params = $this->params;

        // v3 requires page parameter
        if ($this->apiVersion === 'v3' && !isset($params['page']))
        {
            $params['page'] = 1;
        }

        return rtrim($this->apiRoot . '?' . http_build_query($params), '?');
    }

    /**
     * Queries the PunkAPI with the current parameters.
     * Falls back to bundled local data if the API is unreachable.
     *
     * @return array of beer \StdClass objects
     */
    public function getBeers(): array
    {
        $this->usedFallback = false;

        try
        {
            $response = $this->client->get($this->getEndpoint());
            $decoded = json_decode($response->getBody()->getContents());

            if (is_array($decoded))
            {
                return $this->normalizeImageUrls($decoded);
            }
        } catch (GuzzleException)
        {
            // Fall through to local data
        }

        $this->usedFallback = true;

        return $this->normalizeImageUrls($this->filterLocalData($this->loadLocalData()));
    }

    /**
     * Empties the parameters of this object.
     *
     * @return $this
     */
    public function clearParams(): self
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
    public function addParams(array $params): self
    {
        $this->params = array_merge($this->params, $this->cleanParams($params));

        return $this;
    }

    /**
     * Removes given parameters from this object.
     *
     * @param string ...$badParams
     * @return $this
     */
    public function removeParams(string ...$badParams): self
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
     * Get a random beer.
     * Falls back to a random beer from bundled data if the API is unreachable.
     *
     * @return array containing a single beer \StdClass object
     */
    public function getRandomBeer(): array
    {
        $this->usedFallback = false;

        try
        {
            $response = $this->client->get($this->apiRoot . '/random');
            $decoded = json_decode($response->getBody()->getContents());

            if ($decoded !== null)
            {
                // Normalize: v3 returns an object, v2 returns an array
                return $this->normalizeImageUrls(is_array($decoded) ? $decoded : [$decoded]);
            }
        } catch (GuzzleException)
        {
            // Fall through to local data
        }

        $this->usedFallback = true;
        $beers = $this->loadLocalData();

        return $this->normalizeImageUrls([$beers[array_rand($beers)]]);
    }

    /**
     * Get a beer by its ID number.
     * Falls back to bundled data if the API is unreachable.
     *
     * @param int $beerId
     * @return array containing a single beer \StdClass object
     */
    public function getBeerById(int $beerId): array
    {
        $this->usedFallback = false;

        try
        {
            $response = $this->client->get($this->apiRoot . '/' . $beerId);
            $decoded = json_decode($response->getBody()->getContents());

            if ($decoded !== null)
            {
                // Normalize: v3 returns an object, v2 returns an array
                return $this->normalizeImageUrls(is_array($decoded) ? $decoded : [$decoded]);
            }
        } catch (GuzzleException)
        {
            // Fall through to local data
        }

        $this->usedFallback = true;
        $beers = $this->loadLocalData();

        foreach ($beers as $beer)
        {
            if ($beer->id === $beerId)
            {
                return $this->normalizeImageUrls([$beer]);
            }
        }

        return [];
    }

    /**
     * Set the parameters to return the given page of results
     *
     * @param int $pageNumber
     * @return $this
     */
    public function page(int $pageNumber): self
    {
        $this->addParams(['page' => $pageNumber]);

        return $this;
    }

    /**
     * Set the number of beers to return per page
     *
     * @param int $number
     * @return $this
     */
    public function perPage(int $number): self
    {
        $this->addParams(['per_page' => $number]);

        return $this;
    }

    /**
     * Sets the abv_gt parameter to the given number.
     *
     * @param float|int $number
     * @return $this
     */
    public function abvAbove(float|int $number): self
    {
        $this->addParams(['abv_gt' => $number]);

        return $this;
    }

    /**
     * Sets the abv_lt parameter to the given number.
     *
     * @param float|int $number
     * @return $this
     */
    public function abvBelow(float|int $number): self
    {
        $this->addParams(['abv_lt' => $number]);

        return $this;
    }

    /**
     * Sets the ibu_gt parameter to the given number.
     *
     * @param float|int $number
     * @return $this
     */
    public function ibuAbove(float|int $number): self
    {
        $this->addParams(['ibu_gt' => $number]);

        return $this;
    }

    /**
     * Sets the ibu_lt parameter to the given number.
     *
     * @param float|int $number
     * @return $this
     */
    public function ibuBelow(float|int $number): self
    {
        $this->addParams(['ibu_lt' => $number]);

        return $this;
    }

    /**
     * Sets the ebc_gt parameter to the given number.
     *
     * @param float|int $number
     * @return $this
     */
    public function ebcAbove(float|int $number): self
    {
        $this->addParams(['ebc_gt' => $number]);

        return $this;
    }

    /**
     * Sets the ebc_lt parameter to the given number.
     *
     * @param float|int $number
     * @return $this
     */
    public function ebcBelow(float|int $number): self
    {
        $this->addParams(['ebc_lt' => $number]);

        return $this;
    }

    /**
     * Sets the beer_name parameter to the given beer name.
     *
     * @param string $beerName
     * @return $this
     */
    public function named(string $beerName): self
    {
        $this->addParams(['beer_name' => $beerName]);

        return $this;
    }

    /**
     * Sets the yeast parameter to the given yeast name
     *
     * @param string $yeastName
     * @return $this
     */
    public function yeast(string $yeastName): self
    {
        $this->addParams(['yeast' => $yeastName]);

        return $this;
    }

    /**
     * Sets the hops parameter to the given hops name
     *
     * @param string $hopsName
     * @return $this
     */
    public function hops(string $hopsName): self
    {
        $this->addParams(['hops' => $hopsName]);

        return $this;
    }

    /**
     * Sets the malt parameter to the given malt name
     *
     * @param string $maltName
     * @return $this
     */
    public function malt(string $maltName): self
    {
        $this->addParams(['malt' => $maltName]);

        return $this;
    }

    /**
     * Sets the brewed_before parameter to the given date
     *
     * @param string $date format: mm-yyyy or yyyy
     * @return $this
     */
    public function brewedBefore(string $date): self
    {
        $this->addParams(['brewed_before' => $date]);

        return $this;
    }

    /**
     * Sets the brewed_after parameter to the given date
     *
     * @param string $date format: mm-yyyy or yyyy
     * @return $this
     */
    public function brewedAfter(string $date): self
    {
        $this->addParams(['brewed_after' => $date]);

        return $this;
    }

    /**
     * Sets the food parameter to the given food name
     *
     * @param string $foodName
     * @return $this
     */
    public function food(string $foodName): self
    {
        $this->addParams(['food' => $foodName]);

        return $this;
    }

    /**
     * Sets the ids parameter to the given ids.
     * Accepts an array of ID numbers or a pre-formatted string.
     *
     * @param array|string $ids
     * @return $this
     */
    public function ids(array|string $ids): self
    {
        if (is_array($ids))
        {
            $separator = $this->apiVersion === 'v3' ? ',' : '|';
            $ids = join($separator, $ids);
        } else
        {
            // Normalize separator for the active API version
            if ($this->apiVersion === 'v3')
            {
                $ids = str_replace('|', ',', $ids);
            } else
            {
                $ids = str_replace(',', '|', $ids);
            }
        }

        $this->addParams(['ids' => $ids]);

        return $this;
    }

    /**
     * Normalizes image fields on an array of beer objects.
     *
     * Sets `image_url` to the full v3 API image URL and `image_url_fallback`
     * to the GitHub raw URL. Handles bare filenames (v3/fallback data),
     * full v2 URLs (https://images.punkapi.com/v2/...), and already-normalized URLs.
     *
     * @param array $beers
     * @return array
     */
    private function normalizeImageUrls(array $beers): array
    {
        foreach ($beers as $beer)
        {
            // Determine the bare filename from whichever field is present
            $raw = $beer->image ?? $beer->image_url ?? null;

            if ($raw === null)
            {
                $beer->image = null;
                $beer->image_url = null;
                $beer->image_url_fallback = null;
                continue;
            }

            // Extract just the filename if it's a full URL
            $filename = basename($raw);

            // Set all three so consumers can use ->image or ->image_url interchangeably
            $beer->image = $filename;
            $beer->image_url = self::IMAGE_BASE_URL . $filename;
            $beer->image_url_fallback = self::IMAGE_FALLBACK_BASE_URL . $filename;
        }

        return $beers;
    }

    /**
     * Helper method, parameter validation.
     *
     * @param array $params
     * @return array
     */
    private function cleanParams(array $params): array
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

    /**
     * Loads the bundled beer data from the JSON file.
     * Caches the result in a static property to avoid re-reading on every call.
     *
     * @return array of beer \StdClass objects
     */
    private function loadLocalData(): array
    {
        if (self::$localData !== null)
        {
            return self::$localData;
        }

        $dataFile = __DIR__ . '/../data/beers.json';

        if (!file_exists($dataFile))
        {
            return [];
        }

        $decoded = json_decode(file_get_contents($dataFile));
        self::$localData = is_array($decoded) ? $decoded : [];

        return self::$localData;
    }

    /**
     * Applies the current parameters to filter the local beer data,
     * replicating the API's filtering and pagination behavior.
     *
     * @param array $beers
     * @return array
     */
    private function filterLocalData(array $beers): array
    {
        $params = $this->params;

        $beers = array_filter($beers, function ($beer) use ($params)
        {
            // Numeric filters: abv, ibu, ebc
            foreach (['abv', 'ibu', 'ebc'] as $field)
            {
                if (isset($params[$field . '_gt']))
                {
                    if ($beer->$field === null || $beer->$field <= $params[$field . '_gt'])
                    {
                        return false;
                    }
                }
                if (isset($params[$field . '_lt']))
                {
                    if ($beer->$field === null || $beer->$field >= $params[$field . '_lt'])
                    {
                        return false;
                    }
                }
            }

            // Name filter (case-insensitive partial match)
            if (isset($params['beer_name']))
            {
                if (stripos($beer->name, $params['beer_name']) === false)
                {
                    return false;
                }
            }

            // Yeast filter (case-insensitive partial match)
            if (isset($params['yeast']))
            {
                $yeast = $beer->ingredients->yeast ?? null;
                if ($yeast === null || stripos($yeast, $params['yeast']) === false)
                {
                    return false;
                }
            }

            // Hops filter (case-insensitive partial match on any hop name)
            if (isset($params['hops']))
            {
                $hops = $beer->ingredients->hops ?? [];
                $found = false;
                foreach ($hops as $hop)
                {
                    if (stripos($hop->name, $params['hops']) !== false)
                    {
                        $found = true;
                        break;
                    }
                }
                if (!$found)
                {
                    return false;
                }
            }

            // Malt filter (case-insensitive partial match on any malt name)
            if (isset($params['malt']))
            {
                $malts = $beer->ingredients->malt ?? [];
                $found = false;
                foreach ($malts as $malt)
                {
                    if (stripos($malt->name, $params['malt']) !== false)
                    {
                        $found = true;
                        break;
                    }
                }
                if (!$found)
                {
                    return false;
                }
            }

            // Food pairing filter (case-insensitive partial match on any pairing)
            if (isset($params['food']))
            {
                $pairings = $beer->food_pairing ?? [];
                $found = false;
                foreach ($pairings as $pairing)
                {
                    if (stripos($pairing, $params['food']) !== false)
                    {
                        $found = true;
                        break;
                    }
                }
                if (!$found)
                {
                    return false;
                }
            }

            // IDs filter
            if (isset($params['ids']))
            {
                // Support both comma and pipe separators
                $ids = array_map('intval', preg_split('/[,|]/', $params['ids']));
                if (!in_array($beer->id, $ids))
                {
                    return false;
                }
            }

            // Date filters
            if (isset($params['brewed_before']))
            {
                $brewDate = $this->parseBrewDate($beer->first_brewed ?? '');
                $filterDate = $this->parseBrewDate($params['brewed_before']);
                if ($brewDate === null || $filterDate === null || $brewDate >= $filterDate)
                {
                    return false;
                }
            }

            if (isset($params['brewed_after']))
            {
                $brewDate = $this->parseBrewDate($beer->first_brewed ?? '');
                $filterDate = $this->parseBrewDate($params['brewed_after']);
                if ($brewDate === null || $filterDate === null || $brewDate <= $filterDate)
                {
                    return false;
                }
            }

            return true;
        });

        // Re-index after filtering
        $beers = array_values($beers);

        // Pagination
        $perPage = (int) ($params['per_page'] ?? 25);
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        return array_slice($beers, $offset, $perPage);
    }

    /**
     * Parses a brew date string into a comparable integer (YYYYMM format).
     * Supports formats: "MM/YYYY", "MM-YYYY", "YYYY"
     *
     * @param string $date
     * @return int|null
     */
    private function parseBrewDate(string $date): ?int
    {
        if (preg_match('/^(\d{1,2})[\/\-](\d{4})$/', $date, $matches))
        {
            return (int) $matches[2] * 100 + (int) $matches[1];
        }

        if (preg_match('/^(\d{4})$/', $date, $matches))
        {
            return (int) $matches[1] * 100 + 1;
        }

        return null;
    }
}
