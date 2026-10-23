# Dependency Audit: 1.7.10

**Date:** 2026-10-23T06:46:52Z
**Commit:** e4e7020

| Tree | Policy | Exit | Result |
|---|---|---|---|
| plugin composer (no dev) | block | 0 | clean |
| root composer (tooling) | warn | 0 | clean |
| npm (dev env / build) | warn | 1 | advisories |

## plugin composer (no dev)
```
No security vulnerability advisories found.
```
## root composer
```
No security vulnerability advisories found.
```
## npm
```
### repository root (exit 1)
fix available via `npm audit fix`
node_modules/minimatch

simple-git  <=4.0.0
Severity: critical
simple-git Affected by Command Execution via Option-Parsing Bypass - https://github.com/advisories/GHSA-jcxm-m3jx-f287
simple-git has blockUnsafeOperationsPlugin bypass via case-insensitive protocol.allow config key enables RCE - https://github.com/advisories/GHSA-r275-fr43-pm7q
simple-git is vulnerable to Remote Code Execution - https://github.com/advisories/GHSA-hffm-xvc3-vprc
simple-git unsafe-operation guard does not block trailer command configuration - https://github.com/advisories/GHSA-x6jw-m9v5-85vh
simple-git allows command execution through unblocked Git configuration includes - https://github.com/advisories/GHSA-g4wm-2vf7-vfgr
simple-git: unsafe-operations plugin bypass via git long-option abbreviation (--receive-p/--exe) -> command execution (residual of CVE-2026-28291) - https://github.com/advisories/GHSA-858h-whjf-mvg5
fix available via `npm audit fix`
node_modules/simple-git

tmp  <=0.2.5
Severity: high
tmp allows arbitrary temporary file / directory write via symbolic link `dir` parameter - https://github.com/advisories/GHSA-52f5-9888-hmc6
tmp has Path Traversal via unsanitized prefix/postfix that enables directory escape - https://github.com/advisories/GHSA-ph9p-34f9-6g65
fix available via `npm audit fix`
node_modules/tmp
  external-editor  >=1.1.1
  Depends on vulnerable versions of tmp
  node_modules/external-editor
    @inquirer/editor  <=4.2.15
    Depends on vulnerable versions of external-editor
    node_modules/@inquirer/editor

yaml  2.0.0 - 2.8.2
Severity: moderate
yaml is vulnerable to Stack Overflow via deeply nested YAML collections - https://github.com/advisories/GHSA-48c2-rrv3-qjmp
fix available via `npm audit fix`
node_modules/yaml

12 vulnerabilities (2 low, 1 moderate, 8 high, 1 critical)

To address issues that do not require attention, run:
  npm audit fix

To address all issues (including breaking changes), run:
  npm audit fix --force

```
