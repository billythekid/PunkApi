<?php
/**
 * Created by PhpStorm.
 * User: billythekid
 * Date: 10/02/2017
 * Time: 19:40
 */

namespace tests;

use billythekid\PunkApi;

class PunkApiTest extends \PHPUnit_Framework_TestCase
{
    /* @var PunkApi */
    private $punkApi;

    public function setUp()
    {
        $this->punkApi = new PunkApi();
    }

    public function tearDown()
    {
        unset($this->punkApi);
    }

    public function testItReturns25BeersByDefault()
    {
        $beers = $this->punkApi->getBeers();
        $this->assertEquals(25, count($beers));
    }

    public function testItReturns10BeersWhenPerPageIsSetTo10()
    {
        $beers = $this->punkApi->perPage(10)->getBeers();
        $this->assertEquals(25, count($beers));
    }

    public function testItGetsBeersById()
    {
        $beer = $this->punkApi->getBeerById(192)[0];
        $this->assertAttributeEquals(192, "id", $beer);
        $beers = $this->punkApi->ids([192, 224])->getBeers();
        $this->assertAttributeEquals(192, "id", $beers[0]); // Punk IPA (2007 - 2010)
        $this->assertAttributeEquals(224, "id", $beers[1]); // AB:20
    }

}
