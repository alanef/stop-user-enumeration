# Stop User Enumeration

A WordPress security plugin that prevents user enumeration attacks by blocking unauthorized access to user data through various WordPress endpoints.

[![Plugin Check](https://github.com/alanef/stop-user-enumeration/actions/workflows/php.yml/badge.svg)](https://github.com/alanef/stop-user-enumeration/actions/workflows/php.yml)

## Features

- Blocks user enumeration via author archives
- Prevents REST API user endpoint access for non-authenticated users
- Stops user data leakage through oEmbed
- Removes author sitemaps
- Logs enumeration attempts for fail2ban integration
- Removes numbers from comment author names

## Project Structure

This repository follows a standard WordPress plugin development structure with separation between development resources and the actual plugin code:

```
stop-user-enumeration/              # Repository root
├── .github/workflows/              # GitHub Actions CI/CD
├── tests/                          # PHPUnit test suite
├── vendor/                         # Composer dependencies (dev only)
├── .wp-env.json                    # WordPress environment config
├── .wp-env.override.json           # Local environment overrides
├── composer.json                   # Development dependencies
├── package.json                    # Node.js scripts for testing
├── phpcs_sec.xml                   # PHP CodeSniffer rules
├── phpunit.xml.dist                # PHPUnit configuration
├── README.md                       # This file
└── stop-user-enumeration/          # The actual plugin directory
    ├── admin/                      # Admin functionality
    ├── frontend/                   # Frontend functionality
    ├── includes/                   # Core plugin files
    │   └── vendor/                 # Production dependencies
    ├── languages/                  # Translation files
    ├── changelog.txt               # Version history
    ├── composer.json               # Plugin dependencies
    ├── readme.txt                  # WordPress.org readme
    ├── stop-user-enumeration.php   # Main plugin file
    └── .distignore                 # Files to exclude from distribution

```

### Key Concepts

- **Development resources** (tests, build tools, etc.) are at the repository root
- **Plugin code** lives in the `stop-user-enumeration/` subdirectory
- **Production dependencies** are installed in `stop-user-enumeration/includes/vendor/`
- **Development dependencies** are installed in the root `vendor/` directory
- The `.distignore` file controls what gets included in the distribution build

## Development

### Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/alanef/stop-user-enumeration.git
   cd stop-user-enumeration
   ```

2. Install development dependencies:
   ```bash
   composer install
   npm install
   ```

3. Install plugin dependencies:
   ```bash
   composer update -d stop-user-enumeration
   ```

### Building for Distribution

The project uses `wp dist-archive` command to create clean distribution packages. This tool respects the `.distignore` file in the plugin directory to exclude development files.

```bash
# Install wp-cli dist-archive command (first time only)
wp package install wp-cli/dist-archive-command

# Build the plugin
composer run build

# Or manually:
cd stop-user-enumeration && composer install --no-dev && cd ..
wp dist-archive ./stop-user-enumeration ./stop-user-enumeration.zip
```

This will:
- Install production dependencies only (no dev dependencies)
- Create a distribution-ready ZIP file excluding all files listed in `.distignore`
- Output the ZIP to `zipped/stop-user-enumeration-free.zip`

The `.distignore` file ensures that development files like tests, composer.json, phpunit configs, etc. are not included in the distribution package.

### Testing

Run code standards checks:
```bash
composer run check
```

Run specific PHP compatibility checks:
```bash
composer run compat:7.4
composer run compat:8.0
composer run compat:8.1
composer run compat:8.2
composer run compat:8.3
```

Run security checks:
```bash
composer run phpcs
```

### Unit Testing

The plugin uses [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) for local development and testing. This provides a Docker-based WordPress environment configured via `.wp-env.json`.

#### Setup Testing Environment

1. Install dependencies:
   ```bash
   npm install
   composer install
   ```

2. Start the wp-env environment:
   ```bash
   npm run start
   ```
   This creates a local WordPress site at http://localhost:8888 with the plugin activated.

3. Run PHPUnit tests:
   ```bash
   npm test
   # or
   npm run test:unit
   ```

4. Run specific tests:
   ```bash
   # Test vulnerability fixes
   npm run test:fixes
   
   # Test with wp-env (full integration)
   npm run test:vulnerabilities
   ```

5. Stop the environment:
   ```bash
   npm run stop
   ```

#### Test Configuration

- **`.wp-env.json`**: Defines the WordPress environment configuration
- **`.wp-env.override.json`**: Local overrides for mappings and PHP version
- **`phpunit.xml.dist`**: PHPUnit configuration
- **`tests/`**: Contains all test files

The test suite includes:
- REST API security tests
- Authentication bypass prevention tests
- Query parameter manipulation tests
- Filter and hook integration tests
- Compliance verification tests

### Releasing

1. Update version numbers in:
   - `stop-user-enumeration/stop-user-enumeration.php` (header and constant)
   - `stop-user-enumeration/readme.txt` (Stable tag)
   - `stop-user-enumeration/changelog.txt`

2. Commit changes:
   ```bash
   git add .
   git commit -m "Version 1.7.3"
   ```

3. Create a release tag:
   ```bash
   git tag v1.7.3
   git push origin main
   git push origin v1.7.3
   ```

The GitHub Actions release workflow will automatically:
- Build a clean distribution package
- Create a GitHub release
- Attach the distribution zip

### WordPress.org Submission

After building with `composer run build`:

1. Test the packaged plugin from `zipped/stop-user-enumeration-free.zip` in a clean WordPress installation
2. Run the Plugin Check plugin on the packaged version
3. Submit to WordPress.org via SVN

## Security

- Report security vulnerabilities via [Patchstack VDP](https://patchstack.com/database/vdp/stop-user-enumeration)
- Do not open public issues for security problems

## License

GPL v2 or later

## Credits

This project is tested with BrowserStack.