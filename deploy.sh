#!/bin/bash

# E-Commerce Application Deployment Script
# This script automates the deployment process for the e-commerce application

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="E-Commerce App"
DEPLOY_USER="deploy"
DEPLOY_PATH="/var/www/ecommerce-app"
BACKUP_PATH="/var/backups/ecommerce-app"
LOG_FILE="/var/log/deploy.log"

# Functions
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_error "This script should not be run as root"
        exit 1
    fi
}

# Check dependencies
check_dependencies() {
    print_status "Checking dependencies..."

    # Check if required commands exist
    for cmd in git composer php npm; do
        if ! command -v $cmd &> /dev/null; then
            print_error "$cmd is not installed"
            exit 1
        fi
    done

    print_success "All dependencies are installed"
}

# Create backup
create_backup() {
    print_status "Creating backup..."

    BACKUP_DIR="$BACKUP_PATH/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"

    if [[ -d "$DEPLOY_PATH" ]]; then
        cp -r "$DEPLOY_PATH" "$BACKUP_DIR/current"
        print_success "Backup created at $BACKUP_DIR"
    else
        print_warning "No existing installation to backup"
    fi
}

# Update code
update_code() {
    print_status "Updating application code..."

    # Navigate to deploy path
    cd "$DEPLOY_PATH"

    # Pull latest changes
    git pull origin main

    # Install/update dependencies
    composer install --no-dev --optimize-autoloader
    npm ci

    print_success "Code updated successfully"
}

# Run database migrations
run_migrations() {
    print_status "Running database migrations..."

    cd "$DEPLOY_PATH"
    php artisan migrate --force

    print_success "Database migrations completed"
}

# Clear caches
clear_caches() {
    print_status "Clearing application caches..."

    cd "$DEPLOY_PATH"

    # Clear Laravel caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # Clear OPcache if enabled
    if php -m | grep -q "opcache"; then
        php artisan opcache:clear
    fi

    print_success "All caches cleared"
}

# Optimize application
optimize_app() {
    print_status "Optimizing application..."

    cd "$DEPLOY_PATH"

    # Cache configuration and routes
    php artisan config:cache
    php artisan route:cache

    # Optimize autoloader
    composer dump-autoload --optimize

    # Build frontend assets
    npm run build

    print_success "Application optimized"
}

# Set permissions
set_permissions() {
    print_status "Setting correct permissions..."

    cd "$DEPLOY_PATH"

    # Set ownership
    chown -R www-data:www-data storage bootstrap/cache

    # Set permissions
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache

    # Set executable permissions
    chmod +x deploy.sh

    print_success "Permissions set correctly"
}

# Health check
health_check() {
    print_status "Performing health check..."

    # Check if application is responding
    if curl -f -s -o /dev/null -w "%{http_code}" http://localhost/health | grep -q "200"; then
        print_success "Health check passed"
    else
        print_error "Health check failed"
        exit 1
    fi
}

# Rollback function
rollback() {
    print_status "Rolling back to previous version..."

    LATEST_BACKUP=$(ls -t "$BACKUP_PATH" | head -n 1)

    if [[ -n "$LATEST_BACKUP" ]]; then
        rm -rf "$DEPLOY_PATH"
        cp -r "$BACKUP_PATH/$LATEST_BACKUP/current" "$DEPLOY_PATH"
        clear_caches
        set_permissions
        print_success "Rollback completed"
    else
        print_error "No backup found for rollback"
        exit 1
    fi
}

# Main deployment function
deploy() {
    print_status "Starting deployment of $APP_NAME..."

    log_message "Deployment started"

    check_root
    check_dependencies
    create_backup
    update_code
    run_migrations
    clear_caches
    optimize_app
    set_permissions
    health_check

    print_success "Deployment completed successfully!"
    log_message "Deployment completed successfully"
}

# Parse command line arguments
case "${1:-deploy}" in
    "deploy")
        deploy
        ;;
    "rollback")
        rollback
        ;;
    "health")
        health_check
        ;;
    *)
        echo "Usage: $0 {deploy|rollback|health}"
        echo "  deploy   - Deploy the application"
        echo "  rollback  - Rollback to previous version"
        echo "  health    - Perform health check"
        exit 1
        ;;
esac
