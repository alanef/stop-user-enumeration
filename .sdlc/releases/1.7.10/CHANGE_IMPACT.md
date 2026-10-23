# Change Impact Assessment: 1.7.10

## Risk Level: Low

### Factors Considered
- Number of issues: 2 (one is tooling with nothing shipped)
- Security-sensitive changes: 0 (no change to SUE's enumeration protection; library AJAX handlers keep their capability and nonce checks)
- API changes: no
- Database changes: no. The same option and user-meta keys are used.
- Breaking changes: no

## Affected Areas

### Code Areas
- Bundled `alanef/free_plugin_lib` (includes/vendor): the opt-in notice, dismiss handler, and redirect from SUE's settings page to the opt-in screen.
- No SUE plugin code changed.

### Configuration
- None.

### Dependencies
- alanef/free_plugin_lib 1.3.0 -> 1.3.2. The constraint is now ^1.3.2, which excludes the bad 1.3.1.

## Behaviour change users will notice
- Admins who left the opt-in screen without choosing are sent there again the next time they open SUE's settings, until they click Allow or Skip.
- On sites that run several Fullworks free plugins, the other plugins may each ask once. Under the old bug only one of them ever recorded a choice.
- The library class is shared by every plugin bundling it, and the first copy to load wins. On sites where another Fullworks free plugin with an older library loads before SUE (CSCF, FSS, LHF, MMT, RSHFD, SSFGM sort ahead of it), that older copy still runs until those plugins are updated too.

## Rollback Plan

### Immediate Rollback
```bash
git revert <Release 1.7.10>..e4e7020   # or re-release 1.7.9's tree as 1.7.11
```
On wp.org a rollback ships as a new version. Set the constraint back to ^1.3 (1.3.0 behaviour) only if 1.3.2 itself is at fault.

### Database Rollback
N/A

### Configuration Rollback
N/A

## Monitoring

Post-release, monitor:
- The wp.org support thread "settings-notice-wont-go-away" and new support topics mentioning redirects or the opt-in screen
- wp.org reviews for the 1.7.10 period
