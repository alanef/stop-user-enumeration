# Stop User Enumeration - Security Fix Verification

## Vulnerability Summary

Two vulnerabilities were reported by WPScan:

1. **URL-encoding bypass**: The REST API protection could be bypassed by URL-encoding the path
2. **Query parameter bypass**: Adding `?foo=simple-jwt-login` to any request would bypass protection

## How the Fixes Work

### Previous Implementation (Vulnerable)
The plugin was checking `$_SERVER['REQUEST_URI']` which:
- Used `rawurldecode()` on the URI, but WordPress handles URL-encoded paths differently
- Checked for "simple-jwt-login" anywhere in the URI, including query parameters

### New Implementation (Secure)
The plugin now:
- Uses WordPress's `rest_pre_dispatch` filter with proper `WP_REST_Request` object
- Gets the actual route via `$request->get_route()` which is already decoded by WordPress
- Only checks for "simple-jwt-login" in the actual route path, not query parameters
- Uses more specific regex patterns: `#^/wp/v[0-9]+/users#i` and `#^/simple-jwt-login/#i`

## Testing the Fixes

### Manual Testing

1. Start wp-env:
```bash
npm run start
```

2. Run the vulnerability test:
```bash
./test-vulnerability-with-wp-env.sh
```

### Expected Results

✅ **PASSED Tests:**
- Normal REST API request (`/wp-json/wp/v2/users`) - Blocked with 401
- URL-encoded bypass attempts - Return 404 (WordPress doesn't recognize encoded routes)
- Query parameter bypass (`?foo=simple-jwt-login`) - Blocked with 401
- Non-user endpoints still work normally
- Authenticated users can still access the endpoint

### Code Changes

1. **Hook Change** (class-core.php:180):
   ```php
   // Before (vulnerable):
   $this->loader->add_action( 'rest_authentication_errors', $plugin_public, 'only_allow_logged_in_rest_access_to_users' );
   
   // After (secure):
   $this->loader->add_filter( 'rest_pre_dispatch', $plugin_public, 'only_allow_logged_in_rest_access_to_users', 10, 3 );
   ```

2. **Method Signature Change** (class-frontend.php:193):
   ```php
   // Before:
   public function only_allow_logged_in_rest_access_to_users( $access )
   
   // After:
   public function only_allow_logged_in_rest_access_to_users( $result, $server, $request )
   ```

3. **Route Detection** (class-frontend.php:196):
   ```php
   // Before (vulnerable):
   $request_uri = sanitize_text_field( wp_unslash( rawurldecode( $_SERVER['REQUEST_URI'] ) ) );
   
   // After (secure):
   $route = $request->get_route();
   ```

4. **Pattern Matching** (class-frontend.php:199):
   ```php
   // Before (too broad):
   $pattern = '/users/i';
   $exception = '/simple-jwt-login/i';
   
   // After (specific):
   $pattern = '#^/wp/v[0-9]+/users#i';
   $exception = '#^/simple-jwt-login/#i';
   ```

## Additional Security Improvements

1. **IP Validation**: Added `FILTER_VALIDATE_IP` flag to properly validate IP addresses
2. **X-Forwarded-For Handling**: Properly parses multiple IPs in the header

## Verification

The fixes have been verified to:
- Block the URL-encoding bypass attempts
- Block the query parameter bypass attempts
- Maintain backward compatibility with existing filters
- Not affect legitimate simple-jwt-login routes
- Allow authenticated users to access the endpoint

## Version

These fixes are included in version 1.7.3 of the Stop User Enumeration plugin.