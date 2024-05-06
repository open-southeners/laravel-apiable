# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.13.3] - 2024-05-06

### Fixed

- Raw response without pagination returning query builder

## [3.13.2] - 2024-05-06

### Added

- Raw response formatting to `JsonApiResponse`

## [3.13.1] - 2023-12-31

### Added

- Type generics into:
    - `OpenSoutheners\LaravelApiable\Http\JsonApiResponse`
    - `OpenSoutheners\LaravelApiable\Http\RequestQueryObject`
    - `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiResource`
    - `OpenSoutheners\LaravelApiable\Http\Resources\JsonApiCollection`
    - `OpenSoutheners\LaravelApiable\Contracts\ViewQueryable`
    - `OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder`

## [3.13.0] - 2023-12-30

### Added

- `Handler::withHeader` (used as `apiable()->jsonApiRenderable()->withHeader`) to add headers to JSON errors

## [3.12.0] - 2023-12-14

### Added

- Includes relationships counts with `tags_count` (needs to end with `_count`). E.g:

```php
JsonApiResponse::using(Post::class)->allowInclude(['tags_count']);

// Then request this with ?include=tags_count
```

### Fixed

- Some IDEs getting confused with returning `self` types in traits (replaced to `static`)

## [3.11.5] - 2023-10-24

### Changed

- Fix `open-southeners/laravel-helpers` utilities package version constrain so other packages can use similar versions under the same major release

## [3.11.4] - 2023-10-11

### Fixed

- Query `withCount` method usage with apiable is replacing the underlying added selects for these counts

## [3.11.3] - 2023-09-22

### Fixed

- Force formatting always coming through even thought is not forced by config

## [3.11.2] - 2023-09-19

### Changed

- Config file now with `response.formatting` with `type` and `force` sub-items as options

### Fixed

- Force formatting for Inertia based apps (now can use `Apiable::forceResponseFormatting()`)

## [3.11.1] - 2023-09-19

### Fixed

- Non existing class `ForceAppendAttribute` when reading controller attributes

## [3.11.0] - 2023-09-19

### Added

- Default sorts using `JsonApiResponse::applyDefaultSort` method or `OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultSort` attribute
- Default filters using `JsonApiResponse::applyDefaultFilter` method or `OpenSoutheners\LaravelApiable\Attributes\ApplyDefaultFilter` attribute
- Default formatting bypass using `JsonApiResponse::forceFormatting` method 

### Changed

- `Request::wantsJsonApi()` only looks at `Accept` header, not looking for `Content-Type` one anymore
- Now all requests can send a different `Accept` header if they want a diffent response formatting. For e.g. sending `Accept: application/json` will get raw JSON response. Default being configured in the `config/apiable.php` **make sure to publish or update it**

## [3.10.0] - 2023-09-15

### Added

- Sorts now accepts relationships (for e.g. `/posts?sort=tags.created_at`)

## [3.9.2] - 2023-08-18

### Fixed

- PHP deprecation warning on string vars interpolation

## [3.9.1] - 2023-08-15

### Fixed

- `JsonApiResponse` using viewable builder (query scopes to filter by view permission) didn't send any user as first parameter

## [3.9.0] - 2023-08-08

### Added

- `JsonApiResponse::paginateUsing(fn ($query) => $query->simplePaginate())` method to customise pagination used in JSON API responses.

## [3.8.0] - 2023-08-07

### Changed

- Pivot attributes now aren't configurable through `config/apiable.php`, they will always be applied as long as there are any under the relationship. They can be found under `relationships.data.*.meta` and keyed following the same as before: `pivot_name_pivot_attribute` (`pivot_name` being customisable through: https://laravel.com/docs/10.x/eloquent-relationships#customizing-the-pivot-attribute-name)

## [3.7.1] - 2023-08-07

### Fixed

- Missing import to make work pivot attributes functionality

## [3.7.0] - 2023-08-07

### Added

