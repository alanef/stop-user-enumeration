# Tests Passed: 1.7.10

**Date:** 2026-10-23T06:48:08Z
**Commit:** 33c9e80bfb1f7038486b55ebcf3e32c3f4735766
**Tracked changes at run time:** 0

| Gate | Result |
|---|---|
| composer install | pass |
| composer validate --strict | pass |
| composer run check | pass |
| version consistency | pass |
| build (composer run build) | pass |
| Plugin Check | not run locally (no plugin-check script); CI runs it on the built zip |
| PHPUnit (npm test) | pass |

PHPUnit summaries:

    OK (17 tests, 47 assertions)

Full output: tests.log (not committed).
