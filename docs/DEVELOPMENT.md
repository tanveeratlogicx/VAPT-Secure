# VAPT Secure - Development Setup Guide

## Quick Start

Get a local development environment running in under 5 minutes.

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Docker Compose)
- [Git](https://git-scm.com/)
- PHP 8.1+ (for local development without Docker)
- Node.js 18+ and npm

---

## Option 1: Docker (Recommended)

The fastest path to a working development environment.

### 1. Clone and Navigate

```bash
git clone <repository-url>
cd vapt-secure
```

### 2. Start the Environment

```bash
# Start all services
docker-compose up -d

# Or specify a build (first time only)
docker-compose up -d --build
```

### 3. Access Your Dev Environment

| Service | URL | Credentials |
|---------|-----|-------------|
| WordPress | http://localhost:8080 | Set during install |
| phpMyAdmin | http://localhost:8081 | vaptsecure_user / vaptsecure_pass |
| MailHog | http://localhost:8025 | - |

### 4. Complete WordPress Setup

1. Visit http://localhost:8080
2. Follow the WordPress installation wizard
3. **Important**: After WP install, activate the VAPT Secure plugin:
   - Go to Plugins → Installed Plugins
   - Find "VAPT Secure" and click Activate

### 5. Stop the Environment

```bash
# Stop all containers
docker-compose down

# Stop and remove volumes (⚠️ deletes database data)
docker-compose down -v
```

### Docker Commands Reference

```bash
# View logs
docker-compose logs -f wordpress
docker-compose logs -f db

# Run WP-CLI commands
docker-compose exec wordpress wp option list --allow-root

# Access shell in WordPress container
docker-compose exec wordpress bash

# Restart a service
docker-compose restart wordpress

# Update WordPress image
docker-compose pull
docker-compose up -d
```

---

## Option 2: Local WordPress Installation

Use this if you prefer developing with a local web server (XAMPP, MAMP, Local, etc.)

### 1. Install Dependencies

```bash
# PHP dependencies (Composer)
composer install --optimize-autoload

# JavaScript dependencies (npm)
npm install
```

### 2. Configure WordPress

Copy `.env.example` to `.env` and update settings:

```bash
cp .env.example .env
```

Edit `.env` with your local database credentials.

### 3. Install in WordPress

1. Copy the entire `vapt-secure` folder to `/wp-content/plugins/`
2. Go to Plugins in WordPress admin
3. Activate "VAPT Secure"

---

## Development Workflow

### Code Style & Quality

```bash
# PHP CodeSniffer (check coding standards)
composer run lint

# PHP CodeSniffer (auto-fix issues)
composer run lint:fix

# PHPStan (static analysis)
composer run analyze

# JavaScript linting
npm run lint:js
npm run lint:js:fix
```

### Testing

```bash
# PHP Unit Tests
composer run test
composer run test:coverage

# JavaScript Tests
npm test
npm run test:coverage
npm run test:watch
```

---

## Project Structure

```
vapt-secure/
├── assets/                 # Frontend assets
│   ├── js/                # JavaScript files
│   └── css/               # Stylesheets
├── data/                  # Data files (JSON schemas, patterns)
├── docker/                # Docker configuration
│   ├── init-scripts/      # Database initialization
│   └── php.ini           # PHP overrides
├── docs/                  # Documentation
│   ├── API.md            # REST API documentation
│   ├── DEVELOPMENT.md    # This file
│   └── ADRs/             # Architecture Decision Records
├── includes/              # PHP backend classes
│   ├── class-*.php       # Core plugin classes
│   └── enforcers/        # Security driver implementations
├── tests/                 # PHPUnit tests
├── vaptsecure.php         # Main plugin file
├── composer.json          # PHP dependencies
├── docker-compose.yml     # Docker configuration
├── package.json           # Node.js dependencies
└── README.md
```

---

## Key Development Commands

| Task | Command |
|------|---------|
| Install PHP deps | `composer install` |
| Install JS deps | `npm install` |
| PHP linting | `composer run lint` |
| PHP fix | `composer run lint:fix` |
| Static analysis | `composer run analyze` |
| PHP tests | `composer run test` |
| JS tests | `npm test` |
| Start Docker | `docker-compose up -d` |
| Stop Docker | `docker-compose down` |

---

## Debugging

### Enable Debug Mode

1. In Docker: Already enabled via `docker-compose.yml`
2. Local: Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);

// VAPT Secure specific
define('VAPTSECURE_DEBUG', true);
```

### View Logs

```bash
# WordPress debug log (in Docker)
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log

# PHP error log (local Apache/Nginx)
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

### Browser DevTools

- Enable SCRIPT_DEBUG to load unminified assets
- Look for `vapt-` prefixed console logs
- REST API responses available at `/wp-json/vaptsecure/v1/`

---

## Troubleshooting

### Docker Issues

**Port already in use**
```bash
# Check what's using port 8080
lsof -i :8080  # macOS/Linux
netstat -ano | findstr :8080  # Windows

# Change ports in docker-compose.yml
ports:
  - "8090:80"  # Use port 8090 instead
```

**Database connection failed**
```bash
# Wait for database to be ready
docker-compose up -d
docker-compose logs -f db

# Or delete and recreate containers
docker-compose down -v
docker-compose up -d
```

**Permission denied on plugin files**
```bash
# Fix permissions in Docker
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content/plugins/vaptsecure
```

### Local Installation Issues

**Composer not found**
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**npm install fails**
```bash
# Clear npm cache
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

---

## Contributing

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Make changes with tests
3. Run all quality checks: `composer run lint && composer run analyze && npm test`
4. Commit with descriptive messages
5. Push and create a Pull Request

---

## Additional Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [REST API Handbook](https://developer.wordpress.org/rest-api/)
- [CLAUDE.md](/CLAUDE.md) - AI development context
