#!/bin/bash

# UWEZO LMS Deployment Script
# Deploys Laravel 11 + Horizon + MariaDB using Docker images via SCP
# Supports staging/production environments

set -euo pipefail

# ────────────────────────────────────────────────────────────────
# Setup paths
# ────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_ROOT" || exit 1

# ────────────────────────────────────────────────────────────────
# Configuration
# ────────────────────────────────────────────────────────────────
ENVIRONMENT=""
STEP=""
COMPOSE_PROJECT_NAME="uwezo-lms"
SERVICES=(app horizon scheduler)
REMOTE_APP_CONTAINER="uwezo-lms-app"

# ────────────────────────────────────────────────────────────────
# Colors & Logging
# ────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ────────────────────────────────────────────────────────────────
# Parse arguments
# ────────────────────────────────────────────────────────────────
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
            staging|production)
                ENVIRONMENT="$1"
                shift
                ;;
            *)
                log_error "Unknown argument: $1"
                ;;
        esac
    done

    ENVIRONMENT=${ENVIRONMENT:-staging}
    STEP=${STEP:-all}
}

# ────────────────────────────────────────────────────────────────
# Load environment variables (fixed & reliable)
# ────────────────────────────────────────────────────────────────
load_env_vars() {
    if [[ -n "${GITHUB_ACTIONS:-}" ]]; then
        # GitHub Actions: Variables should already be set in environment
        # Use non-prefixed variables (DEPLOY_HOST, DEPLOY_USER, etc.)
        DEPLOY_HOST="${DEPLOY_HOST:-}"
        DEPLOY_USER="${DEPLOY_USER:-}"
        DEPLOY_PATH="${DEPLOY_PATH:-}"
        DEPLOY_KEY="${DEPLOY_KEY:-}"
    else
        # Local deployment: Load from .env file
        if [[ ! -f ".env" ]]; then
            log_error ".env file not found. Please create it with deployment configuration."
            exit 1
        fi

        # Source the .env file
        set -a
        source .env
        set +a

        # For local deployment, REQUIRE environment-prefixed variables
        local env_upper=$(echo "$ENVIRONMENT" | tr '[:lower:]' '[:upper:]')

        DEPLOY_HOST="${env_upper}_DEPLOY_HOST"
        DEPLOY_USER="${env_upper}_DEPLOY_USER"
        DEPLOY_PATH="${env_upper}_DEPLOY_PATH"
        DEPLOY_KEY_PATH="${env_upper}_DEPLOY_KEY_PATH"

        # Get the actual values
        DEPLOY_HOST="${!DEPLOY_HOST:-}"
        DEPLOY_USER="${!DEPLOY_USER:-}"
        DEPLOY_PATH="${!DEPLOY_PATH:-}"
        DEPLOY_KEY_PATH="${!DEPLOY_KEY_PATH:-}"
    fi

    # Check required variables (same for both local and GitHub Actions)
    local missing_vars=()
    [[ -z "$DEPLOY_HOST" ]] && missing_vars+=("DEPLOY_HOST")
    [[ -z "$DEPLOY_USER" ]] && missing_vars+=("DEPLOY_USER")
    [[ -z "$DEPLOY_PATH" ]] && missing_vars+=("DEPLOY_PATH")

    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        if [[ -n "${GITHUB_ACTIONS:-}" ]]; then
            log_error "Missing required environment variables:"
            printf '  %s\n' "${missing_vars[@]}"
            log_error "Please add them as GitHub secrets."
        else
            log_error "Missing required deployment variables:"
            local env_upper=$(echo "$ENVIRONMENT" | tr '[:lower:]' '[:upper:]')
            for var in "${missing_vars[@]}"; do
                echo "  ${env_upper}_${var}"
            done
            log_error "Please add them to your .env file. Example:"
            log_error "  ${env_upper}_DEPLOY_HOST=your-server.com"
            log_error "  ${env_upper}_DEPLOY_USER=deploy"
            log_error "  ${env_upper}_DEPLOY_PATH=/path/to/deployment"
        fi
        exit 1
    fi
}

