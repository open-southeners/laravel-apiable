<?php

namespace OpenSoutheners\LaravelApiable\Tests;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;

class JsonApiRelationshipsTest extends TestCase
{
    /**
     * @var \OpenSoutheners\LaravelApiable\Tests\Fixtures\Post
     */
    protected $authoredPost;

    /**
     * @var \OpenSoutheners\LaravelApiable\Tests\Fixtures\Post
     */
    protected $lonelyPost;

    /**
     * @var \OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag
     */
    protected $postTag;

    /**
     * @var \OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag
     */
    protected $lonelyTag;

    public function testCollectionHasAnyClientAuthorRelationship()
    {
        Route::get('/', function () {
            $this->authoredPost = new Post([
                'id' => 5,
                'status' => 'Published',
                'title' => 'Test Title',
                'abstract' => 'Test abstract',
            ]);

            $this->authoredPost->setRelation('author', new User([
                'id' => 1,
                'name' => 'Myself',
                'email' => 'me@internet.org',
                'password' => '1234',
            ]));

            $this->lonelyPost = new Post([
                'id' => 6,
                'status' => 'Published',
                'title' => 'Test Title 2',
            ]);

            return Apiable::toJsonApi(collect([
                $this->authoredPost,
                $this->lonelyPost,
            ]));
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertSuccessful();

        // var_dump($response->json());

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasAnyRelationships('client', true)
                ->hasNotAnyRelationships('post', true);

            $jsonApi->at(0)->hasNotRelationshipWith($this->lonelyPost, true);
        });
    }

    /**
     * @group requiresDatabase
     */
    public function testResourceHasTagsRelationships()
    {
        // TODO: setRelation method doesn't work with hasMany relationships, so need migrations loaded
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::get('/', function () {
            $this->authoredPost = Post::create([
                'status' => 'Published',
                'title' => 'Test Title',
            ]);

            $this->lonelyTag = Tag::create([
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
            ]);

            $this->postTag = Tag::create([
                'name' => 'News',
                'slug' => 'news',
            ]);

            $anotherTag = Tag::create([
                'name' => 'International',
                'slug' => 'international',
            ]);

            $this->authoredPost->tags()->attach([
                $this->postTag->id,
                $anotherTag->id,
            ]);

            $this->authoredPost->author()->associate(
                User::create([
                    'name' => 'Myself',
                    'email' => 'me@internet.org',
                    'password' => '1234',
                ])->id
            );

            $this->authoredPost->save();

            return Apiable::toJsonApi($this->authoredPost->refresh()->loadMissing('author', 'tags'));
        });

        $response = $this->get('/', ['Accept' => 'application/json']);

        $response->assertSuccessful();

        $response->assertJsonApi(function (AssertableJsonApi $jsonApi) {
            $jsonApi->hasRelationshipWith($this->postTag, true)
                ->hasNotRelationshipWith($this->lonelyTag, true);
        });
    }
}
