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
   ```

3. Install plugin dependencies:
   ```bash
   composer update -d stop-user-enumeration
   ```

### Project Structure

- `/stop-user-enumeration` - Main plugin directory
- `/tests` - Test files
- `/.github/workflows` - GitHub Actions workflows
- `/build-clean.sh` - Build script for creating distribution packages

### Building for Distribution

To create a clean distribution package suitable for WordPress.org:

```bash
# Using the build script directly
./build-clean.sh

# Or using composer
composer run build
```

This will:
- Install production dependencies only
- Remove all development files (composer.json, tests, .git, etc.)
- Create a clean zip in `zipped/stop-user-enumeration-{version}.zip`

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

The plugin includes PHPUnit tests for security and functionality verification.

1. Install dependencies:
   ```bash
   npm install
   composer install
   ```

2. Start the wp-env environment:
   ```bash
   npm run start
   ```

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

After building with `./build-clean.sh`:

1. Test the packaged plugin in a clean WordPress installation
2. Run the Plugin Check plugin on the packaged version
3. Submit to WordPress.org via SVN

## Security

- Report security vulnerabilities via [Patchstack VDP](https://patchstack.com/database/vdp/stop-user-enumeration)
- Do not open public issues for security problems

## License

GPL v2 or later

## Credits

This project is tested with BrowserStack.