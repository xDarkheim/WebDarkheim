# API Development Guide - Darkheim WebEngine

## Overview

This guide covers the development of API endpoints following the established architectural patterns in the Darkheim WebEngine project.

## API Structure

All API endpoints are located in `page/api/` and organized by functional areas:

```
page/api/
├── admin/          # Admin-only endpoints
├── auth/           # Authentication endpoints
├── client/         # Client area endpoints
├── comments/       # Comment system endpoints
├── moderation/     # Content moderation endpoints
├── portfolio/      # Portfolio management endpoints
├── system/         # System utilities endpoints
└── tickets/        # Support ticket endpoints
```

## Controller Pattern

### Standard Controller Structure

```php
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\DatabaseInterface;
use App\Application\Middleware\ClientAreaMiddleware;
use Exception;

class ExampleController {
    private DatabaseInterface $db_handler;
    private ClientAreaMiddleware $middleware;

    public function __construct(DatabaseInterface $db_handler) {
        $this->db_handler = $db_handler;
        $this->middleware = new ClientAreaMiddleware($db_handler);
    }

    public function action(): array {
        // Always check permissions first
        if (!$this->middleware->handle()) {
            return ['success' => false, 'error' => 'Access denied'];
        }

        try {
            // Implementation here
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            error_log("Controller::action() - Exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'Server error occurred'];
        }
    }
}
```

### Required Elements

1. **Strict Types Declaration**: Always use `declare(strict_types=1);`
2. **Proper Namespace**: Follow PSR-4 autoloading standards
3. **Exception Import**: Add `use Exception;` for error handling
4. **Database Dependency**: Inject `DatabaseInterface` via constructor
5. **Middleware Integration**: Use appropriate middleware for access control
6. **Structured Response**: Return consistent array format
7. **Error Logging**: Log all exceptions with context

## Middleware Usage

### Available Middleware Classes

1. **ClientAreaMiddleware**: For client-only access
2. **RoleMiddleware**: For role-based access control
3. **AdminMiddleware**: For admin-only access

### Implementation Examples

```php
// Client area protection
$this->middleware = new ClientAreaMiddleware($db_handler);
if (!$this->middleware->handle()) {
    return ['success' => false, 'error' => 'Access denied'];
}

// Role-based protection
$this->middleware = new RoleMiddleware($db_handler);
if (!$this->middleware->requireRole(['admin', 'employee'])) {
    return ['success' => false, 'error' => 'Insufficient permissions'];
}
```

## Model Integration

### Static Methods Pattern

Models should provide static factory methods for data retrieval:

```php
// Finding records
$user = User::findById($this->db_handler, $userId);
$projects = ClientProject::findByClientProfileId($this->db_handler, $profileId);

// Always check for null results
if (!$user) {
    return ['success' => false, 'error' => 'User not found'];
}
```

### Instance Methods Pattern

For data manipulation, use instance methods:

```php
// Creating new records
$project = new ClientProject($this->db_handler);
$project->setTitle($title)
        ->setDescription($description)
        ->setStatus('draft');

if ($project->save()) {
    return ['success' => true, 'project_id' => $project->getId()];
}
```

## Input Validation

### Standard Validation Pattern

```php
public function createRecord(): array {
    // 1. Check authentication
    if (!$this->middleware->handle()) {
        return ['success' => false, 'error' => 'Access denied'];
    }

    try {
        // 2. Extract and validate input
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($title)) {
            return ['success' => false, 'error' => 'Title is required'];
        }

        // 3. Process business logic
        // ... implementation

    } catch (Exception $e) {
        error_log("createRecord() - Exception: " . $e->getMessage());
        return ['success' => false, 'error' => 'Server error occurred'];
    }
}
```

### File Upload Handling

```php
private function handleFileUpload(array $files): ?array {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/uploads/';
    
    // Create directory if needed
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Validate and process files
    foreach ($files['name'] as $index => $fileName) {
        if (empty($fileName)) continue;
        
        $fileType = $files['type'][$index];
        $fileSize = $files['size'][$index];
        
        if (!in_array($fileType, $allowedTypes)) {
            continue; // Skip invalid files
        }
        
        if ($fileSize > $maxFileSize) {
            continue; // Skip large files
        }
        
        // Process file...
    }
    
    return $uploadedFiles;
}
```

## Error Handling Patterns

### Standard Error Response

```php
try {
    // Operation logic
    return ['success' => true, 'data' => $result];
} catch (Exception $e) {
    error_log("ControllerName::methodName() - Exception: " . $e->getMessage());
    return ['success' => false, 'error' => 'Server error occurred'];
}
```

