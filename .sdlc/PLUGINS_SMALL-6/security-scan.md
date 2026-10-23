# Security Scan Report: PLUGINS_SMALL-6

**Date:** 2026-10-23
**Scanned by:** AI Assistant
**Files analyzed:** 4 (free_plugin_lib src/Main.php, src/Classes/Email.php, src/Assets/scripts/main.js; SUE composer.json)

## Commit Range Covered

**Commit range covered:** free_plugin_lib `84d3d1f..9e94ffc`; SUE `fcafa0a..e4e7020` (the same table as verification.md). No out-of-range commits.

## Summary

| Severity | Count |
|----------|-------|
| Critical | 0 |
| High | 0 |
| Medium | 0 |
| Low | 1 (pre-existing) |
| Info | 1 |

**Recommendation:** PASS_WITH_NOTES

## Findings

### Low
1. **A01 (pre-existing):** `src/Main.php` handle_skip_optin has no capability check. The per-user nonce makes it non-exploitable. Add the check next time the library is touched.

### Info
1. **A03:** esc_js in an attribute selector. Shortnames are developer constants, so it doesn't matter in practice.

## Instrument validation
- **Input-sanitisation sweep:** validated against 958c57d^, the last fix of that class. It flags all 3 raw request reads there and none in v1.3.2.
- **Guard-before-state-change:** **UNVALIDATED.** No earlier fix of this class exists to test the sweep against. Checked by manual review instead: the capability check and nonce precede both AJAX handlers' writes, and the new early returns change no state.

## Checklist Status
- [x] OWASP Top 10 reviewed (A01, A03 relevant; others n/a: no crypto, auth, SSRF, or deserialisation changes)
- [x] Secret exposure: none
- [x] Input validation: all request input goes through sanitize_key/sanitize_text_field/sanitize_email with wp_unslash
- [x] Dependencies: only free_plugin_lib 1.3.2 (own package); check-dependencies.sh runs in /tk-release
- [x] Auth/authz: unchanged, plus a new manage_options check on the redirect
- [x] Data protection: no change to what is sent on opt-in
