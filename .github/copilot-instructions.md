# Customer Segmentation Dashboard - AI Development Guide

## Project Overview
A PHP-based customer analytics dashboard enabling business-driven customer segmentation through multiple dimensions (demographics, behavior, ML clustering) with real-time visualizations and data export capabilities. Runs on Apache/XAMPP with MySQL backend.

## Architecture & Key Components

### Frontend Layer (index.php)
- **Chart.js Integration**: Bar, line, pie, radar, and scatter charts for multi-dimensional analysis
- **Bootstrap 5 UI**: Responsive design with card-based layout for cluster insights  
- **Seven Segmentation Types**: gender, region, age_group, income_bracket, cluster, purchase_tier, clv (Customer Lifetime Value)
- **Real-time Visualizations**: Charts regenerate based on selected segmentation with automatic insight generation

### Backend Services
- **Session Management**: Authentication enforced via $_SESSION['logged_in'] (login.php; credentials: admin/password)
- **Database Layer** (db.php): PDO MySQL connection to customer_segmentation_ph database
- **RESTful API** (api/index.php): JSON API with health checks, segments endpoints, clusters listing
- **Export Pipeline** (exports/): CSV, Excel (XLSX), PDF generation with embedded charts

### Database Schema
**Core Tables:**
- customers: customer_id, gender, region, age, income, purchase_amount, clv_tier, purchase_frequency, calculated_clv, customer_lifespan_months
- segmentation_results: customer_id, cluster_label (ML-generated)
- cluster_metadata: cluster_id, cluster_name, description, customer_count, avg_age, age_min, age_max, avg_income, avg_purchase_amount, dominant_gender, dominant_region, business_recommendation

### Routing Architecture
- **Root .htaccess**: Routes /api/* to pi/index.php via mod_rewrite
- **API .htaccess**: Routes all API requests to pi/index.php with path parameter
- **Key Endpoints**: /api/health, /api/segments/{type}, /api/clusters, /api/auth/login

## Critical Workflows

### Data Segmentation Pipeline
1. **POST Handler in index.php**: Filter input via ilter_input(INPUT_POST, 'segmentation_type', FILTER_SANITIZE_STRING)
2. **SQL Construction**: Each type builds specialized SQL with GROUP BY, CASE expressions for binning
3. **Cluster-specific**: When cluster type selected, fetch enhanced metadata from cluster_metadata + scatter plot data
4. **Error Handling**: Try-catch PDOException blocks with graceful degradation (empty arrays for optional metadata)
5. **Results Processing**: PDO::FETCH_ASSOC for flexible column iteration to charts/tables

### K-Means Clustering Pipeline (run_clustering.php)
- **Trigger**: Via un_clustering.php?clusters=5 or dashboard button
- **Algorithm**: Pure PHP k-means (300 max iterations, 0.0001 convergence threshold)
- **Features Used**: age, income, purchase_amount (z-score normalized)
- **Output**: Inserts cluster_label into segmentation_results table; populates cluster_metadata with business insights
- **Default**: 5 clusters; configurable via GET parameter

### Export Generation (exports/export_handler.php)
- **Input**: type={gender|region|age_group|income_bracket|cluster|purchase_tier|clv}, format={csv|excel|pdf}
- **Flow**: Fetch segmentation data  Generate chart  Build export file  Stream with appropriate headers
- **Dependencies**: PhpOffice\PhpSpreadsheet for Excel, TCPDF for PDF; Chart.js for chart generation
- **Chart Embedding**: PNGs generated in /charts/ directory, embedded in Excel/PDF exports

## Project-Specific Conventions

### SQL Patterns
- Always parameterized queries with PDO (no concatenation)
- Age/income binning via CASE BETWEEN: CASE WHEN age BETWEEN 18 AND 25 THEN '18-25'...
- Aggregation: COUNT(*), AVG(), ROUND(..., 2) for financial data
- CLV ordering: FIELD(clv_tier, 'Platinum', 'Gold', 'Silver', 'Bronze') for custom sort

### Authentication & Session Flow
- Login stores $_SESSION['logged_in'] = true
- All pages except login.php/logout.php check session at top
- API supports optional JWT (commented out, ready to uncomment with token verification)
- Hardcoded credentials in login.php (admin/password) - upgrade to database in production

### Chart Configuration
- **Type Selection**: Line charts for ordered dimensions (age_group, income_bracket), bar charts for categorical
- **Color Palette**: Six predefined RGBA colors with opacity 0.6 for fill, 1.0 for border
- **Dual-Axis**: Grouped bar charts use yAxisID: 'y' and 'y1' for separate scales
- **Radar/Scatter**: Cluster view includes normalized radar (0-100 scale) + scatter plot (income vs purchase colored by cluster)

### Insight Generation Logic
Insights built dynamically in JavaScript by:
- Calculating totals and percentages from result arrays
- Finding max/min values to highlight top/bottom segments
- Checking for optional fields before rendering (avg_income, avg_purchase_amount, business_recommendation)
- Using 	oLocaleString() for currency/number formatting

## Integration Points

### External Dependencies (composer.json)
- **phpoffice/phpspreadsheet**: ^5.4 for Excel generation
- **tecnickcom/tcpdf**: ^6.10 for PDF generation
- Install with: composer install in project root

### Key Route Contracts
| Route | Method | Purpose |
|-------|--------|---------|
| /csapp/ | GET | Dashboard (requires session) |
| /csapp/api/health | GET | Health check (no auth) |
| /csapp/api/segments/{type} | GET | Segmentation data as JSON |
| /csapp/api/clusters | GET | List all clusters with metadata |
| /csapp/run_clustering.php?clusters=N | GET | Trigger k-means (generates segmentation_results) |
| /csapp/exports/export_handler.php?type=X&format=Y | GET/POST | Stream export file |

## Development Patterns

### Adding New Segmentation Type
1. Add case branch in index.php POST switch with SQL query (GROUP BY + CASE for binning)
2. Query must return: segment_name/key column, total_customers, avg_income, avg_purchase_amount
3. Add insights JavaScript case branch (calculate percentages, find max/min)
4. Update segmentation dropdown in HTML form
5. Add export handler support in exports/export_handler.php

### Debugging Data Issues
- Verify customers table: SELECT COUNT(*) FROM customers (should be >0)
- Check clustering results: SELECT DISTINCT cluster_label FROM segmentation_results
- Monitor PDOException in browser/logs for schema mismatches
- Chart generation: PNG files in /charts/ directory (ensure writable)

### Common Fixes
- Chart not rendering: Check data array structure (needs numeric columns)
- Export fails: Verify /exports/ and /charts/ directories are writable (755)
- Clustering hangs: Reduce cluster count, check MySQL memory limits
- PDOException on startup: Verify db.php credentials match XAMPP MySQL (root, empty password)

## Security & Performance
- Input: FILTER_SANITIZE_STRING on user inputs, htmlspecialchars() on output
- Session: Enforced before data access (except login/health endpoints)
- File Handling: Check directory exists before mkdir (export generation)
- Memory: set_time_limit(0), ini_set('memory_limit', '256M') for clustering on large datasets
- Performance: Index customer_id, cluster_label in database; paginate if >10k rows

## Development Environment
**Requirements**: Apache 2.4+, PHP 8.0+, MySQL 5.7+, Composer
**Setup**: Place in /xampp/htdocs/csapp/, run composer install, create MySQL database from SQL scripts
**Testing**: Navigate to http://localhost/csapp/ after XAMPP startup
**Debugging**: Check Apache error.log if rewrite rules fail
