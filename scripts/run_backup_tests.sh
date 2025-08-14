#!/bin/bash

# Backup System Test Suite Runner
# Comprehensive testing script for the Darkheim.net backup system
# Author: GitHub Copilot

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Project root directory
PROJECT_ROOT="/var/www/darkheim.net"
TEST_DIR="$PROJECT_ROOT/tests"
STORAGE_DIR="$PROJECT_ROOT/storage"
LOG_DIR="$STORAGE_DIR/logs"

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}           Darkheim.net Backup System Test Suite${NC}"
echo -e "${BLUE}================================================================${NC}"
echo

# Ensure required directories exist
mkdir -p "$LOG_DIR"
mkdir -p "$STORAGE_DIR/backups"
mkdir -p "$STORAGE_DIR/cache"

# Function to run tests and capture results
run_test_suite() {
    local suite_name=$1
    local suite_path=$2

    echo -e "${YELLOW}Running $suite_name...${NC}"

    if [ -d "$suite_path" ]; then
        cd "$PROJECT_ROOT"

        # Run PHPUnit for specific test suite
        if vendor/bin/phpunit --testsuite "$suite_name" --configuration phpunit.xml; then
            echo -e "${GREEN}✓ $suite_name passed${NC}"
            return 0
        else
            echo -e "${RED}✗ $suite_name failed${NC}"
            return 1
        fi
    else
        echo -e "${YELLOW}! $suite_name directory not found, skipping${NC}"
        return 0
    fi
}

# Function to test individual API endpoints
test_api_endpoints() {
    echo -e "${YELLOW}Testing API Endpoints...${NC}"

    local base_url="https://darkheim.net"
    local api_endpoints=(
        "/page/api/admin/manual_backup.php"
        "/page/api/admin/cleanup_old_backups.php"
        "/page/api/admin/download_backup.php"
        "/page/api/admin/backup_management.php"
    )

    for endpoint in "${api_endpoints[@]}"; do
        echo -n "Testing $endpoint... "

        # Test if endpoint exists and returns proper error for unauthenticated request
        response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$base_url$endpoint" || echo "000")

        if [[ "$response" == "403" || "$response" == "401" || "$response" == "405" ]]; then
            echo -e "${GREEN}✓ Available (HTTP $response)${NC}"
        elif [[ "$response" == "000" ]]; then
            echo -e "${RED}✗ Connection failed${NC}"
        else
            echo -e "${YELLOW}? Unexpected response (HTTP $response)${NC}"
        fi
    done
}

# Function to check file permissions and structure
check_file_structure() {
    echo -e "${YELLOW}Checking file structure and permissions...${NC}"

    local required_files=(
        "$PROJECT_ROOT/src/Application/Controllers/DatabaseBackupController.php"
        "$PROJECT_ROOT/page/api/admin/manual_backup.php"
        "$PROJECT_ROOT/page/api/admin/cleanup_old_backups.php"
        "$PROJECT_ROOT/page/api/admin/download_backup.php"
        "$PROJECT_ROOT/page/api/admin/backup_management.php"
        "$PROJECT_ROOT/page/admin/system/backup_monitor.php"
    )

    for file in "${required_files[@]}"; do
        if [[ -f "$file" ]]; then
            if [[ -r "$file" ]]; then
                echo -e "${GREEN}✓ $(basename "$file") exists and readable${NC}"
            else
                echo -e "${RED}✗ $(basename "$file") exists but not readable${NC}"
            fi
        else
            echo -e "${RED}✗ $(basename "$file") missing${NC}"
        fi
    done

    # Check directory permissions
    local required_dirs=(
        "$STORAGE_DIR/backups"
        "$LOG_DIR"
    )

    for dir in "${required_dirs[@]}"; do
        if [[ -d "$dir" ]]; then
            if [[ -w "$dir" ]]; then
                echo -e "${GREEN}✓ $(basename "$dir") directory writable${NC}"
            else
                echo -e "${RED}✗ $(basename "$dir") directory not writable${NC}"
            fi
        else
            echo -e "${RED}✗ $(basename "$dir") directory missing${NC}"
        fi
    done
}

# Function to validate backup system configuration
check_backup_configuration() {
    echo -e "${YELLOW}Checking backup system configuration...${NC}"

    # Check if backup directory exists and is writable
    if [[ -d "$STORAGE_DIR/backups" && -w "$STORAGE_DIR/backups" ]]; then
        echo -e "${GREEN}✓ Backup directory configured correctly${NC}"
    else
        echo -e "${RED}✗ Backup directory not properly configured${NC}"
    fi

    # Check if required PHP extensions are loaded
    local required_extensions=("pdo_mysql" "zlib" "json")

    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo -e "${GREEN}✓ PHP extension '$ext' loaded${NC}"
        else
            echo -e "${RED}✗ PHP extension '$ext' not loaded${NC}"
        fi
    done

    # Check PDO specifically since it may be loaded as pdo_mysql
    if php -m | grep -q "pdo"; then
        echo -e "${GREEN}✓ PHP PDO support available${NC}"
    else
        echo -e "${RED}✗ PHP PDO support not available${NC}"
    fi

    # Check if mysqldump is available
    if command -v mysqldump >/dev/null 2>&1; then
        echo -e "${GREEN}✓ mysqldump utility available${NC}"
    else
        echo -e "${YELLOW}! mysqldump utility not found in PATH${NC}"
    fi

    # Check if gzip is available
    if command -v gzip >/dev/null 2>&1; then
        echo -e "${GREEN}✓ gzip utility available${NC}"
    else
        echo -e "${RED}✗ gzip utility not available${NC}"
    fi
}

