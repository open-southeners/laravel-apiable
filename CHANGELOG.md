# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
