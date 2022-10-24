# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
