# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.6.0 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.5 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#31](https://github.com/zendframework/zend-inputfilter/pull/31) updates the
  `InputFilterInterface::add()` docblock to match existing, shipped implementations.
- [#25](https://github.com/zendframework/zend-inputfilter/pull/25) updates the
  input filter to prevent validation of missing optional fields (a BC break
  since 2.3.9). This change likely requires changes to your inputs as follows:

  ```php
  $input = new Input();
  $input->setAllowEmpty(true);         // Disable BC Break logic related to treat `null` values as valid empty value instead *not set*.
  $input->setContinueIfEmpty(true);    // Disable BC Break logic related to treat `null` values as valid empty value instead *not set*.  
  $input->getValidatorChain()->attach(
      new Zend\Validator\NotEmpty(),
      true                             // break chain on failure
      
  );
  ```

  ```php
  $inputSpecification = [
    'allow_empty'       => true,
    'continue_if_empty' => true,
    'validators' => [
      [
        'break_chain_on_failure' => true,
        'name'                   => 'Zend\\Validator\\NotEmpty',
      ],
    ],
  ];
  ```

## 2.5.4 - 2015-08-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#15](https://github.com/zendframework/zend-inputfilter/pull/15) ensures that
  `ArrayAccess` data provided to an input filter using `setData()` can be
  validated, a scenario that broke with [#7](https://github.com/zendframework/zend-inputfilter/pull/7).

## 2.5.3 - 2015-08-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/zendframework/zend-inputfilter/pull/10) fixes an
  issue with with the combination of `required`, `allow_empty`, and presence of
  a fallback value on an input introduced in 2.4.5. Prior to the fix, the
  fallback value was no longer considered when the value was required but no
  value was provided; it now is.

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

## 2.4.7 - 2015-08-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#15](https://github.com/zendframework/zend-inputfilter/pull/15) ensures that
  `ArrayAccess` data provided to an input filter using `setData()` can be
  validated, a scenario that broke with [#7](https://github.com/zendframework/zend-inputfilter/pull/7).

## 2.4.6 - 2015-08-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/zendframework/zend-inputfilter/pull/10) fixes an
  issue with with the combination of `required`, `allow_empty`, and presence of
  a fallback value on an input introduced in 2.4.5. Prior to the fix, the
  fallback value was no longer considered when the value was required but no
  value was provided; it now is.

## 2.4.5 - 2015-07-28

### Added

- Nothing.

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
