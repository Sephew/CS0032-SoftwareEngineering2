# Getting Started - 5 Minute Setup

## What You Have

A fully functional **Customer Segmentation Dashboard** - a PHP/MySQL analytics application with:
- 6 customer segmentation types (gender, region, age, income, clustering, purchase tier)
- Real-time visualizations (Chart.js: bar, pie, radar, scatter charts)
- K-means ML clustering (pure PHP implementation)
- Multi-format export (CSV, Excel via PhpSpreadsheet, PDF via TCPDF)
- Session-based authentication
- Complete database schema with sample data

## Quick Start (5 Minutes)

### 1. Start Services
```
Open XAMPP Control Panel → Start Apache & MySQL
```

### 2. Access Dashboard
```
Go to: http://localhost/csapp/login.php
Login: admin / password
```

### 3. Explore Features
```
Select "By Gender" → Click "Show Results" → See visualizations
Click "Run Clustering" → Wait 5 seconds → Select "By Cluster"
Try export buttons (CSV, Excel, PDF)
```

## Documentation

| File | Read Time | Purpose |
|------|-----------|---------|
| **QUICKREF.md** | 2 min | Common commands, URLs, code patterns |
| **ONBOARDING.md** | 10 min | Detailed setup, troubleshooting, workflows |
| **CHECKLIST.md** | 5 min | Setup verification and next steps |
| **.github/copilot-instructions.md** | 10 min | Architecture for AI development |

## Login Credentials

```
Username: admin
Password: password
```

## Database Info

```
Host:     localhost
Database: customer_segmentation_ph
User:     root
Password: (empty)
```

## Key Files

```
index.php                    Main dashboard
run_clustering.php           K-means ML engine
exports/export_handler.php   CSV/Excel/PDF export
db.php                       Database connection
```

## URLs

```
Dashboard:   http://localhost/csapp/index.php
Login:       http://localhost/csapp/login.php
Clustering:  http://localhost/csapp/run_clustering.php?clusters=5
```

## What's Installed

- ✅ PHP 8.2.12 (via XAMPP)
- ✅ MySQL 10.4.28-MariaDB (via XAMPP)
- ✅ Composer & PHP dependencies (PhpSpreadsheet, TCPDF)
- ✅ Node.js v24.12.0 (optional chart generation)
- ✅ Database schema with 20k+ sample customers
- ✅ All documentation

## Next Steps

1. **Access Dashboard** → http://localhost/csapp/login.php
2. **Explore Segmentations** → Try all 6 types
3. **Run Clustering** → See ML in action
4. **Review Code** → Understand architecture
5. **Start Developing** → Add features, enhance UI

## Need Help?

- **Database issues**: See "Troubleshooting" in ONBOARDING.md
- **How something works**: Check QUICKREF.md code patterns
- **Architecture questions**: Read .github/copilot-instructions.md
- **Setup verification**: Follow CHECKLIST.md

---

**You're ready to go!** 🚀

Login and start exploring the dashboard.

