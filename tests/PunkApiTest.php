<?php

namespace tests;

use billythekid\PunkApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PunkApiTest extends TestCase
{
    private PunkApi $punkApi;

    private function makeMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    private function jsonResponse(array|object $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function connectionFailure(): ConnectException
    {
        return new ConnectException('Connection refused', new Request('GET', 'test'));
    }

    public function setUp(): void
    {
        $this->punkApi = new PunkApi();
    }

    public function tearDown(): void
    {
        $this->punkApi->clearParams();
    }

    // -- Constructor and defaults --

    public function testDefaultsToV3Endpoint(): void
    {
        $this->assertStringContainsString('punkapi-alxiw.amvera.io/v3/beers', $this->punkApi->getEndpoint());
    }

    public function testV3EndpointIncludesPageByDefault(): void
    {
        $this->assertStringContainsString('page=1', $this->punkApi->getEndpoint());
    }

    public function testV2EndpointDoesNotForcePageParam(): void
    {
        $api = new PunkApi('v2');
        $this->assertStringNotContainsString('page=', $api->getEndpoint());
    }

    public function testStaticCreateReturnsInstance(): void
    {
        $api = PunkApi::create();
        $this->assertInstanceOf(PunkApi::class, $api);
    }

    // -- Endpoint building tests --

    public function testPerPageSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->perPage(10)->getEndpoint();
        $this->assertStringContainsString('per_page=10', urldecode($endpoint));
    }

    public function testIdsUsesCommasForV3(): void
    {
        $endpoint = $this->punkApi->ids([1, 2, 3])->getEndpoint();
        $this->assertStringContainsString('ids=1,2,3', urldecode($endpoint));
    }

    public function testIdsUsesPipesForV2(): void
    {
        $api = new PunkApi('v2');
        $endpoint = $api->ids([1, 2, 3])->getEndpoint();
        $this->assertStringContainsString('ids=1|2|3', urldecode($endpoint));
    }

    public function testIdsAcceptsStringDirectly(): void
    {
        $endpoint = $this->punkApi->ids('1,2,3')->getEndpoint();
        $this->assertStringContainsString('ids=1,2,3', urldecode($endpoint));
    }

    public function testAbvAboveSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->abvAbove(10)->getEndpoint();
        $this->assertStringContainsString('abv_gt=10', urldecode($endpoint));
    }

    public function testAbvBelowSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->abvBelow(10)->getEndpoint();
        $this->assertStringContainsString('abv_lt=10', urldecode($endpoint));
    }

    public function testIbuAboveSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->ibuAbove(12)->getEndpoint();
        $this->assertStringContainsString('ibu_gt=12', urldecode($endpoint));
    }

    public function testIbuBelowSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->ibuBelow(12)->getEndpoint();
        $this->assertStringContainsString('ibu_lt=12', urldecode($endpoint));
    }

    public function testEbcAboveSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->ebcAbove(25)->getEndpoint();
        $this->assertStringContainsString('ebc_gt=25', urldecode($endpoint));
    }

    public function testEbcBelowSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->ebcBelow(25)->getEndpoint();
        $this->assertStringContainsString('ebc_lt=25', urldecode($endpoint));
    }

    public function testNamedSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->named('punk')->getEndpoint();
        $this->assertStringContainsString('beer_name=punk', urldecode($endpoint));
    }

    public function testBrewedAfterSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->brewedAfter('02-2014')->getEndpoint();
        $this->assertStringContainsString('brewed_after=02-2014', urldecode($endpoint));
    }

    public function testBrewedBeforeSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->brewedBefore('02-2014')->getEndpoint();
        $this->assertStringContainsString('brewed_before=02-2014', urldecode($endpoint));
    }

    public function testFoodSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->food('mint')->getEndpoint();
        $this->assertStringContainsString('food=mint', urldecode($endpoint));
    }

    public function testHopsSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->hops('nelson')->getEndpoint();
        $this->assertStringContainsString('hops=nelson', urldecode($endpoint));
    }

    public function testMaltSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->malt('pale')->getEndpoint();
        $this->assertStringContainsString('malt=pale', urldecode($endpoint));
    }

    public function testYeastSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->yeast('Wyeast_1056')->getEndpoint();
        $this->assertStringContainsString('yeast=Wyeast_1056', urldecode($endpoint));
    }

    public function testPageSetsEndpoint(): void
    {
        $endpoint = $this->punkApi->page(3)->getEndpoint();
        $this->assertStringContainsString('page=3', urldecode($endpoint));
    }

    // -- Chaining and parameter management --

    public function testMultipleParamsChain(): void
    {
        $endpoint = $this->punkApi->abvAbove(5)->ibuBelow(50)->named('pale')->getEndpoint();
        $decoded = urldecode($endpoint);
        $this->assertStringContainsString('abv_gt=5', $decoded);
        $this->assertStringContainsString('ibu_lt=50', $decoded);
        $this->assertStringContainsString('beer_name=pale', $decoded);
    }

    public function testClearParamsRemovesAllParams(): void
    {
        $this->punkApi->abvAbove(5)->named('punk');
        $this->punkApi->clearParams();
        // v3 still has default page=1
        $this->assertStringNotContainsString('abv_gt', $this->punkApi->getEndpoint());
        $this->assertStringNotContainsString('beer_name', $this->punkApi->getEndpoint());
    }

    public function testRemoveParamsRemovesSpecificParams(): void
    {
        $this->punkApi->abvAbove(5)->named('punk')->ibuBelow(50);
        $this->punkApi->removeParams('abv_gt', 'ibu_lt');
        $endpoint = urldecode($this->punkApi->getEndpoint());
        $this->assertStringNotContainsString('abv_gt', $endpoint);
        $this->assertStringNotContainsString('ibu_lt', $endpoint);
        $this->assertStringContainsString('beer_name=punk', $endpoint);
    }

    public function testInvalidParamsAreIgnored(): void
    {
        $this->punkApi->addParams(['invalid_param' => 'value', 'abv_gt' => 10]);
        $endpoint = urldecode($this->punkApi->getEndpoint());
        $this->assertStringNotContainsString('invalid_param', $endpoint);
        $this->assertStringContainsString('abv_gt=10', $endpoint);
    }

    // -- HTTP interaction tests (mocked) --

    public function testGetBeersReturnsDecodedArray(): void
    {
        $beers = [
            ['id' => 1, 'name' => 'Buzz'],
            ['id' => 2, 'name' => 'Trashy Blonde'],
        ];
        $client = $this->makeMockClient([$this->jsonResponse($beers)]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeers();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Buzz', $result[0]->name);
        $this->assertFalse($api->usedFallback());
    }

    public function testGetBeerByIdReturnsArrayFromV3Object(): void
    {
        // v3 returns an object, not an array
        $beer = (object) ['id' => 192, 'name' => 'Punk IPA 2007 - 2010'];
        $client = $this->makeMockClient([$this->jsonResponse($beer)]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeerById(192);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(192, $result[0]->id);
        $this->assertFalse($api->usedFallback());
    }

    public function testGetRandomBeerReturnsArrayFromV3Object(): void
    {
        // v3 returns an object, not an array
        $beer = (object) ['id' => 42, 'name' => 'Nanny State'];
        $client = $this->makeMockClient([$this->jsonResponse($beer)]);
        $api = new PunkApi('v3', $client);

        $result = $api->getRandomBeer();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Nanny State', $result[0]->name);
        $this->assertFalse($api->usedFallback());
    }

    // -- Image URL normalization --

    public function testGetBeersNormalizesImageUrls(): void
    {
        $beers = [
            ['id' => 1, 'name' => 'Buzz', 'image' => '001.png'],
        ];
        $client = $this->makeMockClient([$this->jsonResponse($beers)]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeers();

        $this->assertEquals('001.png', $result[0]->image);
        $this->assertEquals('https://punkapi-alxiw.amvera.io/v3/images/001.png', $result[0]->image_url);
        $this->assertEquals('https://raw.githubusercontent.com/alxiw/punkapi/master/img/001.png', $result[0]->image_url_fallback);
    }

    public function testGetBeerByIdNormalizesImageUrls(): void
    {
        $beer = (object) ['id' => 1, 'name' => 'Buzz', 'image' => '001.png'];
        $client = $this->makeMockClient([$this->jsonResponse($beer)]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeerById(1);

        $this->assertEquals('https://punkapi-alxiw.amvera.io/v3/images/001.png', $result[0]->image_url);
        $this->assertEquals('https://raw.githubusercontent.com/alxiw/punkapi/master/img/001.png', $result[0]->image_url_fallback);
    }

    public function testGetRandomBeerNormalizesImageUrls(): void
    {
        $beer = (object) ['id' => 42, 'name' => 'Nanny State', 'image' => '042.png'];
        $client = $this->makeMockClient([$this->jsonResponse($beer)]);
        $api = new PunkApi('v3', $client);

        $result = $api->getRandomBeer();

        $this->assertEquals('https://punkapi-alxiw.amvera.io/v3/images/042.png', $result[0]->image_url);
        $this->assertEquals('https://raw.githubusercontent.com/alxiw/punkapi/master/img/042.png', $result[0]->image_url_fallback);
    }

    public function testNormalizesV2FullImageUrls(): void
    {
        $beers = [
            ['id' => 1, 'name' => 'Buzz', 'image_url' => 'https://images.punkapi.com/v2/001.png'],
        ];
        $client = $this->makeMockClient([$this->jsonResponse($beers)]);
        $api = new PunkApi('v2', $client);

        $result = $api->getBeers();

        // v2's image_url gets normalized, and ->image is also set for forward compatibility
        $this->assertEquals('001.png', $result[0]->image);
        $this->assertEquals('https://punkapi-alxiw.amvera.io/v3/images/001.png', $result[0]->image_url);
        $this->assertEquals('https://raw.githubusercontent.com/alxiw/punkapi/master/img/001.png', $result[0]->image_url_fallback);
    }

    public function testFallbackDataHasNormalizedImageUrls(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeerById(1);

        $this->assertNotEmpty($result);
        $this->assertEquals('https://punkapi-alxiw.amvera.io/v3/images/001.png', $result[0]->image_url);
        $this->assertEquals('https://raw.githubusercontent.com/alxiw/punkapi/master/img/001.png', $result[0]->image_url_fallback);
    }

    // -- Fallback tests --

    public function testGetBeersFallsBackOnConnectionFailure(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeers();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertTrue($api->usedFallback());
        // Default pagination: 25 per page
        $this->assertLessThanOrEqual(25, count($result));
    }

    public function testGetBeerByIdFallsBackOnConnectionFailure(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeerById(1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->id);
        $this->assertTrue($api->usedFallback());
    }

    public function testGetBeerByIdFallbackReturnsEmptyForInvalidId(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->getBeerById(99999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertTrue($api->usedFallback());
    }

    public function testGetRandomBeerFallsBackOnConnectionFailure(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->getRandomBeer();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertIsInt($result[0]->id);
        $this->assertTrue($api->usedFallback());
    }

    // -- Fallback filtering tests --

    public function testFallbackFiltersAbvAbove(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->abvAbove(40)->getBeers();

        $this->assertNotEmpty($result);
        foreach ($result as $beer)
        {
            $this->assertGreaterThan(40, $beer->abv);
        }
    }

    public function testFallbackFiltersNamed(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->named('punk')->getBeers();

        $this->assertNotEmpty($result);
        foreach ($result as $beer)
        {
            $this->assertStringContainsStringIgnoringCase('punk', $beer->name);
        }
    }

    public function testFallbackFiltersIds(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->ids([1, 10, 100])->getBeers();

        $this->assertCount(3, $result);
        $ids = array_map(fn($b) => $b->id, $result);
        $this->assertEquals([1, 10, 100], $ids);
    }

    public function testFallbackPaginates(): void
    {
        $client = $this->makeMockClient([
            $this->connectionFailure(),
            $this->connectionFailure(),
        ]);
        $api = new PunkApi('v3', $client);

        $page1 = $api->perPage(10)->page(1)->getBeers();
        $this->assertCount(10, $page1);
        $this->assertEquals(1, $page1[0]->id);

        $api->clearParams();
        $page2 = $api->perPage(10)->page(2)->getBeers();
        $this->assertCount(10, $page2);
        $this->assertEquals(11, $page2[0]->id);
    }

    public function testFallbackFiltersBrewedAfter(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->brewedAfter('01-2018')->perPage(80)->getBeers();

        $this->assertNotEmpty($result);
        foreach ($result as $beer)
        {
            // All results should be brewed after January 2018
            $this->assertNotNull($beer->first_brewed);
        }
    }

    public function testFallbackFiltersFoodPairing(): void
    {
        $client = $this->makeMockClient([$this->connectionFailure()]);
        $api = new PunkApi('v3', $client);

        $result = $api->food('chicken')->getBeers();

        $this->assertNotEmpty($result);
        foreach ($result as $beer)
        {
            $found = false;
            foreach ($beer->food_pairing as $pairing)
            {
                if (stripos($pairing, 'chicken') !== false)
                {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Beer '{$beer->name}' should have a food pairing containing 'chicken'");
        }
    }

    public function testUsedFallbackResetsOnSuccessfulApiCall(): void
    {
        $client = $this->makeMockClient([
            $this->connectionFailure(),
            $this->jsonResponse([['id' => 1, 'name' => 'Buzz']]),
        ]);
        $api = new PunkApi('v3', $client);

        $api->getBeers();
        $this->assertTrue($api->usedFallback());

        $api->getBeers();
        $this->assertFalse($api->usedFallback());
    }
}
