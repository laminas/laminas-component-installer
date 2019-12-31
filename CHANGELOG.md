# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.1.0 - TBD

First tagged release.

Previously, PHAR releases were created from each push to the master branch.
Starting in 0.1.0, the architecture changes to implement a
[composer plugin](https://getcomposer.org/doc/articles/plugins.md). As such,
tagged releases now make more sense, as plugins are installed via composer
(either per-project or globally).

### Added

- [zendframework/zend-component-installer#2](https://github.com/zendframework/zend-component-installer/pull/2) adds:
  - All classes in the `Laminas\ComponentInstaller\ConfigDiscovery` namespace.
    These are used to determine which configuration files are present and
    injectable in the project.
  - All classes in the `Laminas\ComponentInstaller\Injector` namespace. These are
    used to perform the work of injecting and removing values from configuration
    files.
  - `Laminas\ComponentInstaller\ConfigOption`, a value object mapping prompt text
    to its related injector.
  - `Laminas\ComponentInstaller\ConfigDiscovery`, a class that loops over known
    configuration discovery types to return a list of `ConfigOption` instances

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-component-installer#2](https://github.com/zendframework/zend-component-installer/pull/2) removes
  all classes in the `Laminas\ComponentInstaller\Command` namespace.
- [zendframework/zend-component-installer#2](https://github.com/zendframework/zend-component-installer/pull/2) removes
  the various `bin/` scripts.
- [zendframework/zend-component-installer#2](https://github.com/zendframework/zend-component-installer/pull/2) removes
  the PHAR distribution.

### Fixed

- [zendframework/zend-component-installer#2](https://github.com/zendframework/zend-component-installer/pull/2) updates
  `Laminas\ComponentInstaller\ComponentInstaller`:
  - to act as a Composer plugin.
  - to add awareness of additional configuration locations:
    - `modules.config.php` (Laminas API Tools)
    - `development.config.php` (laminas-development-mode)
    - `config.php` (Mezzio with mezzio-config-manager)
  - to discover and prompt for known configuration locations when installing a
    package.
  - to allow re-using a configuration selection for remaining packages in the
    current install session.
