<?php

namespace OpenSoutheners\LaravelApiable\Documentation;

use Illuminate\Database\Eloquent\Model;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Support\Apiable;

/**
 * Documentation-oriented representation of a single query parameter.
 */
class QueryParam
{
    public function __construct(
        public readonly string $key,
        public readonly string $kind,         // filter|sort|include|fields|appends|search
        public readonly string $description = '',
        public readonly string $values = '*',
        public readonly bool $required = false,
    ) {
        //
    }

    /**
     * @return array{key: string, kind: string, description: string, values: string, required: bool}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'kind' => $this->kind,
            'description' => $this->description,
            'values' => $this->values,
            'required' => $this->required,
        ];
    }

    public static function fromFilterAttribute(FilterQueryParam $attr): self
    {
        $operator = is_array($attr->type) ? ($attr->type[0] ?? AllowedFilter::SIMILAR) : ($attr->type ?? AllowedFilter::SIMILAR);

        $operatorStr = match ((int) $operator) {
            AllowedFilter::EXACT => 'equal',
            AllowedFilter::SCOPE => 'scope',
            AllowedFilter::LOWER_THAN => 'lt',
            AllowedFilter::GREATER_THAN => 'gt',
            AllowedFilter::LOWER_OR_EQUAL_THAN => 'lte',
            AllowedFilter::GREATER_OR_EQUAL_THAN => 'gte',
            default => 'like',
        };

        $values = is_array($attr->values) ? implode(',', $attr->values) : (string) $attr->values;

        return new self(
            key: "filter[{$attr->attribute}][{$operatorStr}]",
            kind: 'filter',
            description: $attr->description,
            values: $values,
        );
    }

    public static function fromSortAttribute(SortQueryParam $attr): self
    {
        $direction = $attr->direction ?? AllowedSort::BOTH;

        $values = match ((int) $direction) {
            AllowedSort::ASCENDANT => $attr->attribute,
            AllowedSort::DESCENDANT => "-{$attr->attribute}",
            default => "{$attr->attribute},-{$attr->attribute}",
        };

        return new self(
            key: 'sort',
            kind: 'sort',
            description: $attr->description,
            values: $values,
        );
    }

    public static function fromIncludeAttribute(IncludeQueryParam $attr): self
    {
        $relationships = is_array($attr->relationships)
            ? implode(',', $attr->relationships)
            : $attr->relationships;

        return new self(
            key: 'include',
            kind: 'include',
            description: $attr->description,
            values: $relationships,
        );
    }

    public static function fromFieldsAttribute(FieldsQueryParam $attr): self
    {
        return new self(
            key: 'fields['.self::resolveResourceType($attr->type).']',
            kind: 'fields',
            description: $attr->description,
            values: implode(',', $attr->fields),
        );
    }

    public static function fromAppendsAttribute(AppendsQueryParam $attr): self
    {
        return new self(
            key: 'appends['.self::resolveResourceType($attr->type).']',
            kind: 'appends',
            description: $attr->description,
            values: implode(',', $attr->attributes),
        );
    }

    public static function fromSearchAttribute(SearchQueryParam $attr): self
    {
        return new self(
            key: 'search',
            kind: 'search',
            description: $attr->description,
        );
    }

    public static function fromSearchFilterAttribute(SearchFilterQueryParam $attr): self
    {
        $values = is_array($attr->values) ? implode(',', $attr->values) : (string) $attr->values;

        return new self(
            key: "search[fields][{$attr->attribute}]",
            kind: 'search',
            description: $attr->description,
            values: $values,
        );
    }

    /**
     * Merge two query params sharing the same key into a single entry that lists
     * every allowed value, used to collapse repeated #[SortQueryParam]/
     * #[IncludeQueryParam] (and other repeatable) attributes on one endpoint.
     */
    public static function merge(self $a, self $b): self
    {
        return new self(
            key: $a->key,
            kind: $a->kind,
            description: self::mergeDescriptions($a->description, $b->description),
            values: self::mergeValues($a->values, $b->values),
            required: $a->required || $b->required,
        );
    }

    private static function mergeValues(string $a, string $b): string
    {
        if ($a === '*' || $b === '*') {
            return '*';
        }

        $values = array_unique(array_filter(array_merge(
            explode(',', $a),
            explode(',', $b),
        ), static fn (string $value) => $value !== ''));

        return $values !== [] ? implode(',', $values) : '*';
    }

    private static function mergeDescriptions(string $a, string $b): string
    {
        if ($a === '' || $a === $b) {
            return $b;
        }

        if ($b === '') {
            return $a;
        }

        return $a.'; '.$b;
    }

    /**
     * Resolve a documented resource type down to the runtime JSON:API type slug,
     * matching how AllowsAppends/AllowsFields resolve model class-strings at runtime.
     */
    private static function resolveResourceType(string $type): string
    {
        if (class_exists($type) && is_subclass_of($type, Model::class)) {
            return Apiable::getResourceType($type);
        }

        return $type;
    }
}
