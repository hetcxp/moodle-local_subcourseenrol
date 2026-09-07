# Contributing to local_subcourseenrol

## Development Setup

This plugin requires:
- Moodle 4.5+ (2024100700)
- PHP 8.1+
- mod_subcourse >= 2025032001

Install the plugin in your Moodle instance under `local/subcourseenrol`.

## Running Tests

### PHPUnit
From the Moodle root directory:
```bash
vendor/bin/phpunit local/subcourseenrol/tests/observer_test.php
vendor/bin/phpunit local/subcourseenrol/tests/event_test.php
# Or full suite (if phpunit.xml is configured):
vendor/bin/phpunit --testsuite local_subcourseenrol_testsuite
```

### Behat
```bash
php admin/tool/behat/cli/run.php --tags=@local_subcourseenrol
```

### Linting (MDLCS)
```bash
vendor/bin/phpcs --standard=moodle classes/ tests/ db/ lang/ settings.php version.php
```

## Commit Convention

This project uses [Conventional Commits](https://www.conventionalcommits.org/) with **lowercase types**:

```
feat: add new feature
fix: correct a bug
refactor: restructure code without behavior change
test: add or update tests
chore: maintenance tasks (version bump, gitignore, etc.)
docs: documentation changes
style: formatting only (PHPDoc, whitespace)
```

> Note: commit `a9fee33` in the history uses `Refactor:` (capitalized) — this is a historical exception.

## Release Process

1. Determine new version following SemVer:
   - `MAJOR`: breaking API change
   - `MINOR`: new feature, backward-compatible (includes logic bug fixes)
   - `PATCH`: backward-compatible bug fixes only
2. Update `version.php`:
   - `$plugin->version` → Moodle version format `YYYYMMDDNN`
   - `$plugin->release` → SemVer string
3. Update `CHANGELOG.md` — move Unreleased items to new version section
4. Commit: `git commit -m "chore: bump version to X.Y.Z (YYYYMMDDNN)"`
5. Tag: `git tag -a vX.Y.Z HEAD -m "Release vX.Y.Z (YYYYMMDDNN)"`
6. Push: `git push origin main --tags`

## Updating .llm_build/index.md

After modifying class signatures, methods, or functions, regenerate the AST index:
```bash
python3 ~/Documents/scripts/extract_schemas.py --src . --out .llm_build/index.md
git add .llm_build/index.md
git commit -m "chore: update .llm_build/index.md"
```
