# Architecture Guide - Darkheim WebEngine

## Overview

This project follows a modern **Clean Architecture** pattern with **Domain-Driven Design (DDD)** principles, implementing a layered architecture that promotes separation of concerns, testability, and maintainability.

## Project Structure

```
src/
├── Application/        # Application Layer (Use Cases & Controllers)
├── Domain/            # Domain Layer (Business Logic & Models)
└── Infrastructure/    # Infrastructure Layer (External Services)
```

## Architecture Layers

### 1. Domain Layer (`src/Domain/`)
**Purpose**: Contains the core business logic, entities, and domain rules.

**Components**:
- **Models**: Business entities (User, Article, ClientProject, etc.)
- **Interfaces**: Contracts for external dependencies
- **Repositories**: Data access abstractions

**Key Principles**:
- No dependencies on other layers
- Pure business logic
- Framework-agnostic

### 2. Application Layer (`src/Application/`)
**Purpose**: Orchestrates domain objects and implements use cases.

**Components**:
- **Controllers**: Handle HTTP requests and responses
- **Services**: Application-specific business logic
- **Middleware**: Cross-cutting concerns (auth, validation)
- **Core**: Application infrastructure (error handling, routing)

### 3. Infrastructure Layer (`src/Infrastructure/`)
**Purpose**: Implements external concerns and frameworks.

**Components**:
- **Database**: PDO implementations and connection handling
- **Security**: Authentication and authorization implementations
- **Lib**: External library integrations and utilities

## Design Patterns Used

### 1. Dependency Injection
All dependencies are injected through constructors:

```php
class AuthController
{
    public function __construct(
        private AuthenticationInterface $auth,
        private FlashMessageInterface $flashMessage,
        private LoggerInterface $logger
    ) {}
}
```

### 2. Repository Pattern
Data access is abstracted through interfaces:

```php
interface DatabaseInterface
{
    public function getConnection(): PDO;
}
```

### 3. Service Layer Pattern
Business operations are encapsulated in services:

```php
class CommentService
{
    public function createComment(array $data): array;
    public function moderateComment(int $commentId, string $status): array;
}
```

### 4. Middleware Pattern
Cross-cutting concerns are handled via middleware:

```php
class ClientAreaMiddleware
{
    public function handle(): bool;
}
```

## Coding Standards

### 1. Strict Types
All PHP files use strict typing:

```php
declare(strict_types=1);
```

### 2. Type Declarations
All methods have explicit parameter and return types:

```php
public function authenticate(string $identifier, string $password): AuthResult
```

### 3. Readonly Classes
Immutable objects use readonly modifier:

```php
readonly class AuthController
```

### 4. Interface Segregation
Small, focused interfaces:

```php
interface FlashMessageInterface
{
    public function addSuccess(string $message): void;
    public function addError(string $message): void;
}
```

## Database Layer

### Connection Management
- Single PDO connection through DatabaseInterface
- Connection reuse across requests
- Proper error handling and logging

### Models
Models are responsible for:
- Data validation
- Business rules enforcement
- Database operations (CRUD)

Example:
```php
class User
{
    public static function findById(DatabaseInterface $db, int $id): ?array;
    public function save(): bool;
    public function validate(): array;
}
```

## Error Handling

### Centralized Error Handler
All errors flow through `Application/Core/ErrorHandler.php`:

```php
class ErrorHandler
{
    public function handleException(Throwable $exception): void;
    public function handleError(int $severity, string $message): void;
}
```

### Logging Strategy
- Structured logging with Monolog
- Different log levels (DEBUG, INFO, WARNING, ERROR, CRITICAL)
- Contextual information for debugging

## Security Implementation

### Authentication Flow
1. User submits credentials
2. AuthenticationInterface validates
3. Session is established
4. Remember token (optional) is created

### Authorization
- Role-based access control (RBAC)
- Middleware-based route protection
- Permission checking at controller level

### CSRF Protection
All forms protected with CSRF tokens:

```php
private function validateCSRF(): bool
{
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
```

## File Organization Guidelines

### Controllers
- One controller per functional area
- RESTful method naming (create, update, delete)
- Dependency injection in constructor
- Return arrays for API responses

### Models
- Active Record pattern with static factory methods
- Validation in model methods
- Database operations encapsulated
- Proper error handling

### Services
- Business logic that doesn't belong in models
- Coordinate between multiple models
- Handle complex operations
- Return structured responses

## Development Workflow

### 1. Creating New Features
1. Define interfaces in Domain layer
2. Implement models with business rules
3. Create controllers in Application layer
4. Add routes and middleware
5. Implement infrastructure services

### 2. Database Changes
1. Create migration SQL file in `database/`
2. Update model classes
3. Add/update repository methods
4. Test with PHPStan

### 3. Adding New Endpoints
1. Create controller method
2. Add route in `config/routes_config.php`
3. Implement middleware if needed
4. Add API documentation

## Quality Assurance

### Static Analysis
PHPStan Level 6 enabled with custom configuration:

```bash
composer phpstan
```

### Code Quality Checks
- Strict type checking
- Unused code detection
- Method signature validation
- Property type verification

## Common Patterns for AI Developers

### 1. Creating New Controllers

```php
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use Exception;

class NewController {
    private DatabaseInterface $db_handler;
    
    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }
    
    public function actionMethod(): array {
        try {
            // Implementation
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            error_log("Error in actionMethod: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }
}
```

### 2. Adding New Models

```php
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use Exception;

class NewModel {
    private DatabaseInterface $db_handler;
    
    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
    }
    
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
}
```

### 3. Implementing Services

```php
declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;

class NewService {
    public function __construct(
        private DatabaseInterface $db_handler,
        private LoggerInterface $logger
    ) {}
    
    public function performOperation(array $data): array {
        try {
            // Business logic here
            $this->logger->info("Operation completed", ['data' => $data]);
            return ['success' => true];
        } catch (Exception $e) {
            $this->logger->error("Operation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Operation failed'];
        }
    }
}
```

## Testing Strategy

### PHPStan Integration
- Level 6 static analysis
- Custom rules for architecture compliance
- Automated error detection

### Manual Testing
- API endpoint testing
- Database operation verification
- Security validation

## Performance Considerations

### Database Optimization
- Prepared statements for all queries
- Connection reuse
- Query result caching where appropriate

### Memory Management
- Proper resource cleanup
- Avoiding memory leaks in long-running processes
- Efficient data structures

## Maintenance Guidelines

### Code Reviews
- Architecture compliance check
- Security review
- Performance impact assessment

### Documentation Updates
- Keep architecture docs current
- Update API documentation
- Maintain changelog

## Troubleshooting Common Issues

### PHPStan Errors
- Missing imports: Add `use` statements
- Method on array: Check return types from models
- Unused properties: Remove or mark as used

### Database Issues
- Connection errors: Check DatabaseInterface implementation
- Query failures: Verify table structure matches model expectations

This architecture guide provides a foundation for understanding and extending the Darkheim WebEngine project while maintaining code quality and architectural integrity.
