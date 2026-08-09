<?php

namespace OpenSoutheners\LaravelApiable\Tests\Documentation;

use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Documentation\QueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Support\Apiable;
use OpenSoutheners\LaravelApiable\Tests\Fixtures\Tag;
use PHPUnit\Framework\TestCase;

class QueryParamTest extends TestCase
{
    public function test_from_filter_attribute_with_similar_operator(): void
    {
        $attr = new FilterQueryParam('title', AllowedFilter::SIMILAR, '*', 'Filter by title');
        $param = QueryParam::fromFilterAttribute($attr);

        $this->assertSame('filter[title][like]', $param->key);
        $this->assertSame('filter', $param->kind);
        $this->assertSame('Filter by title', $param->description);
    }

    public function test_from_filter_attribute_with_exact_operator(): void
    {
        $attr = new FilterQueryParam('status', AllowedFilter::EXACT);
        $param = QueryParam::fromFilterAttribute($attr);

        $this->assertSame('filter[status][equal]', $param->key);
    }

    public function test_from_filter_attribute_with_comparison_operators(): void
    {
        $this->assertSame('filter[age][lt]', QueryParam::fromFilterAttribute(new FilterQueryParam('age', AllowedFilter::LOWER_THAN))->key);
        $this->assertSame('filter[age][gt]', QueryParam::fromFilterAttribute(new FilterQueryParam('age', AllowedFilter::GREATER_THAN))->key);
        $this->assertSame('filter[age][lte]', QueryParam::fromFilterAttribute(new FilterQueryParam('age', AllowedFilter::LOWER_OR_EQUAL_THAN))->key);
        $this->assertSame('filter[age][gte]', QueryParam::fromFilterAttribute(new FilterQueryParam('age', AllowedFilter::GREATER_OR_EQUAL_THAN))->key);
        $this->assertSame('filter[active][scope]', QueryParam::fromFilterAttribute(new FilterQueryParam('active', AllowedFilter::SCOPE))->key);
    }

    public function test_from_sort_attribute_descending(): void
    {
        $attr = new SortQueryParam('created_at', AllowedSort::DESCENDANT, 'Sort by date');
        $param = QueryParam::fromSortAttribute($attr);

        $this->assertSame('sort', $param->key);
        $this->assertSame('sort', $param->kind);
        $this->assertSame('-created_at', $param->values);
        $this->assertSame('Sort by date', $param->description);
    }

    public function test_from_sort_attribute_ascending(): void
    {
        $attr = new SortQueryParam('name', AllowedSort::ASCENDANT);
        $param = QueryParam::fromSortAttribute($attr);

        $this->assertSame('name', $param->values);
    }

    public function test_from_sort_attribute_both_directions(): void
    {
        $attr = new SortQueryParam('name', AllowedSort::BOTH);
        $param = QueryParam::fromSortAttribute($attr);

        $this->assertSame('name,-name', $param->values);
    }

    public function test_from_include_attribute_string(): void
    {
        $attr = new IncludeQueryParam('tags', 'Include tags');
        $param = QueryParam::fromIncludeAttribute($attr);

        $this->assertSame('include', $param->key);
        $this->assertSame('include', $param->kind);
        $this->assertSame('tags', $param->values);
    }

    public function test_from_include_attribute_array(): void
    {
        $attr = new IncludeQueryParam(['tags', 'author']);
        $param = QueryParam::fromIncludeAttribute($attr);

        $this->assertSame('tags,author', $param->values);
    }

    public function test_from_fields_attribute(): void
    {
        $attr = new FieldsQueryParam('post', ['title', 'body'], 'Sparse fieldset');
        $param = QueryParam::fromFieldsAttribute($attr);

        $this->assertSame('fields[post]', $param->key);
        $this->assertSame('fields', $param->kind);
        $this->assertSame('title,body', $param->values);
        $this->assertSame('Sparse fieldset', $param->description);
    }

    public function test_from_appends_attribute(): void
    {
        $attr = new AppendsQueryParam('post', ['is_featured', 'word_count'], 'Append computed fields');
        $param = QueryParam::fromAppendsAttribute($attr);

        $this->assertSame('appends[post]', $param->key);
        $this->assertSame('appends', $param->kind);
        $this->assertSame('is_featured,word_count', $param->values);
    }

