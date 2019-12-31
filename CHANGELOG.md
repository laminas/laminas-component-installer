# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.1.2 - 2019-09-04

### Added

- [zendframework/zend-component-installer#57](https://github.com/zendframework/zend-component-installer/pull/57) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.1 - 2018-03-21

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-component-installer#54](https://github.com/zendframework/zend-component-installer/pull/54) fixes
  issues when run with symfony/console v4 releases.

## 2.1.0 - 2018-02-08

### Added

- [zendframework/zend-component-installer#52](https://github.com/zendframework/zend-component-installer/pull/52) adds
  the ability to whitelist packages exposing config providers and/or modules.
  When whitelisted, the installer will not prompt to inject configuration, but
  instead do it automatically. This is done at the root package level, using the
  following configuration:

  ```json
  "extra": {
    "laminas": {
      "component-whitelist": [
        "some/package"
      ]
    }
  }
  ```

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.0 - 2018-02-06

### Added

- Nothing.

### Changed

- [zendframework/zend-component-installer#49](https://github.com/zendframework/zend-component-installer/pull/49)
  modifies the default options for installer prompts. If providers and/or
  modules are discovered, the installer uses the first discovered as the default
  option, instead of the "Do not inject" option. Additionally, the "remember
  this selection" prompt now defaults to "y" instead of "n".

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-component-installer#50](https://github.com/zendframework/zend-component-installer/pull/50)
  removes support for PHP versions 5.6 and 7.0.

### Fixed

- Nothing.

## 1.1.1 - 2018-01-11

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-component-installer#47](https://github.com/zendframework/zend-component-installer/pull/47) fixes
  an issue during package removal when a package defines multiple targets (e.g.,
  both "component" and "config-provider") and a `ConfigInjectorChain` is thus
  used by the plugin; previously, an error was raised due to an attempt to call
  a method the `ConfigInjectorChain` does not define.

## 1.1.0 - 2017-11-06

### Added

- [zendframework/zend-component-installer#42](https://github.com/zendframework/zend-component-installer/pull/42)
  adds support for PHP 7.2.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-component-installer#42](https://github.com/zendframework/zend-component-installer/pull/42)
  removes support for HHVM.

### Fixed

- [zendframework/zend-component-installer#40](https://github.com/zendframework/zend-component-installer/pull/40) and
  [zendframework/zend-component-installer#44](https://github.com/zendframework/zend-component-installer/pull/44) fix
  an issue whereby packages that define an array of paths for a PSR-0 or PSR-4
  autoloader would cause the installer to error. The installer now properly
  handles these situations.

## 1.0.0 - 2017-04-25

First stable release.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.7.1 - 2017-04-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-component-installer#38](https://github.com/zendframework/zend-component-installer/pull/38) fixes
  an issue with detection of config providers in `ConfigAggregator`-based
  configuration files. Previously, entries that were globally qualified
  (prefixed with `\\`) were not properly detected, leading to the installer
  re-asking to inject.

## 0.7.0 - 2017-02-22

### Added

- [zendframework/zend-component-installer#34](https://github.com/zendframework/zend-component-installer/pull/34) adds
  support for applications using [laminas/laminas-config-aggregator](https://github.com/zendframework/zend-config-aggregator).

### Changes

- [zendframework/zend-component-installer#34](https://github.com/zendframework/zend-component-installer/pull/34)
  updates the internal architecture such that the Composer `IOInterface` no
  longer needs to be passed during config discovery or injection; instead,
  try/catch blocks are used within code exercising these classes, which already
  composes `IOInterface` instances. As such, a number of public methods that
  were receiving `IOInterface` instances now remove that argument. If you were
  extending any of these classes, you will need to update accordingly.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.6.0 - 2017-01-09

### Added

- [zendframework/zend-component-installer#31](https://github.com/zendframework/zend-component-installer/pull/31) adds
  support for [laminas-config-aggregator](https://github.com/zendframework/zend-config-aggregator)-based
  application configuration.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.1 - 2016-12-20

### Added

- Nothing.

### Changes

- [zendframework/zend-component-installer#29](https://github.com/zendframework/zend-component-installer/pull/29)
  updates the composer/composer dependency to `^1.2.2`, and, internally, uses
  `Composer\Installer\PackageEvent` instead of the deprecated
  `Composer\Script\PackageEvent`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.0 - 2016-10-17

### Added

- [zendframework/zend-component-installer#24](https://github.com/zendframework/zend-component-installer/pull/24) adds
  a new method to the `InjectorInterface`: `setModuleDependencies(array $modules)`.
  This method is used in the `ComponentInstaller` when module dependencies are
  discovered, and by the injectors to provide dependency order during
  configuration injection.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-component-installer#22](https://github.com/zendframework/zend-component-installer/pull/22) and
  [zendframework/zend-component-installer#25](https://github.com/zendframework/zend-component-installer/pull/25) fix
  a bug whereby escaped namespace separators caused detection of a module in
  existing configuration to produce a false negative.
- [zendframework/zend-component-installer#24](https://github.com/zendframework/zend-component-installer/pull/24) fixes
  an issue resulting from the additions from [zendframework/zend-component-installer#20](https://github.com/zendframework/zend-component-installer/pull/20)
  for detecting module dependencies. Since autoloading may not be setup yet, the
  previous approach could cause failures during installation. The patch provided
  in this version introduces a static analysis approach to prevent autoloading
  issues.

## 0.4.0 - 2016-10-11

### Added

- [zendframework/zend-component-installer#12](https://github.com/zendframework/zend-component-installer/pull/12) adds
  a `DiscoveryChain`, for allowing discovery to use multiple discovery sources
  to answer the question of whether or not the application can inject
  configuration for the module or component. The stated use is for injection
  into development configuration.
- [zendframework/zend-component-installer#12](https://github.com/zendframework/zend-component-installer/pull/12) adds
  a `ConfigInjectorChain`, which allows injecting a module or component into
  multiple configuration sources. The stated use is for injection into
  development configuration.
- [zendframework/zend-component-installer#16](https://github.com/zendframework/zend-component-installer/pull/16) adds
  support for defining both a module and a component in the same package,
  ensuring that they are both injected, and at the appropriate positions in the
  module list.
- [zendframework/zend-component-installer#20](https://github.com/zendframework/zend-component-installer/pull/20) adds
  support for modules that define `getModuleDependencies()`. When such a module
  is encountered, the installer will now also inject entries for these modules
  into the application module list, such that they *always* appear before the
  current module. This change ensures that dependencies are loaded in the
  correct order.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.3.1 - 2016-09-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-component-installer#15](https://github.com/zendframework/zend-component-installer/pull/15) fixes
  how modules are injected into configuration, ensuring they go (as documented)
  to the bottom of the module list, and not to the top.

## 0.3.0 - 2016-06-27

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-component-installer#4](https://github.com/zendframework/zend-component-installer/pull/4) removes
  support for PHP 5.5.

### Fixed

- [zendframework/zend-component-installer#8](https://github.com/zendframework/zend-component-installer/pull/8) fixes
  how the `DevelopmentConfig` discovery and injection works. Formerly, these
  were looking for the `development.config.php` file; however, this was
  incorrect. laminas-development-mode has `development.config.php.dist` checked into
  the repository, but specifically excludes `development.config.php` from it in
  order to allow toggling it from the `.dist` file. The code now correctly does
  this.

## 0.2.0 - 2016-06-02

### Added

- [zendframework/zend-component-installer#5](https://github.com/zendframework/zend-component-installer/pull/5) adds
  support for arrays of components/modules/config-providers, in the format:

  ```json
  {
    "extra": {
      "laminas": {
        "component": [
          "Some\\Component",
          "Other\\Component"
        ]
      }
    }
  }
  ```

  This feature should primarily be used for metapackages, or config-providers
  where some configuration might not be required, and which could then be split
  into multiple providers.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

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