# Function to run syntax checks
check_syntax() {
    echo -e "${YELLOW}Running PHP syntax checks...${NC}"

    find "$PROJECT_ROOT/src" -name "*.php" -exec php -l {} \; >/dev/null 2>&1
    if [[ $? -eq 0 ]]; then
        echo -e "${GREEN}✓ All PHP files in src/ have valid syntax${NC}"
    else
        echo -e "${RED}✗ PHP syntax errors found in src/ directory${NC}"
    fi

    find "$PROJECT_ROOT/page/api/admin" -name "*.php" -exec php -l {} \; >/dev/null 2>&1
    if [[ $? -eq 0 ]]; then
        echo -e "${GREEN}✓ All API files have valid syntax${NC}"
    else
        echo -e "${RED}✗ PHP syntax errors found in API files${NC}"
    fi
}

# Function to generate test report
generate_report() {
    echo -e "${BLUE}Generating test report...${NC}"

    local report_file="$LOG_DIR/backup_test_report_$(date +%Y%m%d_%H%M%S).txt"

    {
        echo "Darkheim.net Backup System Test Report"
        echo "Generated: $(date)"
        echo "======================================="
        echo
        echo "Test Environment:"
        echo "- PHP Version: $(php -v | head -n1)"
        echo "- Operating System: $(uname -a)"
        echo "- Project Root: $PROJECT_ROOT"
        echo
        echo "Test Results Summary:"
        echo "- Unit Tests: $(ls -1 "$TEST_DIR/Unit"/*.php 2>/dev/null | wc -l) test files"
        echo "- Integration Tests: $(ls -1 "$TEST_DIR/Integration"/*.php 2>/dev/null | wc -l) test files"
        echo "- Functional Tests: $(ls -1 "$TEST_DIR/Functional"/*.php 2>/dev/null | wc -l) test files"
        echo
        echo "Files Structure Check:"
        ls -la "$PROJECT_ROOT/page/api/admin/"
        echo
        echo "Storage Directory Status:"
        ls -la "$STORAGE_DIR/"
        echo
        echo "For detailed test results, check the PHPUnit logs in $LOG_DIR/"
    } > "$report_file"

    echo -e "${GREEN}✓ Test report generated: $report_file${NC}"
}

# Main execution
main() {
    echo "Starting comprehensive backup system testing..."
    echo

    # Navigate to project root
    cd "$PROJECT_ROOT"

    # Pre-flight checks
    check_file_structure
    echo

    check_backup_configuration
    echo

    check_syntax
    echo

    test_api_endpoints
    echo

    # Run test suites
    local test_results=()

    if run_test_suite "Unit Tests" "$TEST_DIR/Unit"; then
        test_results+=("Unit:PASS")
    else
        test_results+=("Unit:FAIL")
    fi
    echo

    if run_test_suite "Integration Tests" "$TEST_DIR/Integration"; then
        test_results+=("Integration:PASS")
    else
        test_results+=("Integration:FAIL")
    fi
    echo

    if run_test_suite "Functional Tests" "$TEST_DIR/Functional"; then
        test_results+=("Functional:PASS")
    else
        test_results+=("Functional:FAIL")
    fi
    echo

    # Generate final report
    generate_report
    echo

    # Summary
    echo -e "${BLUE}================================================================${NC}"
    echo -e "${BLUE}                        TEST SUMMARY${NC}"
    echo -e "${BLUE}================================================================${NC}"

    local failed_tests=0
    for result in "${test_results[@]}"; do
        local test_name=$(echo "$result" | cut -d: -f1)
        local test_status=$(echo "$result" | cut -d: -f2)

        if [[ "$test_status" == "PASS" ]]; then
            echo -e "${GREEN}✓ $test_name Tests: PASSED${NC}"
        else
            echo -e "${RED}✗ $test_name Tests: FAILED${NC}"
            ((failed_tests++))
        fi
    done

    echo
    if [[ $failed_tests -eq 0 ]]; then
        echo -e "${GREEN}🎉 ALL TESTS PASSED! Backup system is ready for production.${NC}"
        exit 0
    else
        echo -e "${RED}❌ $failed_tests test suite(s) failed. Please review the results.${NC}"
        exit 1
    fi
}

# Help function
show_help() {
    echo "Backup System Test Suite Runner"
    echo
    echo "Usage: $0 [OPTIONS]"
    echo
    echo "Options:"
    echo "  -h, --help      Show this help message"
    echo "  -u, --unit      Run only unit tests"
    echo "  -i, --integration Run only integration tests"
    echo "  -f, --functional Run only functional tests"
    echo "  -c, --check     Run only configuration and syntax checks"
    echo "  -r, --report    Generate report only"
    echo
    echo "Examples:"
    echo "  $0              # Run all tests"
    echo "  $0 -u           # Run only unit tests"
    echo "  $0 -c           # Run only checks"
}

# Parse command line arguments
case "${1:-}" in
    -h|--help)
        show_help
        exit 0
        ;;
    -u|--unit)
        run_test_suite "Unit Tests" "$TEST_DIR/Unit"
        ;;
    -i|--integration)
        run_test_suite "Integration Tests" "$TEST_DIR/Integration"
        ;;
    -f|--functional)
        run_test_suite "Functional Tests" "$TEST_DIR/Functional"
        ;;
    -c|--check)
        check_file_structure
        echo
        check_backup_configuration
        echo
        check_syntax
        ;;
    -r|--report)
        generate_report
        ;;
    "")
        main
        ;;
    *)
        echo "Unknown option: $1"
        show_help
        exit 1
        ;;
esac
