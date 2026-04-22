<?php

namespace OpenSoutheners\LaravelApiable\Tests\Testing;

use Illuminate\Support\Facades\Route;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Testing\AssertableJsonApi;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Post;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\User;
use OpenSoutheners\LaravelApiable\Tests\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class AssertableJsonApiTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Document validation
    // -------------------------------------------------------------------------

    public function test_valid_json_api_document_with_data_passes()
    {
        Route::get('/', fn () => response()->json([
            'data' => ['id' => '1', 'type' => 'posts', 'attributes' => ['title' => 'Hello']],
        ]));

        $this->get('/')->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasId(1)->hasType('posts');
        });
    }

    public function test_valid_json_api_document_with_only_meta_passes()
    {
        Route::get('/', fn () => response()->json(['meta' => ['version' => '1']]));

        $this->get('/')->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->meta(fn ($m) => $m->where('version', '1'));
        });
    }

    public function test_valid_json_api_document_with_errors_passes()
    {
        Route::get('/', fn () => response()->json([
            'errors' => [['status' => '422', 'title' => 'Invalid']],
        ]));

        $this->get('/')->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->errors(fn ($e) => $e->count(1)->etc());
        });
    }

    public function test_document_without_required_members_fails()
    {
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => response()->json(['other' => 'value']));

        $this->get('/')->assertJsonApi(fn () => null);
    }

    public function test_document_with_both_data_and_errors_fails()
    {
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => response()->json([
            'data' => [],
            'errors' => [['status' => '422']],
        ]));

        $this->get('/')->assertJsonApi(fn () => null);
    }

    // -------------------------------------------------------------------------
    // Resource vs collection detection
    // -------------------------------------------------------------------------

    public function test_is_resource_passes_for_single_resource()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'A'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->isResource()
        );
    }

    public function test_is_collection_passes_for_collection()
    {
        Route::get('/', fn () => Apiable::toJsonApi(collect([
            new Post(['id' => 1, 'title' => 'A']),
            new Post(['id' => 2, 'title' => 'B']),
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->isCollection()->hasSize(2)
        );
    }

    public function test_is_resource_fails_for_collection()
    {
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => Apiable::toJsonApi(collect([
            new Post(['id' => 1, 'title' => 'A']),
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->isResource()
        );
    }

    // -------------------------------------------------------------------------
    // hasId / hasType on single resource
    // -------------------------------------------------------------------------

    public function test_has_id_and_type_on_resource()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 42, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasId(42)->hasType('post');
        });
    }

    public function test_has_id_coerced_to_string()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 7, 'title' => 'T'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->hasId('7');
        });
    }

    // -------------------------------------------------------------------------
    // hasAttribute — tightened key/value semantics
    // -------------------------------------------------------------------------

    public function test_has_attribute_checks_key_only()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttribute('title')
        );
    }

    public function test_has_attribute_checks_exact_value()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttribute('title', 'Hello')
        );
    }

    public function test_has_attribute_fails_for_wrong_key()
    {
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttribute('nonexistent')
        );
    }

    public function test_has_attribute_fails_for_wrong_value()
    {
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttribute('title', 'Wrong')
        );
    }

    public function test_has_attribute_value_check_does_not_match_other_keys()
    {
        // Value 'Hello' is the value of 'title', NOT 'abstract'. Passing 'Hello'
        // as the expected value of 'abstract' must fail (old assertContains bug).
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => Apiable::toJsonApi(new Post([
            'id' => 1,
            'title' => 'Hello',
            'abstract' => 'World',
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttribute('abstract', 'Hello')
        );
    }

    // -------------------------------------------------------------------------
    // hasAttributes — list and map forms
    // -------------------------------------------------------------------------

    public function test_has_attributes_map_form()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post([
            'id' => 1,
            'title' => 'Hello',
            'abstract' => 'World',
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttributes(['title' => 'Hello', 'abstract' => 'World'])
        );
    }

    public function test_has_attributes_list_form_checks_keys_only()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post([
            'id' => 1,
            'title' => 'Hello',
            'abstract' => 'World',
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->hasAttributes(['title', 'abstract'])
        );
    }

    // -------------------------------------------------------------------------
    // Collection scoping via at()
    // -------------------------------------------------------------------------

    public function test_at_scopes_into_collection_item()
    {
        Route::get('/', fn () => Apiable::toJsonApi(collect([
            new Post(['id' => 1, 'title' => 'First']),
            new Post(['id' => 2, 'title' => 'Second']),
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->at(0)->hasAttribute('title', 'First');
            $assert->at(1)->hasAttribute('title', 'Second');
        });
    }

    public function test_at_with_closure_scopes_and_returns_parent()
    {
        Route::get('/', fn () => Apiable::toJsonApi(collect([
            new Post(['id' => 1, 'title' => 'First']),
            new Post(['id' => 2, 'title' => 'Second']),
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            // When closure given, at() returns $this (the collection); add etc() to skip
            // interaction checking on unexamined keys in the item scope.
            $result = $assert->at(0, fn (AssertableJsonApi $item) => $item->hasAttribute('title', 'First')->etc());
            $result->hasSize(2);
        });
    }

    public function test_at_out_of_bounds_fails()
    {
        $this->expectException(AssertionFailedError::class);

        Route::get('/', fn () => Apiable::toJsonApi(collect([
            new Post(['id' => 1, 'title' => 'Only']),
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(
            fn (AssertableJsonApi $assert) => $assert->at(99)
        );
    }

    // -------------------------------------------------------------------------
    // Scoping methods: data(), meta(), links(), errors()
    // -------------------------------------------------------------------------

    public function test_data_scope_gives_full_parent_assertable_json_api()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 5, 'title' => 'Test'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->data(function (AssertableJsonApi $d) {
                $d->where('type', 'post')
                    ->where('id', '5')
                    ->has('attributes');
            });
        });
    }

    public function test_meta_scope_accesses_meta_member()
    {
        Route::get('/', fn () => response()->json([
            'data' => ['id' => '1', 'type' => 'posts', 'attributes' => []],
            'meta' => ['page' => 1, 'total' => 42],
        ]));

        $this->get('/')->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->meta(fn (AssertableJsonApi $m) => $m->where('page', 1)->where('total', 42));
        });
    }

    public function test_links_scope_accesses_links_member()
    {
        Route::get('/', fn () => response()->json([
            'data' => ['id' => '1', 'type' => 'posts', 'attributes' => []],
            'links' => ['self' => 'http://example.com/posts/1'],
        ]));

        $this->get('/')->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->links(fn (AssertableJsonApi $l) => $l->where('self', 'http://example.com/posts/1'));
        });
    }

    public function test_errors_scope_accesses_errors_member()
    {
        Route::get('/', fn () => response()->json([
            'errors' => [
                ['status' => '422', 'title' => 'Invalid field'],
                ['status' => '422', 'title' => 'Required field missing'],
            ],
        ]));

        $this->get('/')->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->errors(function (AssertableJsonApi $e) {
                $e->count(2)
                    ->where('0.status', '422')
                    ->where('1.title', 'Required field missing')
                    ->etc();
            });
        });
    }

    // -------------------------------------------------------------------------
    // Parent AssertableJson methods accessible on AssertableJsonApi
    // -------------------------------------------------------------------------

    public function test_parent_where_method_works_on_single_resource()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        // etc() suppresses the "unexpected properties" interaction check for keys not
        // explicitly examined inside this scope.
        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->data(fn (AssertableJsonApi $d) => $d->where('type', 'post')->etc());
        });
    }

    public function test_parent_has_method_works_inside_data_scope()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->data(fn (AssertableJsonApi $d) => $d->has('attributes')->has('id')->has('type')->etc());
        });
    }

    public function test_parent_missing_method_works_inside_data_scope()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hello'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->data(fn (AssertableJsonApi $d) => $d->missing('nonexistent_field')->etc());
        });
    }

    // -------------------------------------------------------------------------
    // toArray returns full document props (parent semantics)
    // -------------------------------------------------------------------------

    public function test_to_array_returns_full_document()
    {
        Route::get('/', fn () => Apiable::toJsonApi(new Post(['id' => 1, 'title' => 'Hi'])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $arr = $assert->toArray();
            $this->assertArrayHasKey('data', $arr);
            $this->assertArrayHasKey('id', $arr['data']);
            $this->assertArrayHasKey('type', $arr['data']);
            $this->assertArrayHasKey('attributes', $arr['data']);
        });
    }

    public function test_to_array_returns_full_document_for_collection()
    {
        Route::get('/', fn () => Apiable::toJsonApi(collect([
            new Post(['id' => 1, 'title' => 'A']),
            new Post(['id' => 2, 'title' => 'B']),
        ])));

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $arr = $assert->toArray();
            $this->assertArrayHasKey('data', $arr);
            $this->assertIsArray($arr['data']);
            $this->assertCount(2, $arr['data']);
        });
    }

    // -------------------------------------------------------------------------
    // relationship() scoping
    // -------------------------------------------------------------------------

    public function test_relationship_scope_accesses_relationship_data()
    {
        Route::get('/', function () {
            $post = new Post(['id' => 5, 'title' => 'Test', 'abstract' => 'Abs']);
            $post->setRelation('author', new User([
                'id' => 3,
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'password' => 'secret',
            ]));

            return Apiable::toJsonApi($post);
        });

        $this->get('/', ['Accept' => 'application/json'])->assertJsonApi(function (AssertableJsonApi $assert) {
            $assert->relationship('author', function (AssertableJsonApi $rel) {
                $rel->where('type', 'client')->has('id');
            });
        });
    }
}
