#!/bin/bash

# UWEZO LMS Professional Deployment Script
# Deploys the application using Docker images via SCP transfer
# Designed for Laravel 11, Horizon, and MariaDB 11.4

set -euo pipefail

# Get script directory and project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Change to project root directory
cd "$PROJECT_ROOT"

# Configuration
ENVIRONMENT=""
STEP=""
COMPOSE_PROJECT_NAME="uwezo-lms"
# These match the buildable services in your docker-compose.yml
SERVICES=(app worker horizon)
REMOTE_APP_CONTAINER="uwezo-lms-app"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --step)
                STEP="$2"
                shift 2
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                if [[ -z "$ENVIRONMENT" ]]; then
                    ENVIRONMENT="$1"
                else
                    log_error "Unknown argument: $1"
                    exit 1
                fi
                shift
                ;;
        esac
    done

    ENVIRONMENT=${ENVIRONMENT:-staging}
    STEP=${STEP:-all}
}

# Load deployment configuration
load_env_vars() {
    if [[ -n "${GITHUB_ACTIONS:-}" ]]; then
        DEPLOY_HOST="${DEPLOY_HOST:-}"
        DEPLOY_USER="${DEPLOY_USER:-}"
        DEPLOY_PATH="${DEPLOY_PATH:-}"
        DEPLOY_KEY="${DEPLOY_KEY:-}"
    else
        if [[ ! -f ".env" ]]; then
            log_error ".env file not found. Please create it with deployment configuration."
            exit 1
        fi

        set -a
        source .env
        set +a

        local env_upper=$(echo "$ENVIRONMENT" | tr '[:lower:]' '[:upper:]')

        DEPLOY_HOST="${!(${env_upper}_DEPLOY_HOST):-}"
        # Fallback to standard names if env-prefixed ones aren't found
        [[ -z "$DEPLOY_HOST" ]] && DEPLOY_HOST="${!env_upper}_DEPLOY_HOST" || DEPLOY_HOST="$DEPLOY_HOST"

        # Manual mapping for local dev if prefix logic is too complex
        DEPLOY_HOST=$(eval echo \$${env_upper}_DEPLOY_HOST)
        DEPLOY_USER=$(eval echo \$${env_upper}_DEPLOY_USER)
        DEPLOY_PATH=$(eval echo \$${env_upper}_DEPLOY_PATH)
        DEPLOY_KEY_PATH=$(eval echo \$${env_upper}_DEPLOY_KEY_PATH)
    fi

    if [[ -z "$DEPLOY_HOST" || -z "$DEPLOY_USER" || -z "$DEPLOY_PATH" ]]; then
        log_error "Missing required deployment variables for $ENVIRONMENT (Host, User, or Path)."
        exit 1
    fi
}

setup_ssh() {
    if [[ -n "${DEPLOY_KEY:-}" ]]; then
        echo "$DEPLOY_KEY" > /tmp/deploy_key
        chmod 600 /tmp/deploy_key
        SSH_KEY_ARG="-i /tmp/deploy_key"
    elif [[ -n "${DEPLOY_KEY_PATH:-}" ]]; then
        SSH_KEY_ARG="-i $DEPLOY_KEY_PATH"
    else
        SSH_KEY_ARG=""
    fi

    SSH_OPTIONS="-o ConnectTimeout=30 -o ServerAliveInterval=10 -o StrictHostKeyChecking=no"

    if ! ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" "echo 'SSH connection successful'" > /dev/null 2>&1; then
        log_error "Failed to connect to $DEPLOY_USER@$DEPLOY_HOST"
        exit 1
    fi
}

# --- CORE ACTIONS ---

build_images() {
    log_info "Building Laravel assets and Docker images..."
    # Build local assets so they are included in the COPY command of Dockerfile
    if [ -f "package.json" ]; then
        npm install && npm run build
    fi
    docker compose build --no-cache
}

check_images() {
    local missing_images=()
    for SERVICE in "${SERVICES[@]}"; do
        IMAGE_NAME="${COMPOSE_PROJECT_NAME}-${SERVICE}:latest"
        if ! docker image inspect "$IMAGE_NAME" > /dev/null 2>&1; then
            missing_images+=("$IMAGE_NAME")
        fi
    done

    if [[ ${#missing_images[@]} -gt 0 ]]; then
        log_error "Missing images: ${missing_images[*]}"
        exit 1
    fi
}

transfer_images() {
    log_info "Transferring images to remote server..."
    ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" "mkdir -p $DEPLOY_PATH"

    for SERVICE in "${SERVICES[@]}"; do
        IMAGE_NAME="${COMPOSE_PROJECT_NAME}-${SERVICE}:latest"
        log_info "Exporting and uploading $SERVICE..."
        docker save "$IMAGE_NAME" | gzip | ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" "zcat | docker load"
    done
}

transfer_configs() {
    log_info "Uploading docker-compose.yml..."
    scp $SSH_KEY_ARG docker-compose.yml "$DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH/"
}

deploy_application() {
    log_info "Booting up containers on remote..."
    ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" \
        "cd $DEPLOY_PATH && docker compose up -d --remove-orphans --no-build"
}

post_deploy() {
    log_info "Running Laravel post-deployment tasks..."

    # Run Migrations
    ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" \
        "docker exec -t $REMOTE_APP_CONTAINER php artisan migrate --force"

    # Optimization
    ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" \
        "docker exec -t $REMOTE_APP_CONTAINER php artisan optimize:clear && \
         docker exec -t $REMOTE_APP_CONTAINER php artisan optimize"

    # Graceful restart for Horizon workers
    ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" \
        "docker exec -t $REMOTE_APP_CONTAINER php artisan horizon:terminate"

    # Fix Permissions
    ssh $SSH_KEY_ARG $SSH_OPTIONS "$DEPLOY_USER@$DEPLOY_HOST" \
        "docker exec -t -u root $REMOTE_APP_CONTAINER chown -R www-data:www-data storage bootstrap/cache"
}

cleanup() {
    if [[ -n "${DEPLOY_KEY:-}" && -f "/tmp/deploy_key" ]]; then
        rm -f /tmp/deploy_key
    fi
}

execute_step() {
    case "$STEP" in
        build)
            build_images
            log_success "Build finished."
            ;;
        deploy)
            check_images
            transfer_images
            transfer_configs
            deploy_application
            log_success "Deploy finished."
            ;;
        post-deploy)
            post_deploy
            log_success "Post-deploy finished."
            ;;
        all)
            build_images
            transfer_images
            transfer_configs
            deploy_application
            post_deploy
            log_success "Full deployment successful!"
            ;;
        *)
            log_error "Invalid step: $STEP"
            exit 1
            ;;
    esac
}

show_help() {
    echo "Usage: $0 [environment] [--step STEP]"
    echo "Steps: build, deploy, post-deploy, all"
}

# --- START ---
parse_args "$@"
load_env_vars
setup_ssh
trap cleanup EXIT
execute_step


# STAGING_DEPLOY_HOST=41.76.170.249
# STAGING_DEPLOY_USER=administrator
# STAGING_DEPLOY_PATH=/home/administrator/lms_staging_app
# STAGING_DEPLOY_KEY_PATH=~/.ssh/id_rsa

# trigger this script automatically using a GitHub Action whenever you push code
