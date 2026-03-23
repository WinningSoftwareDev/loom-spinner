# Changelog

All notable changes to this project will be documented in this file.

## [3.4.0] - 2026-03-23
### Added
- Added Mailcatcher container
- Provide support for HMR

## [3.3.0] - 2025-10-26
### Changed
- Improved output when running `spin:down` command.
- Now removes Nginx config and SSL certificates when destroying environments (if they exist).
- Now prompts the user to run the `env:hosts:clean` command if they want to remove host entries after running
  `spin:down`.
- Default Node version is now 25.
- Added PHP CS Fixer and refactored areas of the codebase for consistent code styles.
- Increased PHP Stan to max level for stricter static analysis and improved code quality.
- Added PHP CS Fixer to the CI workflow.

## [3.2.4] - 2025-10-25
### Fixed
- Force PHP containers to be recreated to ensure that the selected PHP version is used.

## [3.2.3] - 2025-10-23
### Changed
- No longer requires network when using `--disable-server` option.

## [3.2.2] - 2025-10-23
### Fixed
- Fix for Nginx proxy server attempting to start when using `--disable-server` option.

## [3.2.1] - 2025-10-23
### Changed
- No longer creating or attempting to start the Nginx proxy server when `--disable-server` option is used.

### Fixed
- Fix for `npm` and `node` commands no longer working inside the container.

## [3.2.0] - 2025-10-18
### Added
- Added new `loom env:hosts:clean` command to clean up host entries for non-existent environments.

### Changed
- Attempts to re-run `loom env:hosts:add` command with `sudo` if ran without.

## [3.1.1] - 2025-10-18
### Fixed
- Fix for host resolution issue when running multiple environments.
- Fix for issue #1

## [3.1.0] - 2025-10-16
### Changed
- Changed TLD to `.app` for cleaner, generic dev URLs.

## [3.0.0] - 2025-10-16
### Added
- Added a central proxy server and network to support pretty URLs.
- Projects now use `http://{project-name}.spinner` URLs.
- Added `env:hosts:add` command to facilitate new dev URLs.
- Added SSL support for users with `mkcert` installed.

### Removed
- No longer supports accessing projects via `http://localhost:{port}`.

## [2.0.0] - 2025-10-14
### Changed
- Implemented code standards tools (PHP Stan).

### Fixed
- Data now persists between updates.

## [1.5.2] - 2025-10-08
### Changed
- Changed package namespace to `cloudbase/loom-spinner`.

## [1.5.1] - 2025-10-07
### Added
- List environments command now displays the local database port your environments are using.

## [1.5.0] - 2025-10-07
### Added
- Colourise `user@hostname` inside container (when using `loom spin:attach` command).
- Set host display name inside container.

### Fixed
- Fix for commands wrapping inside the container.

## [1.4.4] - 2025-06-23
### Fixed
- No longer redirecting static assets to front controller.

## [1.4.3] - 2025-06-08
### Fixed
- Fixed issue with `env:list` command always using the same `.env` config.
- Now displays correct URL for each environment.

## [1.4.2] - 2025-06-06
### Added
- Added a project URL to the output of the `env:list` command.

## [1.4.1] - 2025-06-06
### Added
- Added `--version` command.

### Fixed
- Fixed `env:list` command output when no environments are configured.

## [1.4.0] - 2025-06-03
### Changed
- Replaced emojis in `env:list` command output with coloured text.

## [1.3.1] - 2025-05-21
### Changed
- Update package for re-release as `loomsoftware/loom-spinner`.

## [1.3.0] - 2025-05-18
### Added
- Added Attach Command: `spin:attach <name>`

## [1.2.2] - 2025-05-17
### Fixed
- Fixed warning outputs that are displayed when an environment does not use Nginx.

## [1.2.1] - 2025-05-17
### Added
- Added Server and Database information to the output of `env:list` command.

## [1.2.0] - 2025-05-17
### Added
- Added List Environments Command: `env:list`.
- Added dev dependency: `loomsoftware/badger`.

## [1.1.3] - 2025-05-04
### Fixed
- Fixed a typo causing XDebug installation to fail inside the PHP-FPM container.

## [1.1.2] - 2025-04-26
### Fixed
- Fixed a critical bug where environments would not build correctly if using a SQLite database.

## [1.1.1] - 2025-04-26
### Fixed
- Fixed a critical bug where environments could not be destroyed if using a MySQL database.
- Fixed a critical bug where PDO extensions were not installed in the container, causing MySQL driver errors.

## [1.1.0] - 2025-04-24
### Added
- New database option: MySQL.
- New configuration option: `options.environment.database.rootPassword`.

### Changed
- Changed the default database for new environments to MySQL (from SQLite).

## [1.0.4] - 2025-04-24
### Fixed
- Fixed a critical autoloading issue after the package is globally installed.

## [1.0.3] - 2025-04-24
### Fixed
- Fixed typo in `composer.json` description field.

## [1.0.2] - 2025-04-24
### Added
- Added information on `spin:down` command to README.

### Changed
- Minor documentation tweak to include a link to the Wiki on Packagist.

## [1.0.1] - 2025-04-24
### Changed
- Minor documentation tweak to make the header image display on Packagist.

## [1.0.0] – 2025-04-24
### Added
- Initial public release of **Loom Spinner CLI**.
- Command to spin up a new PHP development environment with Docker (`spin:up`).
- Commands to stop (`spin:stop`), start (`spin:start`), and destroy (`spin:down`) environments.
- Customizable environment configuration via CLI options or `spinner.yaml`.
- Support for PHP (default: 8.4), Nginx, SQLite3, and Node.js (default: 23).
- Xdebug support (enabled by default; can be disabled per environment).
- Automatic port assignment for services.
- Clear documentation for installation, configuration, usage, commands, and debugging.

---

**Note:**  
This is the project’s initial release. More features and database options are planned for future updates.