### Specific Error Handling

```php
try {
    // Attempt operation
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    return ['success' => false, 'error' => 'Database error occurred'];
} catch (ValidationException $e) {
    return ['success' => false, 'error' => $e->getMessage()];
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    return ['success' => false, 'error' => 'Unexpected error occurred'];
}
```

## Database Operations

### Query Patterns

```php
// Simple select
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

// Complex query with joins
public static function getWithRelations(DatabaseInterface $db, int $id): ?array {
    try {
        $sql = "SELECT t1.*, t2.name as related_name 
                FROM table1 t1 
                LEFT JOIN table2 t2 ON t1.relation_id = t2.id 
                WHERE t1.id = ?";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log("Error in complex query: " . $e->getMessage());
        return null;
    }
}
```

### Transaction Handling

```php
public function complexOperation(array $data): array {
    $conn = $this->db_handler->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Multiple operations
        $stmt1 = $conn->prepare("INSERT INTO table1 ...");
        $stmt1->execute($data1);
        
        $stmt2 = $conn->prepare("UPDATE table2 ...");
        $stmt2->execute($data2);
        
        $conn->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Transaction failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Operation failed'];
    }
}
```

## Response Format Standards

### Success Response

```php
return [
    'success' => true,
    'message' => 'Operation completed successfully',
    'data' => $responseData,
    'meta' => [
        'timestamp' => time(),
        'version' => '1.0'
    ]
];
```

### Error Response

```php
return [
    'success' => false,
    'error' => 'Human-readable error message',
    'error_code' => 'VALIDATION_ERROR', // Optional
    'details' => $validationErrors // Optional
];
```

### List Response

```php
return [
    'success' => true,
    'data' => $items,
    'pagination' => [
        'page' => $currentPage,
        'per_page' => $perPage,
        'total' => $totalItems,
        'total_pages' => ceil($totalItems / $perPage)
    ]
];
```

## Authentication Integration

### Session Handling

```php
// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    return ['success' => false, 'error' => 'Not authenticated'];
}

// Get current user ID
$userId = (int)$_SESSION['user_id'];

// Check user role
$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin', 'employee'])) {
    return ['success' => false, 'error' => 'Insufficient permissions'];
}
```

### Permission Checking

```php
// Check ownership
$resource = ResourceModel::findById($this->db_handler, $resourceId);
if ($resource['user_id'] !== $userId) {
    return ['success' => false, 'error' => 'Access denied'];
}

// Check admin override
$userData = User::findById($this->db_handler, $userId);
if ($resource['user_id'] !== $userId && $userData['role'] !== 'admin') {
    return ['success' => false, 'error' => 'Access denied'];
}
```

## Pagination Implementation

```php
public function getList(): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    
    try {
        $conn = $this->db_handler->getConnection();
        
        // Get total count
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM table WHERE conditions");
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        
        // Get paginated results
        $stmt = $conn->prepare("SELECT * FROM table WHERE conditions LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Pagination error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to fetch data'];
    }
}
```

## Common Anti-Patterns to Avoid

### 1. Direct $_POST Usage Without Validation
❌ **Wrong**:
```php
$title = $_POST['title']; // No validation or sanitization
```

✅ **Correct**:
```php
$title = trim($_POST['title'] ?? '');
if (empty($title)) {
    return ['success' => false, 'error' => 'Title is required'];
}
```

### 2. Missing Error Handling
❌ **Wrong**:
```php
public function action(): array {
    $result = $this->someOperation(); // Could throw exception
    return ['success' => true, 'data' => $result];
}
```

✅ **Correct**:
```php
public function action(): array {
    try {
        $result = $this->someOperation();
        return ['success' => true, 'data' => $result];
    } catch (Exception $e) {
        error_log("action() - Exception: " . $e->getMessage());
        return ['success' => false, 'error' => 'Server error occurred'];
    }
}
```

### 3. Inconsistent Response Format
❌ **Wrong**:
```php
return $data; // Raw data without success indicator
```

✅ **Correct**:
```php
return ['success' => true, 'data' => $data];
```

### 4. Missing Permission Checks
❌ **Wrong**:
```php
public function deleteRecord(): array {
    // Direct deletion without checking ownership or permissions
}
```

✅ **Correct**:
```php
public function deleteRecord(): array {
    if (!$this->middleware->handle()) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    // Check ownership and proceed
}
```

This API development guide ensures consistent, secure, and maintainable API endpoints across the entire application.
