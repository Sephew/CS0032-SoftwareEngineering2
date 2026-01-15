# Quick Development Reference

## Credentials & Access

**Login Page**: `http://localhost/csapp/login.php`
- **Username**: `admin`
- **Password**: `password`

**Database**:
- Host: `localhost`
- Database: `customer_segmentation_ph`
- User: `root`
- Password: (empty)

## Essential Commands

### Database Operations
```bash
# Import main schema
mysql -u root < customer_segmentation_ph.sql

# Import cluster metadata
mysql -u root < create_cluster_metadata.sql

# Access MySQL CLI
mysql -u root customer_segmentation_ph
```

### PHP/Composer
```bash
composer install
composer update
composer audit
```

### Node.js (optional)
```bash
cd scripts && npm install
```

## File Navigation

| File | Purpose |
|------|---------|
| `index.php` | Main dashboard UI |
| `login.php` | Authentication |
| `db.php` | PDO MySQL connection |
| `run_clustering.php` | K-means clustering |
| `exports/export_handler.php` | Multi-format export |

## Common URLs

```
Dashboard:         http://localhost/csapp/index.php
Login:             http://localhost/csapp/login.php
Run Clustering:    http://localhost/csapp/run_clustering.php?clusters=5
```

## Segmentation Types

1. **Gender** - Distribution across gender
2. **Region** - Geographic distribution
3. **Age Group** - Brackets: 18-25, 26-40, 41-60, 61+
4. **Income Bracket** - Low/Middle/High
5. **Cluster** - K-means ML segments
6. **Purchase Tier** - Spending tiers

## Key Queries

```sql
-- Count customers
SELECT COUNT(*) FROM customers;

-- View clusters
SELECT DISTINCT cluster_label FROM segmentation_results;

-- Cluster details
SELECT * FROM cluster_metadata;
```

## Code Patterns

### Database Query
```php
$stmt = $pdo->prepare("SELECT * FROM customers WHERE income > ?");
$stmt->execute([50000]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Session Protection
```php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}
```

### Chart Rendering
```javascript
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($results, 'name')) ?>,
        datasets: [{ data: <?= json_encode(array_column($results, 'count')) ?> }]
    }
});
```

## Debugging

### Test Database Connection
```php
var_dump($pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn());
```

### Run Clustering Manually
```bash
php run_clustering.php 5
```

### Check PHP Errors
```bash
tail -f C:/xampp/php/logs/php_error_log
```

## Performance Notes

- Clustering: ~5-10 seconds for 50k customers (300 iterations)
- Memory: PHP set to 256MB for k-means operations
- Charts: Optimized for <50k data points