- `include_pivot_attributes` config option to optionally add pivot attributes to the relationships data (default to false to match previous functionality). Pivot data will be added as `pivot_name_pivot_attribute` (remember if you're using `as` it will use this one: https://laravel.com/docs/10.x/eloquent-relationships#customizing-the-pivot-attribute-name).

## [3.6.1] - 2023-03-29

### Fixed

- Added status code (401) to unauthorised responses

## [3.6.0] - 2023-03-23

### Fixed

- Inconsistent behaviour with `Apiable::toJsonApi()` returning false sometimes, now returns an API resource/collection all the time (**might be a breakchange therefore this was minor release**)

## [3.5.2] - 2023-03-23

### Fixed

- `JsonApiResponse::conditionallyLoadResults()` method doesn't work as expected

## [3.5.1] - 2023-03-22

### Fixed

- Exceptions handler using `apiable()->jsonApiRenderable()` now returns thrown exception headers

## [3.5.0] - 2023-03-21

### Added

- Backend now returns properly the `Content-Type=application/vnd.api+json` response header

## [3.4.2] - 2023-03-03

### Fixed

- Filters with negative values like `filter[attr]=0` won't be filtered out

## [3.4.1] - 2023-02-28

### Fixed

- `Apiable::jsonApiRenderable` method, Handler missing import of Symfony's HttpException

## [3.4.0] - 2023-02-28

### Changed

- `Apiable::jsonApiRenderable` now accepts 2 arguments: Throwable exception & second a bool nullable withTrace, error handling redesign to properly match JSON:API & Laravel.

## [3.3.1] - 2023-02-15

### Added

- `JsonApiResponse::conditionallyLoadResults` to manage adding viewable query or not to the response 

## [3.3.0] - 2023-02-14

### Added

- Laravel 10 support
- PHP native types

### Removed

- `QueryParametersValidator` constants `ENFORCE_VALIDATION_STRATEGY` & `FILTER_VALIDS_ONLY_STRATEGY`, now strategy parameter is changed to a boolean instead

### Fixed

- Types with static analysis

## [3.2.8] - 2023-02-13

### Fixed

- Error when wrong typed parameter sent to `IteratesResultsAfterQuery::appendToApiResource`

## [3.2.7] - 2023-02-07

### Fixed

- Filtering when wrong operator is present was returning exception, now ignores the operator (e.g. `filter[attribute][0]=1&filter[attribute][1]=2`)

## [3.2.6] - 2023-01-30

### Fixed

- Deprecation notice on `explode` second argument

## [3.2.5] - 2023-01-26

### Added

- `forceAppendWhen` method to `JsonApiResponse`

## [3.2.4] - 2023-01-26

### Fixed

- Fix PHP 8 deprecation with string variable interpolation

## [3.2.3] - 2023-01-25

### Fixed

- Fix issue reporting wrong status code when setup handler renderable receives a query exception: `apiable()->jsonApiRenderable()`

## [3.2.2] - 2022-12-23

### Fixed

- Filters values like: `filter[attribute]=0` now doesn't get removed

## [3.2.1] - 2022-12-21

### Fixed

- PHP 8.2 deprecation warnings

## [3.2.0] - 2022-12-13

### Added

- PHP 8.2 support

## [3.1.3] - 2022-12-07

### Fixed

- Fixed OR filters with rest of filtering or SQL query affecting negatively the results

## [3.1.2] - 2022-12-05

### Fixed

- Fixed `page[size]` in request doesn't get the number of results when lower than model's `$perPage` (only on higher)

## [3.1.1] - 2022-12-02

### Fixed

- Filtering scopes were not being applied

## [3.1.0] - 2022-12-01

### Added

- Search (Laravel Scout) filters with allowed attributes and/or value(s): `?q=hello&q[filter][attribute]=foo` or `?search=hello&search[filter][attribute]=foo`

## [3.0.1] - 2022-11-23

### Fixed

- Empty filter without attribute: `?filter=test` was throwing an error

## [3.0.0] - 2022-11-04

### Fixed

- Remove page parameter from pagination links when using `withQuery`

### Changed

- Filter relationship without any value applies relationship existence filtering, now doesn't
- Filtering now handles AND/OR conditions properly
- Filtering using OR condition (`filter[status]=Active,Inactive`) will be fully invalidated if one member doesn't pass the validation
- `RequestQueryObject::filters` method now implements own logic instead of reusing Symfony's request one to be able to get AND filters with same keys (attributes) on a different array item

### Removed

- `withWhereHas` under filtering, now all related data is loaded (until reimplemented with AND/OR conditionals...)

## [2.3.4] - 2023-01-25

### Fixed

- Fix issue reporting wrong status code when setup handler renderable receives a query exception: `apiable()->jsonApiRenderable()`

## [2.3.3] - 2022-11-01

### Fixed

- Add request query parameters to pagination links on `JsonApiResponse` resource collections

## [2.3.2] - 2022-10-31

### Fixed

- Ambiguous columns when filtering on relationships attributes (redoing it right without any regression)

## [2.3.1] - 2022-10-26

### Fixed

- Ambiguous columns when filtering on relationships attributes

## [2.3.0] - 2022-10-24

### Added

- Conditionally filter out attributes that ends with "_id" (adding config option `responses.include_ids_on_attributes`)
- `JsonApiResponse::includingIdAttributes()` method for setting `responses.include_ids_on_attributes` option value within an individual response

## [2.2.3] - 2022-10-20

### Fixed

- Fix `resolveFromRoute` when using invokable controller `JsonApiResponse`

## [2.2.2] - 2022-10-20

### Fixed

- `JsonApiResponse::toArray` returning wrong formatted JSON:API array

## [2.2.1] - 2022-10-19

### Fixed

- `JsonApiResponse::forceAppend` method fails getting model class when from query builder

## [2.2.0] - 2022-10-18

### Added

- `requests.validate_params` config option to `apiable.php` for enforcing validation. **Requires to publish or manually copy** [#7]
- Additional filter operators like `gte` (greater or equal than), `gt` (greater than), `lte` (lower or equal than), `lt` (lower than) [#5]

### Changed

- Get allowed fields methods from apply query params pipeline classes moved to `OpenSoutheners\LaravelApiable\Http\QueryParamsValidator`
- Operators from `allowed_filters` included to meta data are now URL-safe
- `allowed_sorts` structure is now based on `['attribute' => 'allowed_direction']`
- Allow filters methods/attribute now accepts array with multiple operators

## [2.1.1] - 2022-10-06

### Added

- `Illuminate\Contracts\Support\Arrayable` interface to `JsonApiResponse` so it can be used in Inertia responses (as an example)

## [2.1.0] - 2022-10-05

### Added

- `OpenSoutheners\LaravelApiable\Contracts\ViewQueryable` and `OpenSoutheners\LaravelApiable\Contracts\ViewableBuilder` classes for models & [custom query builders](https://martinjoo.dev/build-your-own-laravel-query-builders) now functional with `JsonApiResponse` (can be disabled by config see `config/apiable.php`)

## [2.0.1] - 2022-09-29

⚠️ **Please make sure to run `php artisan vendor:publish --force --provider="OpenSoutheners\LaravelApiable\ServiceProvider"`** ⚠️

### Fixed

- Getting wrong config keys

## [2.0.0] - 2022-09-29

⚠️ **Please make sure to run `php artisan vendor:publish --force --provider="OpenSoutheners\LaravelApiable\ServiceProvider"`** ⚠️

### Removed

- `JsonApiResponse::list` and `JsonApiResponse::getOne` methods as **isn't the responsibility of this package** to act as query repository so **it makes easier to encapsulate it** into repository or whatever design pattern you're using. **Use `JsonApiResponse::using` instead sending the query as its parameter**.

### Added

- PHP 8 attributes for QueryParams:
  - `OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam`
  - `OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam`
  - `OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam`
  - `OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam`
  - `OpenSoutheners\LaravelApiable\Attributes\SortQueryParam`
  - `OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam`
- Method `JsonApiResponse::from` can now be non-statically called as `JsonApiResponse::using` (for dependency injection usage in controllers, etc)

### Changed

- Config file changes. **Please make sure to run `php artisan vendor:publish --force --provider="OpenSoutheners\LaravelApiable\ServiceProvider"`**
- Constructor from `JsonApiResponse` now only accepts 1 optional parameter (being the query parameter removed in favor of manually setting this by calling `JsonApiResponse::using` method)
- Constructor from `RequestQueryObject` now only accepts 1 optional parameter (being the query parameter removed in favor of manually setting this by calling `RequestQueryObject::setQuery` method)

## [1.3.2] - 2022-09-21

### Added

- `JsonApiResponse:forceAppend` method to force appends to the final response

## [1.3.1] - 2022-09-20

### Changed

- `JsonApiResponse::getPipelineQuery` now accepts an optional callback closure & is exposed as public for repositories usage

## [1.3.0] - 2022-09-14

### Changed

- `RequestQueryObject::allowFilter` method now accepts 3 arguments (attribute, operator/values, values)

### Fixed

- `AllowedFilter::scopedValue` method was removed and accidentally pushed onto `RequestQueryObject::allowScopedFilter`

## [1.2.0] - 2022-09-13

### Added

- Method `RequestQueryObject::allows` to be able to group everything in the same method call without needing to use class methods (`AllowedFilters`, `AllowedFields`, etc...)

## [1.1.0] - 2022-09-12

### Added

- `AllowedFilter::scoped` method for Laravel query scopes filters to specify the filter is not an actual attribute but a query builder scope
- `enforce_scoped_names` to `config/apiable.php` to be used so they rename scoped filters in case there are attributes with the same name on the model **(remember to use `vendor:publish` artisan command to update the config file)**
- `include_allowed` to `config/apiable.php` to be used so any `JsonApiResponse` will include allowed filters and sorts (like using `JsonApiResponse::includeAllowedToResponse` but on all requests)
- `AssertableJsonApi::hasNotAttribute` and `AssertableJsonApi::hasNotAttributes` methods for negative test assertions (counter part of `AssertableJsonApi::hasAttribute` and `AssertableJsonApi::hasAttributes`)

### Fixed

- Scoped filters now can be allowed & applied to requested response
- Issue with `allowAppends` & `allowFields` sending array of attributes will wrongly parse them

## [1.0.2] - 2022-09-01

### Added

- Allowing fields and appends now accepts model class as for type parameter

## [1.0.1] - 2022-08-31

### Fixed

- Missing publishable config

## [1.0.0] - 2022-08-31

### Removed

- Custom transformers (out of package scope / purpose)

### Fixed

- Method `allowIncludes` adds nested array which leads into issues

### Changed

- Model setup not needed (`OpenSoutheners\LaravelApiable\JsonApiOptions` replaced by `OpenSoutheners\LaravelApiable\Facades\Apiable::modelResourceTypeMap` facade method)

### Added

- Support for [laravel/scout](https://github.com/laravel/scout)
- Support for [hammerstone/fast-paginate](https://github.com/hammerstonedev/fast-paginate)

## [0.4.1] - 2022-07-18

### Fixed

- Missing autoload-dev, tests were autoloaded with the released version
- Multiple minor fixes around forwarding calls (methods)
- Apiable facade `toJsonApi`

## [0.4.0] - 2022-07-13

### Added

- Way to add multiple sorts and includes with `allowSort` & `allowInclude` methods

## [0.3.0] - 2022-07-13

### Added

- JsonApiResponse `getOne` method for parse current model instance or model key passed
- `isCollection` and `isResource` testing methods to `AssertableJsonApi`

## [0.2.0] - 2022-07-12

### Fixed

- Multiple fixes to tests & package

### Changed

- Appends now needs to be sent as `?appends[type]=my_attribute,another_attribute` as they're completely different from fields

### Removed

- `JsonApiResource::withRelations()` method (bad idea, lots of possible N+1 problems to the dev-user)

## [0.1.0] - 2022-07-12

### Added

- Initial pre-release! 
