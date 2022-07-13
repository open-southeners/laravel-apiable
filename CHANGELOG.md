# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