# ────────────────────────────────────────────────────────────────
# SSH setup
# ────────────────────────────────────────────────────────────────
setup_ssh() {
    if [[ -n "${DEPLOY_KEY:-}" ]]; then
        # GitHub Actions - use provided key
        echo "$DEPLOY_KEY" > /tmp/deploy_key
        chmod 600 /tmp/deploy_key
        SSH_KEY_ARG="-i /tmp/deploy_key"
    elif [[ -n "${DEPLOY_KEY_PATH:-}" ]]; then
        # Developer machine - use specified key path
        SSH_KEY_ARG="-i $DEPLOY_KEY_PATH"
    else
        # Developer machine - use default SSH configuration (ssh-agent, ~/.ssh/config, etc.)
        SSH_KEY_ARG=""
    fi

    # SSH options for automated deployment
    SSH_OPTIONS="-o ConnectTimeout=30 -o ServerAliveInterval=10 -o StrictHostKeyChecking=no"

    SSH_CMD="ssh $SSH_KEY_ARG $SSH_OPTIONS $DEPLOY_USER@$DEPLOY_HOST"
    SCP_CMD="scp $SSH_KEY_ARG $SSH_OPTIONS"

    # Test SSH connection
    if ! $SSH_CMD "echo 'SSH connection successful'" > /dev/null 2>&1; then
        log_error "Failed to connect to $DEPLOY_USER@$DEPLOY_HOST"
        exit 1
    fi
}

# ────────────────────────────────────────────────────────────────
# Core deployment steps
# ────────────────────────────────────────────────────────────────
build_images() {
    log_info "Building frontend assets and Docker images..."
    if [ -f "package.json" ]; then
        npm install && npm run build || log_error "Frontend build failed"
    fi
    docker compose build --no-cache --parallel || log_error "Docker build failed"
}

