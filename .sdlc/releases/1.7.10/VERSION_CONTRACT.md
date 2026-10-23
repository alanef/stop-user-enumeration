# Version Contract: 1.7.10

**Date:** 2026-10-23

## Canonical Source

Plugin header `Version:` in stop-user-enumeration/stop-user-enumeration.php, `Stable tag:` in
stop-user-enumeration/readme.txt, and `STOP_USER_ENUMERATION_PLUGIN_VERSION`. All are written and read back
by `scripts/release/bump-version.sh`. CI fails when they disagree.

## Expected State After Deploy

- main contains all version locations set to `1.7.10`, and `= 1.7.10 =` at the top of changelog.txt
- Git tag `v1.7.10` exists on main's HEAD; release.yml builds the zip and deploys to wp.org SVN (`Type: free`)
- wordpress.org/plugins/stop-user-enumeration reports 1.7.10 after the wp.org release confirmation
- The zip bundles alanef/free_plugin_lib 1.3.2

## Required Deploy Command

```
/tk-deploy live
```

Do not push tags outside `/tk-deploy live`. Its preconditions refuse on a dirty tree, a version mismatch,
missing or stale evidence, checks.yml not green on HEAD, or an existing tag.

## Post-Deploy Verification

See `.claude/commands/deploy-live.md`: SVN tag check, wp.org release confirmation, YouTrack update. Then post
the fix on https://wordpress.org/support/topic/settings-notice-wont-go-away/.
