# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.5.2 - 2015-07-28

### Added

- [#2](https://github.com/zendframework/zend-inputfilter/pull/2) adds support
  in `Zend\InputFilter\Factory` for using the composed `InputFilterManager` to
  retrieve an input of a given `type` based on configuration; only if the type
  is not available in the factory will it attempt to directly instantiate it.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#7](https://github.com/zendframework/zend-inputfilter/pull/7) fixes an issue
  with the combination of `required` and `allow_empty`, now properly
  invalidating a data set if the `required` input is missing entirely
  (previously, it would consider the data set valid, and auto-initialize the
  missing input to `null`).
