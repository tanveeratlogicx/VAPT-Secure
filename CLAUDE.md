# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Commands

1. **Install Dependency**:

   ```bash
   composer install --optimize-autoload
   ```

2. **Run Security Check**:

   ```bash
   php tools/check_balance.php
   ```

3. **Lint PHP Code**:

   ```bash
   php -l includes/*.php
   ```

4. **Test Enforcer Rules**:

   ```bash
   php wp-cli balance check --config data/Enforcers/rewrite-rules.json
   ```

## Code Structure

- **Core Plugin**: `vaptsecure.php` (main entry point)
- **Enforcers**: `includes/enforcers/` (security rule implementations)
- **Drivers**: `includes/enforcers/driver-*/` (platform-specific adaptations)
- **Patterns**: `data/Enforcers/` (configuration files for security patterns)
- **AI Resources**: `.claude/skills/vaptschema-builder/resources/` (AI instruction files)

## Architecture

1. **VAPT Framework Core**
   - Middleware security rules
   - WordPress admin integration
   - AI-powered security analysis

2. **Modular Enforcers**
   - Apache, Nginx, PHP-FPM, Cloudflare
   - DNS and network traffic monitoring
   - Web application firewall rules

3. **Configuration System**
   - Interface Schema v2.0 (security policies)
   - Pattern Library v2.0 (attack signatures)
   - Driver Manifest v2.0 (platform interfaces)

## Skills & Guides

- `/ai/skills/vaptschema-builder/README.md` (AI configuration instructions)
- `/data/WIP/VAPTSecure_Grouped_Architecture-v3/ README.md` (System architecture)
- `data/VAPT_Driver_Reference_v2.0.php` (Platform-specific implementations)

> References

- WordPress Plugin Handbook: wp-content/plugins/plugin-hdbk.pdf
- Apache Security Configuration: data/Enforcers/apache-template.json
- OWASP Top 10 Implementation Guide: data/VAPT-Risk-Catalogues-Copy.zip

> WARNING: Always verify file integrity using `check_balance.php` tool when  modifying security-sensitive components
