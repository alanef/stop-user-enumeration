# Combined Verification Report: PLUGINS_SMALL-6

**Issue:** free_plugin_lib: setup notice can't be dismissed and never goes away (all free plugins)
**Date:** 2026-10-23
**Commit:** SUE e4e7020; free_plugin_lib v1.3.2 (9e94ffc)

## Executive Summary

| Review | Status |
|--------|--------|
| Commit Integrity | not-applicable (the issue claims no SHAs); change-set non-empty |
| Code Review | passed after one fix round: independent review found 1 critical, fixed in 1.3.2 |
| Acceptance Review | passed: 5/5 ticket fix items, PHPUnit for each |
| Browser-Test Gate | n-a: no layout change. The notice markup and redirects were checked over real HTTP in wp-env (curl, rendered HTML inspected) |
| **Overall** | **APPROVED WITH NOTES** |

## Where the code is

The fix is in alanef/free_plugin_lib (libraries/free_plugin_lib). SUE's change is the constraint
`^1.3.2` and changelog bullets. The code review covers the library diff v1.3.0..v1.3.2.

## Code Review Findings

An independent reviewer (a separate agent, not the author) reviewed v1.3.0..v1.3.1:
- **Critical, fixed in 1.3.2 (803d54e):** the redirect now also fired from 'rendering'. RSHFD passes `''` as
  its page, so every admin request, admin-ajax included, matched it and redirected to the opt-in screen until
  the user chose. Reproduced over HTTP (1.3.1: dashboard and heartbeat return 302; 1.3.2: both return 200).
  The redirect is now limited to admins, outside AJAX, on the plugin's own page, with a non-empty page.
- **Warning, fixed in 1.3.2:** CSCF, LHF, MMT and SSFGM pass a hook suffix as their page, so neither the
  redirect nor the settings-screen notice ever matched. The slug is now derived from either form.
- **Warning, documented:** on multi-plugin sites, plugins other than the last-loaded one never stored a choice
  under the static bug, so each asks once after upgrade. Recorded in the library CHANGELOG (Changed). No
  decision is inherited between plugins (consent is per plugin).
- **Warning, noted (out of scope):** the fix only applies on a site once the copy that loads first is
  >= 1.3.2, so all 8 plugins need the bump.
- **Suggestion, follow-up (out of scope):** `Email::$plugin_map` has no entry for SSFGM-Free, MMT-Free, LHF-Free
  or RSHFD-Free. In those plugins, "Allow & Continue" records optout without subscribing anyone. This predates 1.3.x.
- **Info:** esc_js in the attribute selector would mangle a `"` in a shortname. Shortnames are developer constants.

Security: the capability check and nonce still come before every state change. The new early-return guards change no state.

## Acceptance Review

| Ticket item | Result | Evidence |
|---|---|---|
| Dismiss persists despite sanitize_key lowercasing (keys unchanged) | pass | testDismissPersistsForUppercaseShortname, …Hyphenated…, wp-env AJAX round-trip with SUE |
| 'rendering' recovers | pass | testRenderingLeftWithoutChoiceShowsOptInScreenAgain; HTTP: SUE settings returns 302 to the opt-in screen, Skip sets optout, then 200 |
| Notice links to the opt-in screen, reworded | pass | testNoticeLinksToOptInScreen; rendered HTML in wp-env |
| Static shortname: prove, then fix | pass | Proved in wp-env with SUE+SWEGTS on the old library: both registered `ffpl-opt-in-SWEGTS`, SUE's status was never set, and SUE's notice carried SWEGTS. Fixed: own slugs, statuses and notices; testTwoPluginsKeepTheirOwnShortnames |
| Existing option and meta keys still readable | pass | testExistingDecisionsAndDismissalsAreStillRead |

Library suite: 28 tests, 61 assertions. SUE: 17 tests in wp-env, `composer run check` clean.

### Can the evidence fail? (Phase 2.5)
- The new tests run against v1.3.0 source: 7 of 12 fail. The 1.3.2 additions run against v1.3.1: 3 fail. A fourth (notice on a slug plugin's screen) passes on 1.3.1 by design, as a regression guard.
- One mutation per fix, each caught by the test aimed at it: case-sensitive compare (3 fail), no rendering recovery (1), notice linking to settings (1), dismiss mismatch answering with an error (2), no opt-in guard (1), shared shortname across instances (1), removing the optin write (1). The single-instance tests survive the shared-shortname mutation by design. They cannot see a second plugin; the two-plugin test exists for that.
- Positive controls on the same path: the bad-nonce dismiss test is paired with the passing dismiss; the ajax and non-admin no-redirect cases are paired with the admin redirect in the same test.

## Browser-Test Gate
n-a: there is no CSS or layout change, only text and a link. The rendered notice HTML and the redirect chain were checked over real HTTP in wp-env.

## Commit Range Covered

**Commit range covered:** library `84d3d1f..9e94ffc`; SUE `fcafa0a..e4e7020`

| Repo | SHA | Subject |
|------|-----|---------|
| free_plugin_lib | 84d3d1f | Fix opt-in notice that could not be dismissed (PLUGINS_SMALL-6) |
| free_plugin_lib | 8cdf4be | Release 1.3.1 |
| free_plugin_lib | 803d54e | Stop the opt-in redirect hijacking admin and AJAX requests (PLUGINS_SMALL-6) |
| free_plugin_lib | 9e94ffc | Release 1.3.2 |
| SUE | fcafa0a | Take free_plugin_lib 1.3.1: dismissable opt-in notice (PLUGINS_SMALL-6) |
| SUE | e4e7020 | Take free_plugin_lib 1.3.2 (PLUGINS_SMALL-6) |

These repos don't use conventional-commit scopes. Check with `git log --oneline v1.3.0..v1.3.2` (library)
and `git log --oneline v1.7.9..HEAD` (SUE). No out-of-range commits.

---
Verified by: AI Assistant
Date: 2026-10-23
