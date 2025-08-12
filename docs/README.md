# Darkheim.net - Modern Web Application Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%205-brightgreen.svg)](https://phpstan.org/)
[![Architecture](https://img.shields.io/badge/Architecture-Clean%20Architecture-green.svg)](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

## 📋 Table of Contents

1. [Project Overview](#-project-overview)
2. [Current Status (August 2025)](#-current-status-august-2025)
3. [Architecture](#-architecture)
4. [Features](#-features)
5. [Installation & Setup](#-installation--setup)
6. [Development](#-development)
7. [Security Features](#-security-features)
8. [System Monitoring](#-system-monitoring)
9. [Backup System](#-backup-system)
10. [Testing](#-testing)
11. [Known Issues & Future Work](#-known-issues--future-work)

---

## 🎯 Project Overview

**Darkheim.net** is a production-ready, enterprise-grade web application platform built with modern PHP architecture following Clean Architecture principles, SOLID design patterns, and industry best practices. The system serves as a content management platform with advanced user management, security features, and comprehensive monitoring capabilities.

### Key Highlights

- ✅ **Clean Architecture** with clear separation of concerns
- ✅ **Dependency Injection** container for loose coupling
- ✅ **Zero PHPStan Errors** (Level 5 static analysis)
- ✅ **Enterprise Security** with CSRF protection and rate limiting
- ✅ **Automated Backup System** with email notifications
- ✅ **Admin Dashboard** with system monitoring
- ✅ **User Registration/Authentication** with email verification
- ✅ **Content Management** with moderation system
- ✅ **API Integration** for AJAX operations
- ✅ **Responsive UI** with modern design

---

## 🚀 Current Status (August 2025)

### ✅ Completed Features

#### Authentication & User Management
- **User Registration System** with email verification
- **Login/Logout** with secure session management
- **Password Reset** functionality via email
- **Email Change** with verification process
- **User Profiles** with customizable settings
- **Role-based Access Control** (Admin/User roles)

#### Content Management System
- **News Management** with categories
- **Comment System** with moderation
- **Static Pages** (About, Services, Contact, Team, etc.)
- **User Dashboard** with content management
- **Admin Panel** for full system control

#### Security & Infrastructure
- **CSRF Protection** on all forms
- **Input Validation** and sanitization
- **Rate Limiting** to prevent abuse
- **Secure Token Management** for verification
- **Flash Message System** for user feedback
- **Comprehensive Logging** with Monolog

#### Backup & Monitoring
- **Automated Database Backup** system with cron scheduling
- **Manual Backup** functionality from admin panel
- **Backup Management** (view, download, delete old backups)
- **Email Notifications** for backup status
- **System Monitor** with server metrics
- **Log Management** and monitoring

#### API & AJAX Features
- **RESTful API** for form submissions
- **AJAX-powered** admin operations
- **JSON API** for backup operations
- **Real-time** feedback systems

### 🏗️ Architecture Implementation

The project successfully implements **Clean Architecture** with:

```
src/
├── Application/                    # Application Layer
│   ├── Controllers/               # Form and API controllers
│   │   ├── AuthController.php    # Authentication logic
│   │   ├── DatabaseBackupController.php  # Backup management
│   │   ├── CommentController.php # Comment management
│   │   ├── ModerationController.php # Content moderation
│   │   └── *FormController.php   # Form handling controllers
│   ├── Core/                     # Core application components
│   │   ├── Application.php       # Main application class
│   │   ├── Container.php         # DI container
│   │   ├── ServiceProvider.php   # Service factory
│   │   └── Router.php           # Request routing
│   ├── Services/                # Business logic services
│   │   ├── UserRegistrationService.php
│   │   ├── AuthenticationService.php
│   │   ├── PasswordManager.php
│   │   └── EmailService.php
│   └── Middleware/              # Application middleware
├── Domain/                      # Domain Layer
│   ├── Interfaces/             # Business contracts
│   │   ├── UserRegistrationInterface.php
│   │   ├── AuthServiceInterface.php
│   │   ├── DatabaseInterface.php
│   │   └── *Interface.php
│   ├── Models/                 # Domain models
│   │   ├── User.php
│   │   ├── Comment.php
│   │   └── Category.php
│   └── Collections/            # Domain collections
└── Infrastructure/             # Infrastructure Layer
    ├── Lib/                   # External libraries integration
    │   ├── FlashMessageService.php
    │   ├── MailerService.php
    │   └── DatabaseHandler.php
    ├── Components/            # UI components
    └── Middleware/           # Infrastructure middleware
        ├── AuthMiddleware.php
        └── AdminMiddleware.php
```

### 📁 File Structure

```
darkheim.net/
├── config/                    # Configuration
│   ├── config.php            # Main config with DB, email, security
│   └── routes_config.php     # Complete route definitions
├── database/                 # Database schemas and migrations
│   ├── database_setup.sql    # Main database schema
│   ├── add_backup_settings.sql
│   ├── add_moderation_system.sql
│   └── insert_settings_data.sql
├── page/                     # Page controllers and views
│   ├── admin/               # Admin interface
│   │   ├── backup_monitor.php    # Backup management UI
│   │   ├── system_monitor.php    # System monitoring
│   │   ├── manage_users.php      # User management
│   │   └── site_settings.php     # Site configuration
│   ├── auth/                # Authentication pages
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── verify_email.php
│   │   └── reset_password.php
│   ├── public/              # Public pages
│   │   ├── home.php
│   │   ├── news.php
│   │   ├── contact.php
│   │   └── about.php
│   ├── user/                # User dashboard
│   │   ├── dashboard.php
│   │   └── profile/
│   └── api/                 # API endpoints
│       └── manual_backup.php
├── public/                  # Web-accessible files
│   ├── assets/             # Static assets (CSS, JS, images)
│   └── webengine.php       # Alternative entry point
├── scripts/                # CLI scripts
│   ├── auto_backup.php     # Automated backup script
│   ├── backup.php          # Manual backup script
│   └── install_backup_cron.sh  # Cron installation
├── storage/                # Application storage
│   ├── backups/           # Database backups
│   ├── logs/              # Application logs
│   ├── cache/             # Cache files
│   └── uploads/           # User uploads
├── resources/             # Templates and resources
│   └── views/             # Template partials
├── themes/                # UI themes
│   └── default/           # Default theme
└── tests/                 # Test suite
    ├── Unit/              # Unit tests
    └── Integration/       # Integration tests
```

---

## ✨ Features

### User Management
- 🔐 **Secure Authentication** with bcrypt password hashing
- 📧 **Email Verification** system with token-based validation
- 🔄 **Password Reset** with secure token delivery
- 👤 **User Profiles** with editable information
- 🛡️ **Role-based Access Control** (Admin/User)
- 📝 **Registration** with comprehensive validation

### Content Management
- 📰 **News System** with category management
- 💬 **Comment System** with user interaction
- 🔍 **Content Moderation** with admin approval workflow
- 📄 **Static Pages** (About, Services, Contact, Team, Careers)
- 🎨 **Responsive UI** with modern design
- 📱 **Mobile-first** approach

### Admin Dashboard
- 👥 **User Management** (view, edit, activate/deactivate)
- 📊 **System Monitoring** with server metrics
- 💾 **Backup Management** with automated scheduling
- 🔧 **Site Settings** configuration
- 📝 **Comment Moderation** tools
- 📈 **Activity Logging** and monitoring

### Backup System
- ⏰ **Automated Backups** via cron (daily at 2:00 AM)
- 🔧 **Manual Backup** creation from admin panel
- 📧 **Email Notifications** for backup status
- 🗂️ **Backup Management** (view, download, delete)
- 🔐 **Secure Storage** in `/storage/backups/`
- 📊 **Backup Monitoring** with file sizes and dates

### Security Features
- 🛡️ **CSRF Protection** on all forms
- ⏱️ **Rate Limiting** to prevent abuse
- 🔒 **Secure Token Management** for verifications
- 📝 **Input Validation** and sanitization
- 🔐 **Secure Session Handling**
- 🛡️ **Admin-only Areas** with middleware protection

---

## ⚙️ Installation & Setup

### Prerequisites

- **PHP 8.2+** with extensions: PDO, PDO_MySQL, JSON, MBString, OpenSSL
- **MySQL/MariaDB 10.4+**
- **Composer 2.0+**
- **Web Server**: Apache 2.4+/Nginx 1.18+ with rewrite support
- **Cron** access for automated backups

### Quick Start

1. **Clone Repository**
   ```bash
   git clone <repository-url> darkheim.net
   cd darkheim.net
   ```

2. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE darkheim_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Import schema and data
   mysql -u root -p darkheim_db < database/database_setup.sql
   mysql -u root -p darkheim_db < database/insert_settings_data.sql
   mysql -u root -p darkheim_db < database/add_backup_settings.sql
   mysql -u root -p darkheim_db < database/add_moderation_system.sql
   ```

4. **Configuration**
   ```bash
   # Edit configuration file
   nano config/config.php
   ```
   
   Configure:
   - Database credentials
   - Email settings (SMTP)
   - Site URL and name
   - Security keys
   - Backup settings

5. **Set Permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 storage/logs/
   chmod -R 755 storage/cache/
   chmod -R 755 storage/uploads/
   chmod -R 755 storage/backups/
   ```

6. **Install Backup Cron** (Optional)
   ```bash
   chmod +x scripts/install_backup_cron.sh
   ./scripts/install_backup_cron.sh
   ```

### Web Server Configuration

**Apache (.htaccess already included)**:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx**:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

---

## 👨‍💻 Development

### Development Setup

```bash
# Install dev dependencies
composer install

# Run tests
composer test

# Static analysis
./vendor/bin/phpstan analyse

# Code style check
./vendor/bin/phpcs --standard=PSR12 src/
```

### Adding New Features

1. **Create Interface** (Domain layer)
2. **Implement Service** (Application layer)
3. **Create Controller** (Application layer)
4. **Register Route** in `config/routes_config.php`
5. **Add Tests** in `tests/`

### Code Standards

- **PSR-12** coding standards
- **Strict typing** (`declare(strict_types=1)`)
- **PHPStan Level 5** compliance
- **Comprehensive PHPDoc** documentation
- **Interface-driven** development

---

## 🛡️ Security Features

### Authentication Security
- **bcrypt** password hashing
- **Secure token** generation for verification
- **Session regeneration** after login
- **Rate limiting** on login attempts
- **Email verification** for new accounts

### Application Security
- **CSRF tokens** on all forms
- **Input validation** and sanitization
- **SQL injection** prevention via PDO
- **XSS protection** via output escaping
- **Admin middleware** for protected areas

---

## 📊 System Monitoring

### Available Monitors
- **Server Information** (PHP version, memory, disk space)
- **Database Status** (connection, table info)
- **Log File Monitoring** (error logs, application logs)
- **Backup Status** (last backup, file sizes)
- **System Health** checks

### Access
Visit `/index.php?page=system_monitor` (Admin only)

---

## 💾 Backup System

### Automated Backups
- **Schedule**: Daily at 2:00 AM via cron
- **Retention**: Keeps last 30 backups
- **Compression**: gzip compression for space efficiency
- **Notifications**: Email alerts for success/failure

### Manual Backups
- Admin panel interface at `/index.php?page=backup_monitor`
- **Create**: On-demand backup creation
- **Download**: Direct download of backup files
- **Delete**: Remove old backup files
- **Cleanup**: Bulk removal of old backups

### Backup Features
- **Full database** backup with structure and 
- **Automatic cleanup** of old backups
- **Email notifications** to administrators
- **Secure storage** in protected directory
- **File integrity** validation

---

## 🧪 Testing

### Test Coverage
- **Unit Tests**: Core business logic
- **Integration Tests**: Database operations
- **Controller Tests**: Request handling
- **Service Tests**: Business services

### Running Tests
```bash
# All tests
composer test

# Specific test suite
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Integration/
```

---

## ⚠️ Known Issues & Future Work

### Current Limitations
1. **Theme System**: Only default theme implemented
2. **File Upload**: Basic implementation, needs enhancement
3. **Search**: Not implemented yet
4. **API Documentation**: Needs Swagger/OpenAPI docs
5. **Caching**: Basic file caching, could benefit from Redis

### Future Enhancements
1. **Multi-theme Support**: Theme switching system
2. **Advanced Search**: Full-text search with filters
3. **File Management**: Enhanced upload/gallery system
4. **API Expansion**: RESTful API for mobile apps
5. **Performance**: Redis caching, database optimization
6. **Localization**: Multi-language support
7. **Analytics**: Built-in analytics dashboard

### Deployment Considerations
- **Environment Variables**: Move sensitive config to .env
- **Docker Support**: Add containerization
- **CI/CD Pipeline**: Automated testing and deployment
- **Monitoring**: Application performance monitoring
- **Scaling**: Database clustering for high traffic

---

## 📞 Support & Maintenance

### Logging
- **Location**: `/storage/logs/`
- **Rotation**: Automatic log rotation
- **Levels**: Emergency, Alert, Critical, Error, Warning, Notice, Info, Debug

### Maintenance Scripts
- `scripts/auto_backup.php` - Automated backup script
- `scripts/clear_logs.php` - Log cleanup utility
- `scripts/maintenance.php` - General maintenance tasks

### Monitoring Commands
```bash
# Check backup status
php scripts/auto_backup.php --status

# Clear old logs
php scripts/clear_logs.php --days=30

# System maintenance
php scripts/maintenance.php --full
```

---

## 🤝 Contributing

This project follows Clean Architecture principles and SOLID design patterns. When contributing:

1. Maintain **interface contracts**
2. Follow **PSR-12** coding standards
3. Add **comprehensive tests**
4. Update **documentation**
5. Ensure **PHPStan Level 5** compliance

---

## 📄 License

This project is licensed under the MIT License. See LICENSE file for details.

---

**Last Updated**: August 8, 2025
**Version**: 1.0.0 (Production Ready)
**Status**: ✅ Active Development Complete - Maintenance Mode
