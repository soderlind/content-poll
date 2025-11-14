# Example: wp-config.php Configuration for Test Database

Add this code to your `wp-config.php` file to support separate test database prefix:

```php
<?php
/**
 * WordPress Database Table prefix.
 * 
 * Support for test database prefix to prevent test data corruption.
 * When WP_TESTS_DB_PREFIX environment variable is set, use that prefix.
 * This allows running tests against a separate set of tables.
 */

// Check if running tests with custom prefix
if ( getenv( 'WP_TESTS_DB_PREFIX' ) ) {
    $table_prefix = getenv( 'WP_TESTS_DB_PREFIX' );
} else {
    // Production prefix
    $table_prefix = 'wp_';
}

// Rest of your wp-config.php continues below...
```

## Usage Examples

### Run tests with test prefix
```bash
WP_TESTS_DB_PREFIX=test_ composer test
```
This creates tables like:
- `test_posts`
- `test_users`
- `test_vote_block_submissions`

### Run tests with phpunit prefix
```bash
WP_TESTS_DB_PREFIX=phpunit_ composer test
```

### Normal WordPress usage (no environment variable)
Your site continues to use `wp_` prefix as normal.

## Alternative: Separate Test WordPress Installation

Instead of modifying wp-config.php, you can point tests to a completely separate WordPress installation:

```bash
WP_ROOT=/path/to/test-wordpress composer test
```

This is the safest option as it completely isolates test data from production.
