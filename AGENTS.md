# AGENTS.md

## Language

English only, always.

- Write all code, code comments, docblocks, commit messages, and documentation in English.
- Translatable user-facing strings are written in English and shipped through the plugin text domain `sf-icon-manager`. Translations live in `languages/` (`sf-icon-manager.pot`, `sf-icon-manager-*.po`, compiled `.mo` and `.json` files).

## WP-CLI

Use `ddev wp` for all WP-CLI commands, never a global `wp` binary. Translation tasks (`.pot`, `.po`, `.mo`, `.json` generation) must be done with the `wp i18n` commands via `ddev wp` (`ddev wp i18n make-pot`, `ddev wp i18n make-mo`, `ddev wp i18n make-json`), following `docs/translations.md`.

## Process

When something is ambiguous or a decision could go several ways, ask the user first instead of assuming. Do not act on assumptions about how they want things done.

## Release notes

Keep both changelogs in sync with every release:

- `CHANGELOG.md` (Keep a Changelog format, full detail).
- The `== Changelog ==` section in `readme.txt` (wp.org readme format, `= X.Y.Z =` entries). This is what wp.org shows in the plugin's "Changelog" tab, so it must always be up to date — never older than `CHANGELOG.md`.