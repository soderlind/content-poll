# Testing Guide

## Database Safety for Tests

**Important**: By default, tests will NOT truncate your production database to prevent accidental data loss.

### Option 1: Use a Test Database (Recommended)

Create a separate test database with a different prefix:

1. **Add test database configuration to `wp-config.php`**:
```php
// Near the top, before table_prefix is set
if ( getenv( 'WP_TESTS_DB_PREFIX' ) ) {
    $table_prefix = getenv( 'WP_TESTS_DB_PREFIX' );
} else {
    $table_prefix = 'wp_'; // Your production prefix
}
```

2. **Run tests with test prefix**:
```bash
WP_TESTS_DB_PREFIX=test_ composer test
```

This creates tables like `test_vote_block_submissions` instead of `wp_vote_block_submissions`.

### Option 2: Separate Test WordPress Installation

Point tests to a completely separate WordPress installation:

```bash
WP_ROOT=/path/to/test-wordpress composer test
```

### Option 3: Allow Truncating Production (NOT RECOMMENDED)

Only use this if you understand the risks:

```bash
TRUNCATE_TEST_DATA=true composer test
```

This will DELETE all votes from your database before running tests!

## Running Tests

### All Tests
```bash
composer test           # PHP tests only
npm test               # JavaScript tests (lint + vitest)
```

### Specific Test Files
```bash
vendor/bin/phpunit tests/php/VoteStorageServiceTest.php
```

### With Coverage
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Database Prefixes

The bootstrap file checks for these safe prefixes before truncating:
- `test_*` - Test database prefix
- `phpunit_*` - PHPUnit test prefix
- Environment variable `TRUNCATE_TEST_DATA=true` - Explicit permission

If your database doesn't use one of these prefixes, tests will run against your production data but **will NOT truncate it**.

## CI/CD Configuration

For GitHub Actions or other CI systems, set environment variables:

```yaml
- name: Run Tests
  env:
    WP_TESTS_DB_PREFIX: test_
    TRUNCATE_TEST_DATA: true
  run: composer test
```
