# CLV Tiers Segmentation - Design Proposal

## 1. ADDITIONAL DATABASE COLUMNS NEEDED

### New `customers` table columns:
```sql
-- For CLV calculation support
ALTER TABLE customers ADD COLUMN (
    purchase_frequency INT DEFAULT 1 COMMENT 'Number of purchases made by customer',
    first_purchase_date DATE COMMENT 'Initial purchase date (for lifespan calculation)',
    last_purchase_date DATE COMMENT 'Most recent purchase date',
    total_spent DECIMAL(12,2) COMMENT 'Cumulative total spending'
);

-- Optional: Create an orders table for more granular tracking
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_date DATE NOT NULL,
    order_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);
```

### Rationale:
- **purchase_frequency**: Required for CLV calculation = Avg Purchase × Frequency × Lifespan
- **first_purchase_date**: Determines customer lifespan (months/years with company)
- **last_purchase_date**: Indicates recency (engagement metric)
- **total_spent**: Quick access to lifetime spending without aggregation

---

## 2. SQL QUERY FOR CLV TIERS CALCULATION

```sql
-- CLV Calculation Query
SELECT 
    c.customer_id,
    c.gender,
    c.region,
    c.age,
    c.income,
    c.purchase_frequency,
    ROUND(c.purchase_amount, 2) as avg_purchase_amount,
    ROUND(
        DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25, 
        1
    ) as customer_lifespan_years,
    -- CLV Formula: (Avg Purchase × Frequency × Lifespan in years)
    ROUND(
        c.purchase_amount * c.purchase_frequency * 
        (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25),
        2
    ) as clv_value,
    -- Assign CLV Tiers
    CASE 
        WHEN c.purchase_amount * c.purchase_frequency * 
             (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25) >= 15000 
        THEN 'Platinum'
        WHEN c.purchase_amount * c.purchase_frequency * 
             (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25) >= 8000 
        THEN 'Gold'
        WHEN c.purchase_amount * c.purchase_frequency * 
             (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25) >= 3000 
        THEN 'Silver'
        ELSE 'Bronze'
    END as clv_tier,
    COUNT(DISTINCT c.customer_id) OVER (
        PARTITION BY CASE 
            WHEN c.purchase_amount * c.purchase_frequency * 
                 (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25) >= 15000 
            THEN 'Platinum'
            WHEN c.purchase_amount * c.purchase_frequency * 
                 (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25) >= 8000 
            THEN 'Gold'
            WHEN c.purchase_amount * c.purchase_frequency * 
                 (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25) >= 3000 
            THEN 'Silver'
            ELSE 'Bronze'
        END
    ) as tier_count
FROM customers c
WHERE c.first_purchase_date IS NOT NULL
GROUP BY c.clv_tier
ORDER BY clv_value DESC;

-- Aggregated version for dashboard
SELECT 
    CASE 
        WHEN (c.purchase_amount * c.purchase_frequency * 
              (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)) >= 15000 
        THEN 'Platinum'
        WHEN (c.purchase_amount * c.purchase_frequency * 
              (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)) >= 8000 
        THEN 'Gold'
        WHEN (c.purchase_amount * c.purchase_frequency * 
              (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)) >= 3000 
        THEN 'Silver'
        ELSE 'Bronze'
    END as clv_tier,
    COUNT(*) as total_customers,
    COUNT(*) * 100 / (SELECT COUNT(*) FROM customers WHERE first_purchase_date IS NOT NULL) as percentage,
    ROUND(AVG(c.purchase_amount), 2) as avg_purchase_amount,
    ROUND(AVG(c.purchase_frequency), 1) as avg_frequency,
    ROUND(
        AVG(
            c.purchase_amount * c.purchase_frequency * 
            (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)
        ), 2
    ) as avg_clv,
    ROUND(AVG(c.income), 2) as avg_income
FROM customers c
WHERE c.first_purchase_date IS NOT NULL
GROUP BY clv_tier
ORDER BY FIELD(clv_tier, 'Platinum', 'Gold', 'Silver', 'Bronze');
```

---

## 3. PHP CASE STATEMENT FOR INDEX.PHP

```php
case 'clv':
    $sql = "SELECT 
                CASE 
                    WHEN (c.purchase_amount * c.purchase_frequency * 
                          (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)) >= 15000 
                    THEN 'Platinum'
                    WHEN (c.purchase_amount * c.purchase_frequency * 
                          (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)) >= 8000 
                    THEN 'Gold'
                    WHEN (c.purchase_amount * c.purchase_frequency * 
                          (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)) >= 3000 
                    THEN 'Silver'
                    ELSE 'Bronze'
                END as clv_tier,
                COUNT(*) AS total_customers,
                ROUND(AVG(c.purchase_amount), 2) AS avg_purchase_amount,
                ROUND(AVG(c.purchase_frequency), 1) AS avg_frequency,
                ROUND(
                    AVG(
                        c.purchase_amount * c.purchase_frequency * 
                        (DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25)
                    ), 2
                ) AS avg_clv,
                ROUND(AVG(c.income), 2) AS avg_income,
                ROUND(AVG(DATEDIFF(CURDATE(), c.first_purchase_date) / 365.25), 1) AS avg_lifespan_years
            FROM customers c
            WHERE c.first_purchase_date IS NOT NULL
            GROUP BY clv_tier
            ORDER BY FIELD(clv_tier, 'Platinum', 'Gold', 'Silver', 'Bronze')";
    break;
```

