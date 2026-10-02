# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The comment-author script now loads only on pages that show a comment form, instead of
  on every front-end page.
- The internal action that adds the settings page meta boxes is renamed from
  `settings_page_stop-user-enumeration_settings_page_boxes` to
  `stop_user_enumeration_settings_page_boxes`, so it carries the plugin prefix.

## [1.7.9] - 2026-10-02

### Fixed

- Two database writes on every page load: the plugin and its bundled opt-in library both
  registered an uninstall hook for the same plugin file and overwrote each other's entry in
  the `uninstall_plugins` option. Uninstall now runs from `uninstall.php` instead.
- Plugin settings were left in the database after the plugin was deleted, because only the
  library's uninstall callback ever ran.

### Changed

- Updated opt-in library to 1.3.0 (requires `^1.3`). It no longer registers its own uninstall
  hook, and drops the anti-spam advert from the settings page.

## [1.7.8]

- Baseline. Earlier history is in `stop-user-enumeration/readme.txt`.
