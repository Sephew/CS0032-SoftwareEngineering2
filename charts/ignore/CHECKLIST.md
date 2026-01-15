# Onboarding Checklist

## ✅ Setup Complete

This checklist guides you through getting the Customer Segmentation Dashboard up and running.

### Prerequisites (Already Verified)
- [x] PHP 8.2.12 installed (with XAMPP)
- [x] MySQL 10.4.28-MariaDB available
- [x] Composer 2.9.3 installed
- [x] Node.js v24.12.0 available
- [x] PHP dependencies installed in `vendor/`
- [x] Git repository ready

### Phase 1: Documentation Review
- [ ] Read `README.md` - Project overview
- [ ] Read `.github/copilot-instructions.md` - AI development guidelines
- [ ] Read `ONBOARDING.md` - Complete setup guide
- [ ] Read `QUICKREF.md` - Quick reference for common tasks
- [ ] Skim `index.php` - Understand dashboard architecture

### Phase 2: Database Setup
- [ ] Start XAMPP (Apache + MySQL)
- [ ] Open phpMyAdmin at http://localhost/phpmyadmin
- [ ] Create database: `customer_segmentation_ph`
- [ ] Import `customer_segmentation_ph.sql` (20k lines with sample data)
- [ ] Import `create_cluster_metadata.sql` (cluster metadata table)
- [ ] Test connection: Visit http://localhost/csapp/test.php

### Phase 3: Application Access
- [ ] Navigate to http://localhost/csapp/login.php
- [ ] Login with credentials:
  - Username: `admin`
  - Password: `password`
- [ ] Verify dashboard loads at http://localhost/csapp/index.php

### Phase 4: Feature Exploration
- [ ] Select "By Gender" segmentation → View results table
- [ ] Select "By Region" → Observe regional distribution
- [ ] Select "By Age Group" → See age bracket analysis
- [ ] Select "By Income Bracket" → View income tier insights
- [ ] Select "By Purchase Tier" → Analyze spending patterns
- [ ] Try export buttons (CSV, Excel, PDF)

### Phase 5: Run Clustering (ML Feature)
- [ ] Click "Run Clustering" button on dashboard
- [ ] Select "By Cluster" from segmentation dropdown
- [ ] View advanced cluster visualizations:
  - [ ] Cluster characteristics cards
  - [ ] Statistics table with demographics
  - [ ] Radar chart (normalized feature comparison)
  - [ ] Grouped bar chart (income vs purchase)
  - [ ] Scatter plot (income vs purchase colored by cluster)
  - [ ] Business recommendations per cluster

### Phase 6: Development Setup
- [ ] Verify PHP dependencies: `composer install` (already done)
- [ ] Install Node dependencies: `cd scripts && npm install` (optional)
- [ ] Verify all files exist: Check `.github/copilot-instructions.md`
- [ ] Verify database schema: `mysql customer_segmentation_ph < customer_segmentation_ph.sql`

### Phase 7: Ready for Development
- [ ] Understand session authentication (required on all pages)
- [ ] Review database configuration in `db.php`
- [ ] Study segmentation SQL patterns in `index.php`
- [ ] Examine export handlers in `exports/` directory
- [ ] Review k-means algorithm in `run_clustering.php`
- [ ] Explore Chart.js visualizations in `index.php`

## 📁 Project Structure Quick View

```
csapp/
├── Documentation (created during onboarding)
│   ├── README.md ........................... Project title
│   ├── ONBOARDING.md ....................... Setup guide
│   ├── QUICKREF.md ......................... Quick reference
│   └── .github/copilot-instructions.md .... AI dev guide
│
├── Core Application
│   ├── index.php ........................... Dashboard UI + segmentation logic
│   ├── login.php ........................... Authentication (admin/password)
│   ├── logout.php .......................... Session destruction
│   └── db.php .............................. PDO MySQL connection
│
├── Features
│   ├── run_clustering.php .................. K-means clustering engine
│   ├── exports/export_handler.php ......... Multi-format export router
│   ├── exports/export_csv.php ............. CSV export
│   ├── exports/export_excel.php ........... Excel export (PhpSpreadsheet)
│   ├── exports/export_pdf.php ............. PDF export (TCPDF)
│   └── exports/export_functions.php ....... Shared export utilities
│
├── Database
│   ├── customer_segmentation_ph.sql ....... Main schema (customers table)
│   └── create_cluster_metadata.sql ........ Cluster metadata schema
│
├── Dependencies
│   ├── composer.json ....................... PHP dependencies (PhpSpreadsheet, TCPDF)
│   ├── composer.lock ....................... PHP lock file
│   ├── vendor/ ............................. Composer packages (installed)
│   ├── scripts/package.json ............... Node.js dependencies
│   ├── scripts/node_modules/ .............. Node packages
│   └── scripts/generate_chart.js .......... Node chart rendering (optional)
│
├── Generated Files
│   ├── charts/ ............................. Generated PNG charts
│   └── phpinfo.php ......................... PHP configuration info
│
└── Utilities
    ├── test.php ........................... Database connection test
    └── setup.bat .......................... Windows setup script
```