---

## 4. JAVASCRIPT INSIGHTS FOR CLV

```javascript
case 'clv':
    insights = `<ul>
        <li><strong>CLV Distribution:</strong> ${labels.length} distinct customer value tiers identified</li>
        <li><strong>Premium Customers (Platinum + Gold):</strong> ${(data[0] + (data[1]||0)).toLocaleString()} customers represent ${(((data[0] + (data[1]||0))/totalCustomers)*100).toFixed(1)}% of base but drive significant revenue</li>
        <li><strong>Highest Value Tier:</strong> ${labels[0]} customers with average CLV of $${results.length > 0 && results[0].avg_clv ? Math.max(...results.map(r => parseFloat(r.avg_clv))).toLocaleString() : '0'}</li>
        <li><strong>Customer Lifespan:</strong> Premium tiers average ${results.length > 0 && results[0].avg_lifespan_years ? Math.max(...results.map(r => parseFloat(r.avg_lifespan_years))).toFixed(1) : '0'} years with company</li>
        <li><strong>Growth Opportunity:</strong> ${data[data.length - 1] || 0} Bronze-tier customers ($0-$3k CLV) are potential upsell targets</li>
        <li><strong>Retention Strategy:</strong> Focus on maintaining Platinum tier; implement loyalty programs for Gold tier progression</li>
        <li><strong>Marketing ROI:</strong> Target Platinum/Gold for premium offerings; Bronze for value promotions</li>
    </ul>`;
    break;
```

---

## 5. CHART TYPE RECOMMENDATION & JUSTIFICATION

### **PRIMARY: Horizontal Stacked Bar Chart**
**Why:**
- Shows tier names on Y-axis (more readable than vertical for tier labels)
- Stacked segments show customer count distribution
- Color-coded: Bronze=Gray, Silver=Silver, Gold=Gold, Platinum=Purple
- Easy to compare tier sizes and composition

**Benefits:**
- Tier names are primary identifiers → horizontal is better UX
- Shows both absolute count and relative composition
- Professional appearance for executive reporting

### **SECONDARY: Waterfall Chart (if available)**
**Why:**
- Shows progression through tiers: Bronze → Silver → Gold → Platinum
- Highlights how many customers "upgrade" from each tier
- Emphasizes business growth pathway
- Ideal for retention/churn analysis

### **TERTIARY: Scatter Plot (Optional)**
**Why:**
- X-axis: Purchase Frequency, Y-axis: Purchase Amount
- Color by CLV Tier, Size by Customer Lifespan
- Shows the three CLV components visually
- Reveals natural clustering patterns

### **NOT RECOMMENDED: Pie Chart**
- Too many small slices if tiers are unbalanced
- Percentage alone doesn't show absolute numbers
- Tier names hard to read in compact spaces

---

## TIER DEFINITIONS & THRESHOLDS

| Tier | CLV Range | Target Behavior | Marketing Strategy |
|------|-----------|-----------------|-------------------|
| **Bronze** | $0 - $2,999 | New/inactive customers | Nurture campaigns, onboarding |
| **Silver** | $3,000 - $7,999 | Regular buyers | Cross-sell, loyalty rewards |
| **Gold** | $8,000 - $14,999 | Loyal customers | VIP perks, exclusive access |
| **Platinum** | $15,000+ | Best customers | White-glove service, advisory |

**CLV Formula:**
```
CLV = (Average Purchase Amount) × (Purchase Frequency) × (Customer Lifespan in Years)

Example:
- Avg Purchase: $500
- Frequency: 12 times/year
- Lifespan: 3 years
- CLV = $500 × 12 × 3 = $18,000 (Platinum)
```

---

## IMPLEMENTATION CHECKLIST

- [ ] Add columns to customers table (purchase_frequency, first_purchase_date, etc.)
- [ ] Backfill historical data if needed
- [ ] Add 'clv' case to POST switch in index.php
- [ ] Add JavaScript insights for CLV
- [ ] Create horizontal bar chart visualization
- [ ] Add CLV tier option to segmentation dropdown
- [ ] Test with sample data
- [ ] Export CLV data (verify columns in export)
- [ ] Add to export history tracking
- [ ] Document business rules in comments

