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
