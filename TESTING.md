# Testing Guide - ContentPoll AI

## ✅ Setup Complete!

Your wp-config.php has been configured to support test database prefixes. Tests are now safe to run.

## Quick Start

### Safe Testing (Recommended)
```bash
# Run this anytime - won't delete your data
./run-tests.sh
```

### Testing with Clean Database
```bash
# Requires confirmation - will DELETE all votes
./run-tests.sh --with-truncate
```

## How It Works

### Protection Mechanism

The test bootstrap checks your database prefix:

| Prefix | Behavior |
|--------|----------|
| `test_*` | ✅ Truncates safely (test database) |
| `phpunit_*` | ✅ Truncates safely (test database) |
| `wp_*` (yours) | ✅ Runs tests but **NO truncation** |
| With `TRUNCATE_TEST_DATA=true` | ⚠️  Truncates any prefix |

### Current Status

- ✅ wp-config.php supports `WP_TESTS_DB_PREFIX` environment variable
- ✅ Production database (`wp_` prefix) is protected
- ✅ Tests run without deleting your data
- ⚠️  Some tests may fail (they expect clean database)

## Running Tests

### Manual Commands

**PHP tests only** (safe - no truncation with `wp_` prefix):
```bash
composer test
```

**JavaScript tests** (always safe - no database):
```bash
npm test
```

**All tests**:
```bash
composer test && npm test
```

### With Database Truncation

**Option 1: Using test runner** (safest - asks for confirmation):
```bash
./run-tests.sh --with-truncate
```

**Option 2: Direct command** (no confirmation - dangerous!):
```bash
TRUNCATE_TEST_DATA=true composer test
```

## Advanced: Separate Test Database

If you want completely isolated test data, you can set up a test database prefix:

### One-Time Setup

1. Your wp-config.php is already configured ✓

2. Initialize test database:
```bash
# Create test tables by triggering WordPress with test prefix
WP_TESTS_DB_PREFIX=test_ wp eval "global \$wpdb; echo 'Using prefix: ' . \$wpdb->prefix;"
```

3. Run tests with test prefix:
```bash
WP_TESTS_DB_PREFIX=test_ composer test
```

This creates separate tables like:
- `test_posts`
- `test_users`  
- `test_vote_block_submissions`

Your production data (`wp_*` tables) remains untouched.

## CI/CD Configuration

For GitHub Actions or automated testing:

```yaml
- name: Run Tests
  env:
    TRUNCATE_TEST_DATA: true  # Safe in CI - fresh database
  run: composer test
```

## Troubleshooting

### Tests fail with "Expected 1, got 3"

This means tests are running against production data. Either:
- Accept that tests verify against real data
- Run `./run-tests.sh --with-truncate` before testing
- Use `WP_TESTS_DB_PREFIX=test_` for isolated testing

### Database Error with WP_TESTS_DB_PREFIX

The test prefix requires WordPress to create tables. If you see "Database Error":
1. Make sure WordPress can write to the database
2. The test runner will create tables on first run
3. Or stick with safe mode testing (no prefix needed)

## Test Coverage

- **PHP Tests**: 11 tests covering storage, API, and aggregation
- **JavaScript Tests**: 8 tests covering vote logic and helpers
- **Total**: 19 automated tests

## Development Workflow

1. **Write code**
2. **Run tests safely**: `./run-tests.sh`
3. **Fix failures** (if any)
4. **Before commit**: `./run-tests.sh --with-truncate`
5. **Commit** when all tests pass

Your production vote data stays safe throughout development! 🎉
