#!/bin/bash
# Test runner script with database safety
# Usage: ./run-tests.sh [--with-truncate]

set -e

cd "$(dirname "$0")"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}ContentPoll AI - Test Runner${NC}"
echo ""

# Check for truncate flag
if [ "$1" = "--with-truncate" ]; then
    echo -e "${RED}⚠️  WARNING: This will DELETE all votes from your database!${NC}"
    echo ""
    read -p "Are you sure you want to truncate vote data? Type 'yes' to continue: " confirm
    if [ "$confirm" != "yes" ]; then
        echo "Test run cancelled."
        exit 1
    fi
    echo ""
    echo -e "${YELLOW}Running tests with database truncation...${NC}"
    TRUNCATE_TEST_DATA=true composer test
else
    echo -e "${GREEN}Running tests in SAFE MODE (no database truncation)${NC}"
    echo "Your production vote data will NOT be deleted."
    echo ""
    echo "Note: Some tests may fail because they expect a clean database."
    echo "To run with truncation: ./run-tests.sh --with-truncate"
    echo ""
    composer test
fi

echo ""
echo -e "${GREEN}JavaScript tests:${NC}"
npm test
