# Customer Segmentation Dashboard - Onboarding Guide

## Prerequisites
- **PHP**: 8.2.12 (installed with XAMPP)
- **MySQL**: 10.4.28-MariaDB (running on XAMPP)
- **Composer**: 2.9.3 (for PHP dependencies)
- **Node.js**: 24.12.0 (for chart generation scripts)
- **npm**: Available (for Node dependencies)

## Quick Start

### 1. Database Setup
The database schema is already defined. Import it into MySQL:

```bash
# Option A: Via MySQL command line
mysql -u root -p < customer_segmentation_ph.sql
mysql -u root -p < create_cluster_metadata.sql

# Option B: Via phpMyAdmin
# 1. Open http://localhost/phpmyadmin
# 2. Create new database: "customer_segmentation_ph"
# 3. Import "customer_segmentation_ph.sql"
# 4. Import "create_cluster_metadata.sql"
```

**Database Configuration** (in `db.php`):
- Host: `localhost`
- Database: `customer_segmentation_ph`
- User: `root`
- Password: `` (empty)

### 2. PHP Dependencies
PHP dependencies are already installed in `vendor/` via Composer:

```bash
# Verify installation
composer install

# Dependencies:
# - phpoffice/phpspreadsheet ^5.4 (Excel export)
# - tecnickcom/tcpdf ^6.10 (PDF export)
```

### 3. Node.js Dependencies (Optional - for advanced chart generation)
Chart generation scripts use Node.js dependencies:

```bash
cd scripts
npm install

# Dependencies:
# - chart.js ^4.5.1
# - chartjs-node-canvas ^5.0.0
```

### 4. Access the Application
1. Start XAMPP (Apache + MySQL)
2. Navigate to: **http://localhost/csapp/login.php**
3. Credentials: Check login.php for default credentials or database setup

## Project Structure

```
csapp/
├── .github/
│   └── copilot-instructions.md        # AI development guide
├── index.php                          # Main dashboard
├── login.php                          # Authentication
├── logout.php                         # Session destruction
├── db.php                             # Database connection (PDO)
├── run_clustering.php                 # K-means clustering engine
├── export_csv.php                     # CSV export handler
├── exports/
│   └── export_handler.php             # Multi-format export (CSV/Excel/PDF)
├── scripts/
│   ├── package.json                   # Node.js dependencies
│   ├── generate_chart.js              # Chart rendering
│   └── node_modules/                  # Node dependencies
├── vendor/                            # PHP dependencies (Composer)
├── charts/                            # Generated chart outputs
├── customer_segmentation_ph.sql       # Main database schema
├── create_cluster_metadata.sql        # Cluster metadata table
├── README.md                          # Project title
├── composer.json                      # PHP dependencies
├── composer.lock                      # PHP lock file
└── phpinfo.php                        # PHP info (development)
```

## Key Workflows

### Dashboard Access Flow
```
login.php (authentication)
  ↓
index.php (session validated)
  ↓
- Select segmentation type (dropdown)
- Execute SQL query (6 types available)
- Display results table
- Render Chart.js visualizations
- Generate insights
- Export options (CSV/Excel/PDF)
```

### Clustering Pipeline
```
run_clustering.php?clusters=5
  ↓
KMeansClustering class (Pure PHP k-means)
  ↓
Normalize customer data (z-score)
  ↓
Initialize centroids randomly
  ↓
Iterate until convergence (max 300 iterations)
  ↓
Update segmentation_results table
  ↓
Update cluster_metadata table
```

## Common Development Tasks

### Add Authentication to a New Page
```php
<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
// Your code here
?>
```

### Query Customers with PDO
```php
$stmt = $pdo->prepare("SELECT * FROM customers WHERE age > ?");
$stmt->execute([30]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Run Clustering from Code
```bash
# Via web (redirects to run_clustering.php)
http://localhost/csapp/run_clustering.php?clusters=5

# Via CLI (command line)
cd c:\xampp\htdocs\csapp
php run_clustering.php 5
```

### Export Data
```
Segmentation results → export_handler.php
  ├── ?format=csv → CSV stream
  ├── ?format=excel → .xlsx file (PhpSpreadsheet)
  └── ?format=pdf → .pdf file (TCPDF)
```

## Database Tables Reference

### `customers`
```sql
customer_id, gender, region, age, income, purchase_amount
```

### `segmentation_results`
```sql
customer_id, cluster_label, created_at
```

### `cluster_metadata`
```sql
cluster_id, cluster_name, description, avg_age, avg_income, 
avg_purchase_amount, customer_count, age_min, age_max, 
income_min, income_max, purchase_min, purchase_max, 
dominant_gender, dominant_region, business_recommendation, last_updated
```

## Development Server

### Start XAMPP Services
```bash
# Windows: Use XAMPP Control Panel
# Or command line:
"C:\xampp\apache_start.bat"
"C:\xampp\mysql_start.bat"
```

### Test PHP Configuration
```bash
# View installed extensions and settings
http://localhost/csapp/phpinfo.php

# Test database connection
http://localhost/csapp/test.php
```

## Security Notes
- Session-based authentication on all pages
- Input sanitization: `FILTER_SANITIZE_STRING` for form inputs
- Output escaping: `htmlspecialchars()` for HTML content
- PDO parameterized queries to prevent SQL injection
- Default credentials: Change before production deployment

## Troubleshooting

### Database Connection Failed
1. Verify MySQL is running (XAMPP Control Panel)
2. Check `db.php` credentials match your setup
3. Ensure `customer_segmentation_ph` database exists
4. Test: `http://localhost/csapp/test.php`

### PHP Extensions Missing
- PDO and mysql drivers required
- Check `phpinfo.php` for installed extensions
- Verify in `php.ini`: `extension=pdo_mysql`

### Chart Not Rendering
1. Check browser console for JavaScript errors
2. Verify results are returned from segmentation query
3. Ensure Chart.js CDN is accessible
4. Check for console errors in browser DevTools

### Clustering Timeout
- Large datasets (>100k rows) may exceed script timeout
- Adjust `set_time_limit()` in `run_clustering.php`
- Or increase `memory_limit` in php.ini

## Next Steps
1. **Import database schema** → See "Database Setup" above
2. **Access dashboard** → http://localhost/csapp/login.php
3. **Run clustering** → Click "Run Clustering" button on dashboard
4. **Explore segmentations** → Select from dropdown, view results
5. **Export data** → Use export buttons for CSV/Excel/PDF

## Support Files
- See `.github/copilot-instructions.md` for AI development guidelines
- Check SQL schema files for database structure details
- Review source code comments in php files for implementation details