check_images() {
    log_info "Checking for required Docker images..."
    local missing_images=()
    for SERVICE in "${SERVICES[@]}"; do
        IMAGE_NAME="${COMPOSE_PROJECT_NAME}-${SERVICE}:latest"
        if ! docker image inspect "$IMAGE_NAME" > /dev/null 2>&1; then
            missing_images+=("$IMAGE_NAME")
        fi
    done

    if [[ ${#missing_images[@]} -gt 0 ]]; then
        log_error "Missing Docker images. Please build them first:"
        printf '  %s\n' "${missing_images[@]}"
        log_error "Run: ./scripts/deploy.sh $ENVIRONMENT --step build"
        exit 1
    fi

    log_success "All required images found locally."
}

transfer_images() {
    log_info "Starting image transfer to $DEPLOY_HOST..."

    # 1. Ensure remote directory exists
    $SSH_CMD "mkdir -p $DEPLOY_PATH"

    for SERVICE in "${SERVICES[@]}"; do
        IMAGE_NAME="${COMPOSE_PROJECT_NAME}-${SERVICE}:latest"
        LOCAL_TMP="/tmp/${SERVICE}-image.tar.gz"
        REMOTE_TMP="/tmp/${SERVICE}-image.tar.gz"

        # 2. Save and Compress locally
        log_info "→ Packaging $SERVICE..."
        docker save "$IMAGE_NAME" | gzip > "$LOCAL_TMP"

        # Calculate size for user feedback
        local size=$(du -sh "$LOCAL_TMP" | cut -f1)
        log_info "→ Transferring $SERVICE ($size)..."

        # 3. Transfer with SCP
        # Using $SCP_CMD ensures your keys/options are respected
        $SCP_CMD "$LOCAL_TMP" "$DEPLOY_USER@$DEPLOY_HOST:$REMOTE_TMP"

        # 4. Load on Remote and Cleanup Remote Temp
        log_info "→ Loading $SERVICE on server..."
        $SSH_CMD "zcat $REMOTE_TMP | docker load && rm $REMOTE_TMP"

        # 5. Cleanup Local Temp
        rm "$LOCAL_TMP"
        log_success "✓ $SERVICE transferred and loaded."
    done

    # 6. Final cleanup of dangling images on server
    log_info "Cleaning up old images on server..."
    $SSH_CMD "docker image prune -f >/dev/null 2>&1 || true"
}

transfer_configs() {
    log_info "Uploading docker-compose.yml..."
    $SCP_CMD docker-compose.yml "$DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PATH/"
}

deploy_application() {
    log_info "Deploying on remote server..."

    $SSH_CMD "
        cd $DEPLOY_PATH &&
        docker compose down --remove-orphans --quiet || true &&
        docker compose up -d --remove-orphans --no-build --force-recreate
    " || log_error "Deployment failed on remote"
}

post_deploy() {
    log_info "Running post-deployment tasks..."

    # 1. Fix permissions FIRST so artisan commands actually work
    log_info "→ Fixing file permissions..."
    # $SSH_CMD "docker exec -u root $REMOTE_APP_CONTAINER chown -R www-data:www-data /var/www/html storage bootstrap/cache"
    # $SSH_CMD "docker exec -u root $REMOTE_APP_CONTAINER chmod -R 775 /var/www/html storage bootstrap/cache"


    $SSH_CMD "
        cd $DEPLOY_PATH &&
        docker exec -t $REMOTE_APP_CONTAINER php artisan key:generate &&
        docker exec -t $REMOTE_APP_CONTAINER php artisan optimize:clear &&
        docker exec -t $REMOTE_APP_CONTAINER php artisan optimize &&
        docker exec -t $REMOTE_APP_CONTAINER php artisan horizon:terminate
    " || log_warn "Some post-deploy tasks failed (check logs)"
}

cleanup() {
    [[ -f /tmp/deploy_key ]] && rm -f /tmp/deploy_key
}

# show_help() {
#     cat <<-EOF
#     Usage: $0 [staging|production] [--step build|deploy|post-deploy|all]

#     Examples:
#     $0 staging                  # Full deployment to staging
#     $0 staging --step build     # Build only
#     $0 production --step deploy # Deploy only (assumes images exist)
#         EOF
# }

show_help() {
    echo "UWEZO Deployment Script"
    echo
    echo "Usage: $0 [environment] [--step STEP]"
    echo
    echo "Arguments:"
    echo "  environment             - Target environment (default: staging)"
    echo "  --step STEP             - Which step to run (default: all)"
    echo
    echo "Available steps:"
    echo "  build                   - Build Docker images only"
    echo "  deploy                  - Deploy application (requires pre-built images)"
    echo "  post-deploy             - Run post-deployment tasks only"
    echo "  all                     - Run all steps (build, deploy, post-deploy)"
    echo
    echo "Configuration:"
    echo
    echo "For LOCAL deployment (using .env file):"
    echo "  Required variables MUST be prefixed with environment name:"
    echo "    STAGING_DEPLOY_HOST     - SSH hostname for staging"
    echo "    STAGING_DEPLOY_USER     - SSH username for staging"
    echo "    STAGING_DEPLOY_PATH     - Remote deployment path for staging"
    echo "    STAGING_DEPLOY_KEY_PATH - SSH private key path (optional)"
    echo
    echo "For GITHUB ACTIONS (using secrets):"
    echo "  Variables should NOT be prefixed:"
    echo "    DEPLOY_HOST             - SSH hostname"
    echo "    DEPLOY_USER             - SSH username"
    echo "    DEPLOY_PATH             - Remote deployment path"
    echo "    DEPLOY_KEY              - SSH private key content"
    echo
    echo "IMPORTANT: The .env file is NOT transferred to the server during deployment."
    echo "           You must manually create and maintain the application .env file on the server."
    echo
    echo "Example .env configuration:"
    echo "  STAGING_DEPLOY_HOST=server.example.com"
    echo "  STAGING_DEPLOY_USER=deploy"
    echo "  STAGING_DEPLOY_PATH=/var/www/app"
    echo
    echo "Usage examples:"
    echo "  $0 staging                      # Full deployment to staging"
    echo "  $0 staging --step build         # Build images only"
    echo "  $0 staging --step deploy        # Deploy only (for CI/CD)"
    echo "  $0 staging --step post-deploy   # Post-deployment tasks only"
    echo
}

# ────────────────────────────────────────────────────────────────
# Execute
# ────────────────────────────────────────────────────────────────
parse_args "$@"
load_env_vars
setup_ssh
trap cleanup EXIT

case "$STEP" in
    build)
        build_images
        log_success "Build completed."
        ;;
    deploy)
        check_images
        transfer_images
        transfer_configs
        deploy_application
        log_success "Deployment completed."
        ;;
    post-deploy)
        post_deploy
        log_success "Post-deployment tasks completed."
        ;;
    all)
        build_images
        check_images
        transfer_images
        transfer_configs
        deploy_application
        post_deploy
        log_success "Full deployment successful!"
        ;;
    *)
        log_error "Invalid step: $STEP"
        show_help
        exit 1
        ;;
esac
