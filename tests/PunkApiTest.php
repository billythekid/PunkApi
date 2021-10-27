<?php
/**
 * Created by PhpStorm.
 * User: billythekid
 * Date: 10/02/2017
 * Time: 19:40
 */

namespace tests;

use billythekid\PunkApi;
use GuzzleHttp\Exception\ClientException;

class PunkApiTest extends \PHPUnit_Framework_TestCase
{
    /* @var PunkApi */
    protected static $pApi;
    protected static $b;
    /* @var PunkApi */
    private $punkApi;
    private $beers;

    public static function setUpBeforeClass()
    {
        self::$pApi = new PunkApi();
        self::$b    = self::$pApi->getBeers(); //this allows us a collection of beers at a single API hit.
    }

    public function setUp()
    {
        $this->punkApi = self::$pApi;
        $this->beers   = self::$b;
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->punkApi->clearParams();
    }

    // test that the API returns a collection of beers
    public function testGetBeers()
    {
        $this->assertInstanceOf('Illuminate\Support\Collection', $this->beers);
    }
    

    public function testItReturns25BeersByDefault()
    {
        $this->assertEquals(25, count($this->beers));
    }

    public function testItReturnsBeersInAscendingOrderById()
    {
        foreach ($this->beers as $index => $beer)
        {
            $this->assertAttributeEquals($index + 1, "id", $beer);
        }
    }

    public function testItGetsBeersById()
    {
        $beer = $this->punkApi->getBeerById(192)[0];
        $this->assertAttributeEquals(192, "id", $beer);
    }

    public function testItThrowsAnExceptionIfIncorrectParamIsPassed()
    {
        $this->expectException(ClientException::class);
        $beer = $this->punkApi->abvAbove(-1)->getBeers();
    }

    /* THE FOLLOWING TESTS ONLY TEST THE ENDPOINTS, WITHOUT ACTUALLY HITTING THE API */

    public function testItThrowsADeprecatedNoticeForV1Use()
    {
        try
        {
            $punkApi = PunkApi::create('someAPIKey');
        } catch (\Exception $e)
        {
            $this->assertEquals("V1 of the API is deprecated and should not be used.", $e->getMessage());
        }
    }

    public function testItReturns10BeersWhenPerPageIsSetTo10()
    {
        $endpoint = $this->punkApi->perPage(10)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?per_page=10', urldecode($endpoint));
    }

    public function testItGetsBeersByIds()
    {
        $endpoint = $this->punkApi->ids([192, 224])->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?ids=192|224', urldecode($endpoint));
    }

    public function testItReturnsBeersWithAbvGreaterThan10()
    {
        $endpoint = $this->punkApi->abvAbove(10)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?abv_gt=10', urldecode($endpoint));
    }

    public function testItReturnsBeersWithAbvLowerThan10()
    {
        $endpoint = $this->punkApi->abvBelow(10)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?abv_lt=10', urldecode($endpoint));
    }

    public function testItReturnsBeersWithANameMatchingPunk()
    {
        $endpoint = $this->punkApi->named("punk")->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?beer_name=punk', urldecode($endpoint));
    }

    public function testItReturnsBeersBrewedAfter02_2014()
    {
        $endpoint = $this->punkApi->brewedAfter('02-2014')->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?brewed_after=02-2014', urldecode($endpoint));
    }

    public function testItReturnsBeersBrewedBefore02_2014()
    {
        $endpoint = $this->punkApi->brewedBefore('02-2014')->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?brewed_before=02-2014', urldecode($endpoint));
    }

    public function testItReturnsBeersWithEbcGreaterThan25()
    {
        $endpoint = $this->punkApi->ebcAbove(25)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?ebc_gt=25', urldecode($endpoint));
    }

    public function testItReturnsBeersWithEbcLowerThan25()
    {
        $endpoint = $this->punkApi->ebcBelow(25)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?ebc_lt=25', urldecode($endpoint));
    }

    public function testItReturnsBeersWithIbuGreaterThan12()
    {
        $endpoint = $this->punkApi->ibuAbove(12)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?ibu_gt=12', urldecode($endpoint));
    }

    public function testItReturnsBeersWithIbuLowerThan12()
    {
        $endpoint = $this->punkApi->ibuBelow(12)->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?ibu_lt=12', urldecode($endpoint));
    }

    public function testItReturnsBeersWithFoodPairingOfMint()
    {
        $endpoint = $this->punkApi->food('mint')->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?food=mint', urldecode($endpoint));
    }

    public function testItReturnsBeersThatContainHopsWithNelsonSauvin()
    {
        $endpoint = $this->punkApi->hops('nelson')->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?hops=nelson', urldecode($endpoint));
    }

    public function testItReturnsBeersWithMaltMatchingPale()
    {
        $endpoint = $this->punkApi->malt('pale')->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?malt=pale', urldecode($endpoint));
    }

    public function testItReturnsBeersWithYeastMatchingWyeast1056()
    {
        $endpoint = $this->punkApi->yeast('Wyeast_1056')->getEndpoint();
        $this->assertEquals('https://api.punkapi.com/v2/beers?yeast=Wyeast_1056', urldecode($endpoint));
    }

}
