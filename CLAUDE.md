# Stop User Enumeration - Development Guide

## Version Update Checklist

When updating the plugin version, the following files need to be modified:

1. **`stop-user-enumeration/stop-user-enumeration.php`**:
   - Line 6: Plugin header `Version: X.X.X`
   - Line 45: Constant `define( 'STOP_USER_ENUMERATION_PLUGIN_VERSION', 'X.X.X' );`

2. **`stop-user-enumeration/readme.txt`**:
   - Line 8: `Stable tag: X.X.X`

3. **`stop-user-enumeration/changelog.txt`**:
   - Add new version entry at the top with changes in the format:
     ```
     = X.X.X =
     * Change description 1
     * Change description 2
     ```

4. **Online WordPress Changelog**:
   - Update automatically using: `update-changelog.sh` (available in PATH)
   - Uses BasePress custom API (article ID: 10024)

Example command to verify all version references:
```bash
grep -n "1\.7\.5" stop-user-enumeration/stop-user-enumeration.php stop-user-enumeration/readme.txt
```

### Updating the Online Changelog

The online changelog at https://fullworksplugins.com/docs/stop-user-enumeration/usage-stop-user-enumeration/change-log-4/ is a BasePress knowledge base article that can be updated automatically.

1. **One-time Setup**:
   ```bash
   # Copy the example file
   cp .env.wordpress.example .env.wordpress
   
   # Edit .env.wordpress and add:
   # - WP_SITE_URL (https://fullworksplugins.com)
   # - WP_USERNAME (your WordPress username)
   # - WP_APP_PASSWORD (from WordPress Admin > Users > Your Profile > Application Passwords)
   # - WP_POST_ID (10024 for this plugin's changelog article)
   ```

2. **Update the Changelog**:
   ```bash
   # After updating changelog.txt, run:
   update-changelog.sh
   ```

The script (`update-changelog.sh`) is available globally in PATH from the dev_scripts project.

The script will:
- Load credentials from `.env.wordpress`
- Convert `changelog.txt` to HTML (uses pandoc if available)
- Update the BasePress article via custom API
- Display the URL to view changes

**Note**: The script uses the BasePress custom API endpoint (`/wp-json/basepress/v1/article/`) which is configured in the BasePress API plugin on the site.

## Build and Release Process

1. **Update version numbers** in all files listed above
2. **Update changelog.txt** with version changes
3. **Run build**:
   ```bash
   composer run build
   ```
4. **Commit changes**:
   ```bash
   git add .
   git commit -m "Version X.X.X"
   ```
5. **Create and push tag**:
   ```bash
   git tag vX.X.X
   git push origin main
   git push origin vX.X.X
   ```
6. **Update online changelog**:
   ```bash
   update-changelog.sh
   ```

The GitHub Actions workflow will automatically create a release and deploy to WordPress.org SVN.

## PHP Compatibility Testing

The plugin supports PHP 7.4 through 8.4. Test compatibility using:

```bash
# Test all supported versions
composer run check

# Test specific version
composer run compat:8.4
```

## Development Environment

- Use `wp-env` for local development: `npm run start`
- Run tests: `npm test`
- Stop environment: `npm run stop`