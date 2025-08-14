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
7. [Documentation](#-documentation)
8. [Security Features](#-security-features)
9. [System Monitoring](#-system-monitoring)
10. [Testing](#-testing)
11. [Known Issues & Future Work](#-known-issues--future-work)

---

## 🎯 Project Overview

**Darkheim.net** is a production-ready, enterprise-grade web application platform built with modern PHP architecture following Clean Architecture principles, SOLID design patterns, and industry best practices. The system serves as a content management platform with advanced user management, security features, and comprehensive monitoring capabilities.

### Key Highlights

- ✅ **Clean Architecture** with clear separation of concerns
- ✅ **Dependency Injection** container for loose coupling
- ✅ **PHPStan Level 6** static analysis (21 errors remaining - 74% improvement)
- ✅ **Enterprise Security** with CSRF protection and rate limiting
- ✅ **Automated Backup System** with email notifications
- ✅ **Admin Dashboard** with system monitoring
- ✅ **User Registration/Authentication** with email verification
- ✅ **Client Portfolio System** with moderation workflow
- ✅ **Support Ticket System** for client communication
- ✅ **API Integration** for AJAX operations
- ✅ **Responsive UI** with modern design

### 📊 Project Progress: **75% Complete** (8 of 12 phases)

---

## 🚀 Current Status (August 2025)

### ✅ Completed Features

#### Authentication & User Management
- **Role-Based Access Control** - 4 roles with 20 granular permissions
- **User Registration System** with email verification
- **Login/Logout** with secure session management and remember me
- **Password Reset** functionality via email
- **Email Change** with verification process
- **Middleware Security** - automatic route protection

#### Client Portfolio System
- **Extended Client Profiles** with skills, social links, company info
- **Project Portfolio** - create, edit, manage client projects
- **Moderation Workflow** - admin approval system for client projects
- **Image Management** - upload, validation, storage system
- **Statistics & Analytics** - project views, engagement metrics

#### Content Management
- **Article System** - admin/employee only content creation
- **Comment System** - threaded comments with moderation
- **Category Management** - organized content classification
- **Draft System** - save and publish workflow

#### Administrative Features
- **Admin Dashboard** - comprehensive system overview
- **User Management** - role assignment, user administration
- **Moderation Panel** - project and comment approval workflow
- **System Monitoring** - performance metrics, error tracking
- **Backup Management** - automated daily backups with email notifications

#### Client Portal
- **Support Ticket System** ✅ - create, track, manage support requests
- **Project Management** 🔄 - (planned) client view of studio projects
- **Invoice System** 🔄 - (planned) billing and payment tracking
- **Document Management** 🔄 - (planned) project files and contracts

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

## 📚 Documentation

### Complete Documentation Structure

This project maintains comprehensive documentation organized by category:

#### **🏗️ Architecture Documentation** (`architecture/`)
- **[Architecture Overview](architecture/ARCHITECTURE.md)** - Clean Architecture implementation with DDD
- **[API Development Guide](architecture/API_DEVELOPMENT.md)** - RESTful API patterns and security
- **[Database & Models](architecture/DATABASE_MODELS.md)** - Data layer patterns and ORM usage
- **[Security & Middleware](architecture/SECURITY_MIDDLEWARE.md)** - Authentication and authorization

#### **🛠️ Developer Documentation** (`development/`)
- **[AI Developer Guide](development/AI_DEVELOPER_GUIDE.md)** - Complete onboarding for AI developers
- **[Original Task Instructions](development/Task.md)** - System redesign specifications

#### **📊 Development Phases** (`phases/`)
- **[Phase 1: Roles & Permissions](phases/phase1_roles_permissions.md)** ✅ 100% - RBAC system
- **[Phase 2: Middleware & Security](phases/phase2_middleware.md)** ✅ 100% - Route protection  
- **[Phase 3: Client Profiles](phases/phase3_client_profiles.md)** ✅ 100% - Extended profiles
- **[Phase 4: Portfolio System](phases/phase4_portfolio_system.md)** ✅ 100% - Backend system
- **[Phase 5: User Interfaces](phases/phase5_user_interfaces.md)** ✅ 100% - Portfolio UI
- **[Phase 6: Comment System](phases/phase6_comments.md)** ✅ 100% - Threaded comments
- **[Phase 7: Admin Moderation](phases/phase7_admin_moderation.md)** ✅ 100% - Moderation tools
- **[Phase 8: Client Portal](phases/phase8_client_portal.md)** 🔄 20% - Ticket system complete

#### **📋 Project Documentation**
- **[Documentation Index](INDEX.md)** - Main documentation entry point
- **[Project Changelog](CHANGELOG.md)** - Development history and progress
- **Current File (README.md)** - Project overview and setup guide

### **🎯 For Different Users:**
- **New Developers**: Start with [INDEX.md](INDEX.md) → [Architecture Overview](architecture/ARCHITECTURE.md)
- **AI Developers**: Go directly to [AI Developer Guide](development/AI_DEVELOPER_GUIDE.md)
- **Project Managers**: Review [Changelog](CHANGELOG.md) and phase documentation
- **System Administrators**: Focus on installation sections and [Security Guide](architecture/SECURITY_MIDDLEWARE.md)

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
