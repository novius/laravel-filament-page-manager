---
name: package-release
description: "Use this skill when preparing Laravel package releases: CHANGELOG.md updates, generated release notes, GitHub release workflows, version checks, tags, release validation, or release automation changes. Never publish autonomously."
license: MIT
metadata:
  author: laravel
---

# Package Release

## Primary Goal

Prepare a safe package release checklist and implementation without tagging, pushing, or publishing unless the user explicitly approves that action.

## Workflow

1. Review `CHANGELOG.md`, the `release` job of `.github/workflows/tests.yml`, open diff, and pending package changes.
2. Validate the release state with `composer test` before recommending a release.
3. Confirm whether version metadata needs to change; the package relies on Git tags rather than a hardcoded package version.
4. Release by bumping `CHANGELOG.md` (see README, "Publishing a release"): rename `## [Unreleased]` to `## [x.y.z](compare link) - YYYY-MM-DD`, reopen an empty `## [Unreleased]` above it, and push to `main`. The `release` job of the `tests` workflow then creates the GitHub release and its tag from the first `## [x.y.z]` heading, with that section as release notes, once all tests pass. It only runs on `main` and does nothing when the tag already exists.
5. Do not push a version bump to `main`, tag, or publish without explicit user approval: pushing the bump publishes the release.

## References

- `CHANGELOG.md`
- `.github/workflows/tests.yml`
- `composer.json`

## Examples

- Prepare a release by checking changelog coverage, running `composer test`, and bumping the `CHANGELOG.md` heading, then ask for approval before pushing to `main`.

## Anti-Patterns

- Creating tags, pushing branches, or publishing releases without explicit approval.
- Creating the tag or the GitHub release by hand: the `release` job creates them from `CHANGELOG.md`.
- Skipping `composer test` before a release recommendation.
- Writing release notes in the GitHub release instead of `CHANGELOG.md`, which is their only source.
- Changing release workflows without checking the supported matrix in `.github/workflows/tests.yml`.
