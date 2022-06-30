<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Repository;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\PredictableDataGenerator;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;

class JsonApiRequestTest extends TestCase
{
    /**
     * @var \OpenSoutheners\LaravelApiable\Tests\Fixtures\PredictableDataGenerator
     */
    protected $generator;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->generator = PredictableDataGenerator::generate();
    }

    public function testFilteringByNonAllowedAttributeWillGetEverything()
    {
        Route::get('/', function () {
            $repository = new Repository(Tag::class);

            return $repository->list();
        });

        $response = $this->get('/?filter[name]=in');

        $response->assertSuccessful();

        $response->assertJsonCount(10, 'data');
    }

    public function testFilteringByAllowedAttributeWillGetFilteredResults()
    {
        Route::get('/', function () {
            $repository = new Repository(Tag::class);

            $repository->allowFilter('name');

            return $repository->list();
        });

        $response = $this->get('/?filter[name]=in');

        $response->assertSuccessful();

        $response->assertJsonCount(4, 'data');
    }

    public function testFilteringOrValuesByAllowedAttributeValue()
    {
        Route::get('/', function () {
            $repository = new Repository(Post::class);

            $repository->allowFilter(AllowedFilter::exact('status', ['Active', 'Archived']));

            return $repository->list();
        });

        $response = $this->get('/?filter[status]=Active,Inactive');

        $response->assertJsonCount(2, 'data');
    }

    public function testFilteringByNonAllowedAttributeValueWillGetEverything()
    {
        $this->markTestIncomplete('FIXME: Need to fix filter patterns');

        Route::get('/', function () {
            $repository = new Repository(Tag::class);

            $repository->allowFilter('name', 'in*');

            return $repository->list();
        });

        $response = $this->get('/?filter[name]=et,in');

        // var_dump($response->json());
    }
}