## 🚀 Getting Started Workflow

### First Time Login
```
1. Visit http://localhost/csapp/login.php
2. Enter: admin / password
3. Click "Show Results" with "By Gender" selected
4. Explore visualizations and export options
```

### Run Clustering
```
1. Click "Run Clustering" button on dashboard
2. Wait for k-means algorithm (5-10 seconds)
3. Select "By Cluster" from dropdown
4. View 5 distinct customer clusters
5. Review business recommendations per cluster
```

### Export Data
```
1. After selecting any segmentation type
2. Click "Export CSV", "Export Excel", or "Export PDF"
3. File downloads to your browser's default folder
```

## 📚 Key Resources

| Document | Purpose |
|----------|---------|
| `README.md` | Project title and overview |
| `ONBOARDING.md` | Detailed setup and troubleshooting |
| `QUICKREF.md` | Common commands and code patterns |
| `.github/copilot-instructions.md` | AI developer guidelines |

## 🔑 Important Credentials

| Type | Value |
|------|-------|
| Username | `admin` |
| Password | `password` |
| DB Host | `localhost` |
| DB Name | `customer_segmentation_ph` |
| DB User | `root` |
| DB Password | (empty) |

## 🛠 Common First Tasks

### Add a New Segmentation Type
1. Open `index.php`
2. Add case in POST switch statement (around line 16)
3. Write SQL with GROUP BY and aggregation
4. Add insights case in JavaScript (around line 250)
5. Test on dashboard

### Debug Database Issues
1. Visit http://localhost/csapp/test.php
2. Check error message in browser
3. Verify database exists: `mysql -u root customer_segmentation_ph`
4. Review `db.php` credentials

### Export Enhancements
1. Review `exports/export_handler.php`
2. Study PhpSpreadsheet usage in `export_excel.php`
3. Study TCPDF usage in `export_pdf.php`
4. Extend `export_functions.php` with new utilities

## ⚠️ Troubleshooting

### "Database connection failed"
```bash
# Check MySQL is running
mysql -u root -e "SELECT 1"

# If fails, start XAMPP MySQL via Control Panel or CLI
```

### "Login page loops"
```
Clear browser cookies for localhost or use private/incognito window
```

### "Charts not rendering"
```
Open browser DevTools (F12) → Console tab
Check for JavaScript errors from Chart.js or data encoding
```

### "Clustering timeout"
```php
// Increase in run_clustering.php:
set_time_limit(300); // 5 minutes instead of default
ini_set('memory_limit', '512M'); // Increase if needed
```

## ✨ Next Steps After Onboarding

1. **Explore Code**: Review PHP patterns in `index.php` and `exports/`
2. **Understand Architecture**: Read `.github/copilot-instructions.md`
3. **Enhance Features**: Add new segmentations or export formats
4. **Database Expansion**: Add new tables and queries
5. **UI Improvements**: Customize Bootstrap components
6. **Performance Tuning**: Optimize for larger datasets

## 📞 Support

- **Database Issues**: Check XAMPP MySQL status and `db.php`
- **Session Issues**: Clear cookies, use private browsing
- **Chart Issues**: Check browser console for JavaScript errors
- **Export Issues**: Verify `vendor/` packages are installed
- **Clustering Issues**: Check PHP memory and timeout settings

---

**Status**: ✅ Onboarding Complete

All documentation, database schema, PHP dependencies, and configuration are ready for development. The application is fully functional and ready for exploration and enhancement.

Next: Log in and explore the dashboard features!

