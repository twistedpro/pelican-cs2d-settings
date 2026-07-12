# Changelog

All notable changes to this project will be documented in this file.

## [1.0.2] - 2026-07-12

### Changed

- Synced the saved server name with the egg `SERVER_NAME` startup variable when it exists.
- Generalized startup variable syncing so config values and egg variables stay aligned.

## [1.0.1] - 2026-07-12

### Added

- Added `scripts/package-plugin.sh` for building `dist/cs2dsettings.zip` locally.
- Documented local packaging in the README.

### Changed

- Synced saved max players with the egg `SERVER_PLAYERS` startup variable when it exists.
- Moved the generated `map` command after server settings so `sv_maxplayers` is applied before map load.
- Updated the release workflow to use the local packaging script.
- Bumped the plugin version to `1.0.1`.

## [1.0.0] - 2026-07-12

### Added

- Added a CS2D settings page for Pelican server panels.
- Added map discovery from the server `maps` directory.
- Added loading and saving of managed `sys/server.cfg` settings.
- Added restart action for reloading saved CS2D settings.
- Added GitHub release packaging workflow.
- Added screenshots and usage documentation.
