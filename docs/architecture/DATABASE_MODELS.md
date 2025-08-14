# Database & Model Development Guide - Darkheim WebEngine

## Overview

This guide covers database operations, model development, and data layer patterns used in the Darkheim WebEngine project.

## Database Architecture

### Connection Management

The project uses a single PDO connection managed through the `DatabaseInterface`:

```php
interface DatabaseInterface
{
    public function getConnection(): PDO;
}
```

**Key Principles**:
- Connection reuse across requests
- Centralized error handling
- Prepared statements for all queries
- Transaction support when needed

### Database Configuration

Database settings are managed in `config/config.php`:
- Host, port, database name
- Character set (UTF-8)
- Connection options and error handling

## Model Development Pattern

### Base Model Structure

```php
declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use PDO;
use Exception;

class ExampleModel
{
    // Instance properties
    private DatabaseInterface $db_handler;
    private ?int $id = null;
    private string $name;
    private ?string $description = null;
    private string $created_at;

    public function __construct(DatabaseInterface $db_handler)
    {
        $this->db_handler = $db_handler;
    }

    // Static factory methods
    public static function findById(DatabaseInterface $db, int $id): ?array
    {
        try {
            $stmt = $db->getConnection()->prepare("SELECT * FROM table_name WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error finding record by ID: " . $e->getMessage());
            return null;
        }
    }

    // Instance methods for data manipulation
    public function save(): bool
    {
        try {
            if ($this->id) {
                return $this->update();
            } else {
                return $this->create();
            }
        } catch (Exception $e) {
            error_log("Error saving record: " . $e->getMessage());
            return false;
        }
    }

    // Getters and setters with fluent interface
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

### Required Model Components

1. **Constructor**: Accept `DatabaseInterface` dependency
2. **Static Factory Methods**: For data retrieval (`findById`, `findAll`, etc.)
3. **Instance Methods**: For data manipulation (`save`, `delete`, etc.)
4. **Fluent Setters**: Return `self` for method chaining
5. **Validation Methods**: Business rule enforcement
6. **Error Handling**: Proper exception catching and logging

## CRUD Operations

### Create Operations

```php
private function create(): bool
{
    try {
        $sql = "INSERT INTO table_name (name, description, created_at) 
                VALUES (?, ?, NOW())";
        
        $stmt = $this->db_handler->getConnection()->prepare($sql);
        $result = $stmt->execute([
            $this->name,
            $this->description
        ]);

        if ($result) {
            $this->id = (int)$this->db_handler->getConnection()->lastInsertId();
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error creating record: " . $e->getMessage());
        return false;
    }
}
```

### Read Operations

```php
// Single record retrieval
public static function findById(DatabaseInterface $db, int $id): ?array
{
    try {
        $stmt = $db->getConnection()->prepare("SELECT * FROM table_name WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log("Error finding record: " . $e->getMessage());
        return null;
    }
}

// Multiple records with conditions
public static function findByCondition(DatabaseInterface $db, array $conditions): array
{
    try {
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT * FROM table_name WHERE " . implode(' AND ', $whereClause) . " ORDER BY created_at DESC";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error finding records by condition: " . $e->getMessage());
        return [];
    }
}

// Paginated results
public static function findWithPagination(DatabaseInterface $db, int $page = 1, int $perPage = 20): array
{
    try {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM table_name");
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        
        // Get paginated results
        $stmt = $db->getConnection()->prepare("SELECT * FROM table_name ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $records,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    } catch (Exception $e) {
        error_log("Error in paginated query: " . $e->getMessage());
        return ['data' => [], 'pagination' => []];
    }
}
```

### Update Operations

```php
private function update(): bool
{
    try {
        $sql = "UPDATE table_name 
                SET name = ?, description = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db_handler->getConnection()->prepare($sql);
        return $stmt->execute([
            $this->name,
            $this->description,
            $this->id
        ]);
    } catch (Exception $e) {
        error_log("Error updating record: " . $e->getMessage());
        return false;
    }
}

// Partial updates
public function updateField(string $field, $value): bool
{
    try {
        $allowedFields = ['name', 'description', 'status']; // Whitelist
        
        if (!in_array($field, $allowedFields)) {
            throw new InvalidArgumentException("Field '{$field}' is not allowed for update");
        }
        
        $sql = "UPDATE table_name SET {$field} = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db_handler->getConnection()->prepare($sql);
        
        return $stmt->execute([$value, $this->id]);
    } catch (Exception $e) {
        error_log("Error updating field {$field}: " . $e->getMessage());
        return false;
    }
}
```

### Delete Operations

```php
// Soft delete (recommended)
public function softDelete(): bool
{
    try {
        $sql = "UPDATE table_name SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db_handler->getConnection()->prepare($sql);
        return $stmt->execute([$this->id]);
    } catch (Exception $e) {
        error_log("Error soft deleting record: " . $e->getMessage());
        return false;
    }
}

// Hard delete (use with caution)
public function delete(): bool
{
    try {
        $sql = "DELETE FROM table_name WHERE id = ?";
        $stmt = $this->db_handler->getConnection()->prepare($sql);
        return $stmt->execute([$this->id]);
    } catch (Exception $e) {
        error_log("Error deleting record: " . $e->getMessage());
        return false;
    }
}

// Static delete method
public static function deleteById(DatabaseInterface $db, int $id): bool
{
    try {
        $stmt = $db->getConnection()->prepare("UPDATE table_name SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        error_log("Error deleting record by ID: " . $e->getMessage());
        return false;
    }
}
```

## Advanced Query Patterns

### Joins and Relations

```php
public static function findWithJoin(DatabaseInterface $db, int $id): ?array
{
    try {
        $sql = "SELECT 
                    t1.*,
                    t2.name as related_name,
                    t2.description as related_description
                FROM table_name t1
                LEFT JOIN related_table t2 ON t1.related_id = t2.id
                WHERE t1.id = ?";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        error_log("Error in join query: " . $e->getMessage());
        return null;
    }
}

// Complex multi-table queries
public static function getComplexData(DatabaseInterface $db, array $filters = []): array
{
    try {
        $sql = "SELECT 
                    t1.id,
                    t1.name,
                    t2.title,
                    COUNT(t3.id) as comment_count,
                    AVG(t4.rating) as average_rating
                FROM table_name t1
                LEFT JOIN posts t2 ON t1.id = t2.author_id
                LEFT JOIN comments t3 ON t2.id = t3.post_id
                LEFT JOIN ratings t4 ON t2.id = t4.post_id
                WHERE t1.active = 1";
        
        $params = [];
        
        // Dynamic filtering
        if (!empty($filters['category'])) {
            $sql .= " AND t2.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['min_rating'])) {
            $sql .= " AND t4.rating >= ?";
            $params[] = $filters['min_rating'];
        }
        
        $sql .= " GROUP BY t1.id, t1.name, t2.title
                  ORDER BY average_rating DESC, comment_count DESC";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in complex query: " . $e->getMessage());
        return [];
    }
}
```

### Transaction Handling

```php
public function performComplexOperation(array $data): bool
{
    $conn = $this->db_handler->getConnection();
    
    try {
        $conn->beginTransaction();
        
        // Operation 1: Create main record
        $stmt1 = $conn->prepare("INSERT INTO main_table (name, description) VALUES (?, ?)");
        $stmt1->execute([$data['name'], $data['description']]);
        $mainId = (int)$conn->lastInsertId();
        
        // Operation 2: Create related records
        $stmt2 = $conn->prepare("INSERT INTO related_table (main_id, value) VALUES (?, ?)");
        foreach ($data['related_items'] as $item) {
            $stmt2->execute([$mainId, $item]);
        }
        
        // Operation 3: Update statistics
        $stmt3 = $conn->prepare("UPDATE statistics SET count = count + 1 WHERE type = 'main_table'");
        $stmt3->execute();
        
        $conn->commit();
        $this->id = $mainId;
        return true;
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Transaction failed: " . $e->getMessage());
        return false;
    }
}
```

## Data Validation

### Input Validation

```php
public function validate(): array
{
    $errors = [];
    
    // Required field validation
    if (empty($this->name)) {
        $errors['name'] = 'Name is required';
    }
    
    // Length validation
    if (strlen($this->name) > 255) {
        $errors['name'] = 'Name must not exceed 255 characters';
    }
    
    // Format validation
    if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Custom business rules
    if (!empty($this->price) && $this->price < 0) {
        $errors['price'] = 'Price cannot be negative';
    }
    
    // Uniqueness validation
    if (!empty($this->username) && $this->isUsernameTaken()) {
        $errors['username'] = 'Username is already taken';
    }
    
    return $errors;
}

private function isUsernameTaken(): bool
{
    try {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ? AND id != ?";
        $stmt = $this->db_handler->getConnection()->prepare($sql);
        $stmt->execute([$this->username, $this->id ?? 0]);
        
        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log("Error checking username uniqueness: " . $e->getMessage());
        return false;
    }
}
```

### Data Sanitization

```php
public function sanitizeInput(array $data): array
{
    $sanitized = [];
    
    // String sanitization
    $sanitized['name'] = trim($data['name'] ?? '');
    $sanitized['description'] = trim($data['description'] ?? '');
    
    // HTML sanitization for rich content
    $sanitized['content'] = htmlspecialchars($data['content'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Number sanitization
    $sanitized['price'] = filter_var($data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $sanitized['quantity'] = filter_var($data['quantity'] ?? 0, FILTER_VALIDATE_INT);
    
    // Boolean sanitization
    $sanitized['active'] = !empty($data['active']);
    
    // Date sanitization
    if (!empty($data['date'])) {
        $sanitized['date'] = date('Y-m-d H:i:s', strtotime($data['date']));
    }
    
    return $sanitized;
}
```

## Search and Filtering

### Full-Text Search

```php
public static function search(DatabaseInterface $db, string $query, array $filters = []): array
{
    try {
        $sql = "SELECT *, MATCH(name, description) AGAINST(? IN BOOLEAN MODE) as relevance
                FROM table_name 
                WHERE MATCH(name, description) AGAINST(? IN BOOLEAN MODE)";
        
        $params = [$query, $query];
        
        // Additional filters
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['active'])) {
            $sql .= " AND active = 1";
        }
        
        $sql .= " ORDER BY relevance DESC, created_at DESC";
        
        // Pagination
        if (isset($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
            
            if (isset($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = $filters['offset'];
            }
        }
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in full-text search: " . $e->getMessage());
        return [];
    }
}
```

### Dynamic Filtering

```php
public static function findWithFilters(DatabaseInterface $db, array $filters): array
{
    try {
        $sql = "SELECT * FROM table_name WHERE 1=1";
        $params = [];
        
        // Dynamic WHERE conditions
        if (!empty($filters['name'])) {
            $sql .= " AND name LIKE ?";
            $params[] = '%' . $filters['name'] . '%';
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Sorting
        $allowedSort = ['name', 'price', 'created_at'];
        $sortBy = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'created_at';
        $sortOrder = ($filters['order'] ?? '') === 'asc' ? 'ASC' : 'DESC';
        
        $sql .= " ORDER BY {$sortBy} {$sortOrder}";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in filtered query: " . $e->getMessage());
        return [];
    }
}
```

## Performance Optimization

### Query Optimization Tips

1. **Use Indexes**: Ensure proper indexing on frequently queried columns
2. **Limit Results**: Always use LIMIT when possible
3. **Avoid N+1 Queries**: Use JOINs instead of separate queries
4. **Use Prepared Statements**: For security and performance
5. **Cache Results**: For frequently accessed data

### Connection Pooling

```php
class DatabaseConnection implements DatabaseInterface
{
    private static ?PDO $connection = null;
    
    public function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = $this->createConnection();
        }
        
        return self::$connection;
    }
    
    private function createConnection(): PDO
    {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true, // Connection pooling
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        return new PDO($dsn, $this->username, $this->password, $options);
    }
}
```

## Error Handling Best Practices

### Database Error Logging

```php
private function logDatabaseError(Exception $e, string $operation, array $context = []): void
{
    $errorData = [
        'operation' => $operation,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'context' => $context
    ];
    
    error_log("Database Error: " . json_encode($errorData));
}
```

### Graceful Degradation

```php
public static function findById(DatabaseInterface $db, int $id): ?array
{
    try {
        $stmt = $db->getConnection()->prepare("SELECT * FROM table_name WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        // Specific database error handling
        error_log("PDO Error in findById: " . $e->getMessage());
        return null;
    } catch (Exception $e) {
        // General error handling
        error_log("General error in findById: " . $e->getMessage());
        return null;
    }
}
```

This database and model development guide ensures consistent, secure, and maintainable data layer operations across the entire application.