    public function test_from_appends_attribute_resolves_model_class_to_resource_type_slug(): void
    {
        // Apiable's resource-type map is a shared static, isolate it from ambient
        // state that other suites in the same process may have configured.
        $originalMap = Apiable::getModelResourceTypeMap();
        Apiable::modelResourceTypeMap([]);

        try {
            $attr = new AppendsQueryParam(Tag::class, ['is_featured'], 'Append computed fields');
            $param = QueryParam::fromAppendsAttribute($attr);

            $this->assertSame('appends[tag]', $param->key);
            $this->assertStringNotContainsString(Tag::class, $param->key);
        } finally {
            Apiable::modelResourceTypeMap($originalMap);
        }
    }

    public function test_from_appends_attribute_resolves_model_class_through_resource_type_map(): void
    {
        $originalMap = Apiable::getModelResourceTypeMap();
        Apiable::modelResourceTypeMap([Tag::class => 'label']);

        try {
            $attr = new AppendsQueryParam(Tag::class, ['is_featured'], 'Append computed fields');
            $param = QueryParam::fromAppendsAttribute($attr);

            $this->assertSame('appends[label]', $param->key);
        } finally {
            Apiable::modelResourceTypeMap($originalMap);
        }
    }

    public function test_from_fields_attribute_resolves_model_class_to_resource_type_slug(): void
    {
        $originalMap = Apiable::getModelResourceTypeMap();
        Apiable::modelResourceTypeMap([]);

        try {
            $attr = new FieldsQueryParam(Tag::class, ['name'], 'Sparse fieldset');
            $param = QueryParam::fromFieldsAttribute($attr);

            $this->assertSame('fields[tag]', $param->key);
            $this->assertStringNotContainsString(Tag::class, $param->key);
        } finally {
            Apiable::modelResourceTypeMap($originalMap);
        }
    }

    public function test_from_search_attribute(): void
    {
        $attr = new SearchQueryParam(true, 'Full-text search');
        $param = QueryParam::fromSearchAttribute($attr);

        $this->assertSame('search', $param->key);
        $this->assertSame('search', $param->kind);
        $this->assertSame('Full-text search', $param->description);
    }

    public function test_from_search_filter_attribute(): void
    {
        $attr = new SearchFilterQueryParam('title', '*', 'Search by title');
        $param = QueryParam::fromSearchFilterAttribute($attr);

        $this->assertSame('search[fields][title]', $param->key);
        $this->assertSame('search', $param->kind);
    }

    public function test_merge_combines_values_and_descriptions_for_same_key(): void
    {
        $a = new QueryParam('sort', 'sort', 'Sort by creation date', '-created_at');
        $b = new QueryParam('sort', 'sort', 'Sort by likes', 'likes');

        $merged = QueryParam::merge($a, $b);

        $this->assertSame('sort', $merged->key);
        $this->assertSame('sort', $merged->kind);
        $this->assertStringContainsString('-created_at', $merged->values);
        $this->assertStringContainsString('likes', $merged->values);
        $this->assertStringContainsString('Sort by creation date', $merged->description);
        $this->assertStringContainsString('Sort by likes', $merged->description);
    }

    public function test_merge_with_wildcard_values_stays_wildcard(): void
    {
        $a = new QueryParam('filter[status]', 'filter', '', '*');
        $b = new QueryParam('filter[status]', 'filter', '', 'todo,done');

        $merged = QueryParam::merge($a, $b);

        $this->assertSame('*', $merged->values);
    }

    public function test_merge_does_not_duplicate_identical_values(): void
    {
        $a = new QueryParam('include', 'include', 'Include tags', 'tags');
        $b = new QueryParam('include', 'include', 'Include tags', 'tags');

        $merged = QueryParam::merge($a, $b);

        $this->assertSame('tags', $merged->values);
        $this->assertSame('Include tags', $merged->description);
    }

    public function test_to_array(): void
    {
        $param = new QueryParam('filter[title][like]', 'filter', 'Title filter', 'foo', false);
        $arr = $param->toArray();

        $this->assertSame('filter[title][like]', $arr['key']);
        $this->assertSame('filter', $arr['kind']);
        $this->assertSame('Title filter', $arr['description']);
        $this->assertSame('foo', $arr['values']);
        $this->assertFalse($arr['required']);
    }
}
