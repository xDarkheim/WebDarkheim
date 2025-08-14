# AI Developer Guide - Darkheim WebEngine

## 🤖 Welcome, AI Developer!

This comprehensive guide will help you understand and work with the Darkheim WebEngine architecture efficiently. The project follows modern PHP practices with Clean Architecture principles.

## 📚 Documentation Structure

### 1. [Architecture Overview](ARCHITECTURE.md)
**Start here for overall understanding**
- Project structure and layers
- Design patterns used
- Coding standards
- Development workflow
- Quality assurance guidelines

### 2. [API Development Guide](API_DEVELOPMENT.md)
**Essential for creating new endpoints**
- Controller patterns and structure
- Middleware usage
- Input validation
- Error handling
- Response formats
- Authentication integration

### 3. [Database & Models Guide](DATABASE_MODELS.md)
**Master data layer operations**
- Model development patterns
- CRUD operations
- Advanced queries and joins
- Transaction handling
- Validation and sanitization
- Performance optimization

### 4. [Security & Middleware Guide](SECURITY_MIDDLEWARE.md)
**Critical security implementation**
- Authentication and authorization
- CSRF protection
- Session management
- Input sanitization
- XSS/SQL injection prevention
- Rate limiting

## 🚀 Quick Start for AI Developers

### Step 1: Understand the Architecture
```
src/
├── Application/     # Controllers, Services, Middleware
├── Domain/         # Models, Interfaces, Business Logic
└── Infrastructure/ # Database, Security, External Services
```

### Step 2: Follow the Patterns

**Creating a new API endpoint:**
```php
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Application\Middleware\ClientAreaMiddleware;
use Exception;

class NewController {
    private DatabaseInterface $db_handler;
    private ClientAreaMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
        $this->middleware = new ClientAreaMiddleware($db_handler);
    }

    public function action(): array {
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            // Your logic here
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            error_log("NewController::action() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }
}
```

### Step 3: Use Standard Response Format
```php
// Success response
return [
    'success' => true,
    'data' => $responseData,
    'message' => 'Operation completed successfully'
];

// Error response
return [
    'success' => false,
    'error' => 'Human-readable error message'
];
```

## 🛠️ Essential Patterns for AI

### 1. **Always Import Exception**
```php
use Exception;
```

### 2. **Database Operations Pattern**
```php
public static function findById(DatabaseInterface $db, int $id): ?array {
    try {
        $stmt = $db->getConnection()->prepare("SELECT * FROM table WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log("Error finding record: " . $e->getMessage());
        return null;
    }
}
```

### 3. **Input Validation Pattern**
```php
$title = trim($_POST['title'] ?? '');
if (empty($title)) {
    return ['success' => false, 'error' => 'Title is required'];
}
```

### 4. **Middleware Protection Pattern**
```php
if (!$this->middleware->handle()) {
    return ['success' => false, 'error' => 'Access denied'];
}
```

## 📁 Project Structure Reference

### Key Directories:
- **`page/api/`** - API endpoints organized by functionality
- **`src/Application/Controllers/`** - Business logic controllers
- **`src/Domain/Models/`** - Data models and business entities
- **`src/Application/Middleware/`** - Access control and validation
- **`config/`** - Configuration files
- **`database/`** - SQL migrations and schema

### Important Files:
- **`composer.json`** - Dependencies and autoloading
- **`phpstan.neon`** - Static analysis configuration
- **`includes/bootstrap.php`** - Application initialization

## 🔧 Development Tools

### PHPStan Analysis
```bash
composer phpstan
```
**Current Status**: 21 errors remaining (74% reduction achieved)

### Quality Standards
- **PHP 8.4+** with strict types
- **PSR-4 autoloading**
- **Dependency injection**
- **Interface-based architecture**
- **Prepared statements only**

## 🚨 Critical Security Rules

### ✅ Always Do:
1. Use `declare(strict_types=1);`
2. Validate all input with trim() and empty() checks
3. Use prepared statements for database queries
4. Check middleware permissions first
5. Return consistent array formats
6. Log exceptions with context
7. Sanitize output with htmlspecialchars()

### ❌ Never Do:
1. Use string concatenation in SQL queries
2. Skip input validation
3. Ignore middleware checks
4. Return raw data without success/error structure
5. Use global variables
6. Skip error handling

## 📊 Current Architecture Health

### ✅ Strengths:
- Clean separation of concerns
- Consistent error handling
- Proper dependency injection
- Security-first approach
- Comprehensive middleware system

### 🔄 Areas for Improvement:
- Continue PHPStan error reduction
- Enhance test coverage
- Optimize database queries
- Improve caching strategies

## 🎯 Common Tasks for AI

### Adding a New Feature:
1. **Define the interface** in `Domain/Interfaces/`
2. **Create the model** in `Domain/Models/`
3. **Implement controller** in `Application/Controllers/`
4. **Add API endpoint** in `page/api/`
5. **Configure middleware** for access control
6. **Test with PHPStan**

### Debugging Issues:
1. Check PHPStan output for type errors
2. Verify middleware is properly configured
3. Ensure database connections are working
4. Review error logs for exceptions
5. Validate input/output formats

### Performance Optimization:
1. Use proper database indexing
2. Implement connection pooling
3. Add caching where appropriate
4. Optimize query patterns
5. Profile critical paths

## 📞 Emergency Troubleshooting

### Common PHPStan Errors:
- **Missing imports**: Add `use Exception;` or other imports
- **Method on array**: Check model return types (arrays vs objects)
- **Undefined methods**: Verify model has required methods
- **Type mismatches**: Ensure proper type declarations

### Database Issues:
- Verify table structure matches model expectations
- Check connection configuration in `config/config.php`
- Ensure prepared statements are used correctly
- Validate SQL syntax and parameters

### Authentication Problems:
- Check session configuration and security headers
- Verify middleware is properly instantiated
- Ensure CSRF tokens are included in forms
- Validate user roles and permissions

## 🎓 Learning Path

### For New AI Developers:
1. **Week 1**: Read ARCHITECTURE.md thoroughly
2. **Week 2**: Study API_DEVELOPMENT.md and practice with simple endpoints
3. **Week 3**: Master DATABASE_MODELS.md with CRUD operations
4. **Week 4**: Implement SECURITY_MIDDLEWARE.md patterns

### For Experienced AI Developers:
1. Review architecture patterns
2. Focus on security implementation
3. Optimize existing code for PHPStan compliance
4. Extend functionality following established patterns

## 🔮 Future Development

### Roadmap Considerations:
- API versioning strategy
- Enhanced caching layer
- Background job processing
- Real-time notifications
- Microservices architecture migration

### Technology Upgrades:
- PHP 8.5+ features adoption
- Enhanced type system usage
- Performance monitoring tools
- Automated testing framework
- CI/CD pipeline integration

---

**Remember**: This architecture prioritizes **security**, **maintainability**, and **developer experience**. Always follow the established patterns and consult the specific guides for detailed implementation instructions.

**Happy coding! 🚀**
