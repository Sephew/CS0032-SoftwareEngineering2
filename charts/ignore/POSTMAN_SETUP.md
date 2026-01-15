# Postman Setup Guide - Customer Segmentation API

## Overview
This guide walks you through importing and using the Postman collection for testing the Customer Segmentation API.

---

## 0. PREREQUISITE: Start XAMPP

Before using Postman, ensure XAMPP is running:

1. Open **XAMPP Control Panel**
2. Click **Start** for Apache
3. Verify it shows "Running" (green)
4. Open browser: http://localhost/ → should show XAMPP dashboard
5. Verify csapp works: http://localhost/csapp → should show dashboard

**API is now ready at**: `http://localhost/csapp/api`

---

## 1. INSTALLATION & SETUP

### Step 1: Download & Install Postman
- Download from: https://www.postman.com/downloads/
- Available for Windows, Mac, and Linux
- Create a free account or use Postman directly

### Step 2: Import Collection
1. Open Postman
2. Click **Import** button (top-left)
3. Select **Upload Files** or paste raw JSON
4. Choose: `Postman_Collection.json`
5. Click **Import**

### Step 3: Import Environment
1. Click the **Environments** tab (left sidebar)
2. Click **Import**
3. Select: `Postman_Environment.json`
4. Click **Import**

### Step 4: Select Environment
1. In the top-right, find the **Environment** dropdown (currently says "No Environment")
2. Select: **Customer Segmentation API - Development**
3. Verify the base_url shows: `http://localhost/csapp/api`

---

## 2. AUTHENTICATION SETUP

### Prerequisite: Obtain JWT Token
Before testing any authenticated endpoints, you must get a valid JWT token.

**Steps:**
1. In Postman, navigate to **Authentication** → **Login (Get JWT Token)**
2. Update the request body with valid credentials:
   ```json
   {
     "username": "user@example.com",
     "password": "secure_password"
   }
   ```
3. Click **Send**
4. The token will automatically be saved to the `access_token` environment variable
5. You'll see in response:
   ```json
   {
     "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
     "token_type": "Bearer",
     "expires_in": 3600
   }
   ```

### Token Auto-Refresh (Optional)
You can set up automatic token refresh by using Postman's pre-request scripts. Edit the environment and add a refresh script to automatically get a new token if expired.

---

## 3. MAKING API REQUESTS

### Example 1: Get CLV Segments

1. Click **Segments** → **Get CLV Segments**
2. Verify you have a valid token (should be set automatically from login)
3. Click **Send**
4. Response should show:
   ```json
   {
     "success": true,
     "data": {
       "segmentation_type": "clv",
       "total_records": 4,
       "segments": [
         {
           "clv_tier": "Platinum",
           "total_customers": 45,
           ...
         }
       ]
     }
   }
   ```

### Example 2: Get Customer Segments

1. Click **Customers** → **Get Customer Segment (ID: 12345)**
2. This uses a hardcoded customer ID (12345)
3. To use a different customer ID:
   - Use **Get Customer Segment (Variable ID)**
   - In Environment, change `customer_id` variable to your desired ID
4. Click **Send**

### Example 3: Run Clustering Job

1. Click **Clusters** → **Run Clustering (K-Means)**
2. The request body includes parameters:
   ```json
   {
     "n_clusters": 5,
     "algorithm": "kmeans",
     "features": ["income", "purchase_amount", "purchase_frequency"],
     "normalize": true,
     "random_state": 42
   }
   ```
3. Click **Send**
4. Response includes `job_id` (auto-saved to environment)
5. Monitor progress with **Check Clustering Job Status**

### Example 4: Export Data

1. Click **Exports** → **Export CLV as Excel**
2. Request body:
   ```json
   {
     "format": "excel",
     "include_charts": true,
     "columns": ["clv_tier", "total_customers", "avg_clv", "avg_income"]
   }
   ```
3. Click **Send**
4. Response includes `export_id` and `download_url`

---

## 4. UNDERSTANDING RESPONSE CODES

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Proceed with result |
| 202 | Accepted (Async) | Check job status later |
| 400 | Bad Request | Check request parameters |
| 401 | Unauthorized | Re-authenticate (get new token) |
| 429 | Rate Limited | Wait before retrying |
| 500 | Server Error | Check server logs |

---

## 5. RESPONSE HEADERS - Rate Limiting

When you make requests, check the **Headers** tab in the response:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1673532645
```

**Meaning:**
- **X-RateLimit-Limit**: Max requests allowed per hour
- **X-RateLimit-Remaining**: How many requests you have left
- **X-RateLimit-Reset**: Unix timestamp when limit resets

---

## 6. VIEWING RESPONSES

### Response Tabs

1. **Body** - JSON response data
2. **Headers** - Response headers (rate limiting info)
3. **Status** - HTTP status code
4. **Test Results** - Any test scripts that ran

### Pretty Print
By default, JSON is formatted. Click the dropdown next to "Body" to see raw JSON or other formats.

---

## 7. COMMON SCENARIOS

### Scenario 1: Get All Platinum Customers
1. **Get CLV Segments** → Filter results for `clv_tier == "Platinum"`
2. Note the `total_customers` count
3. Use **Get Customer Segment** to get individual customer data

### Scenario 2: Run New Clustering & Monitor
1. **Run Clustering (K-Means)** - captures `job_id`
2. Wait a few seconds
3. **Check Clustering Job Status** - automatically uses captured `job_id`
4. When status is "completed", proceed to get results

### Scenario 3: Export & Download
1. **Export CLV as Excel** - captures `export_id`
2. Response includes `download_url`
3. Copy the `download_url`
4. Open in browser or download via **Export/exp_*/download**

---

## 8. SETTING CUSTOM VARIABLES

### Add a Custom Variable

1. Click **Environments** tab
2. Click **Customer Segmentation API - Development**
3. Under "Variable", add new rows:
   - **Key**: `my_segmentation_type`
   - **Value**: `gender`
   - Click **Save**

### Use in Request

In any request URL or body, use: `{{my_segmentation_type}}`

**Example:**
```
GET {{base_url}}/segments/{{my_segmentation_type}}
```

---

## 9. PRE-REQUEST & TEST SCRIPTS

### Pre-Request Script (runs before request)
- Used to set variables or authenticate
- Example: Auto-refresh JWT token

### Test Script (runs after response)
- Validate response
- Extract values to environment
- Set up test assertions

### Example Test Script:
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response contains success flag", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.be.true;
});

// Extract job_id if present
if (pm.response.code === 202) {
    var jsonData = pm.response.json();
    pm.environment.set("job_id", jsonData.data.job_id);
}
```

---

## 10. SHARING & COLLABORATION

### Export for Sharing
1. Right-click collection name
2. **Export** → Save as JSON
3. Share file with team

### Import from Shared File
1. Team members click **Import**
2. Upload the JSON file
3. Select environment if provided

### Using Postman Cloud
1. Sign into Postman account
2. Collections auto-sync to cloud
3. Team members can access shared collections

---

## 11. TROUBLESHOOTING

### Problem: 401 Unauthorized
**Solution:**
- Click **Authentication** → **Login (Get JWT Token)**
- Verify credentials in request body
- Click **Send** to get new token
- Token will auto-populate in environment

### Problem: 429 Rate Limited
**Solution:**
- Check `X-RateLimit-Remaining` header
- Wait until `X-RateLimit-Reset` time passes
- Use Postman's **Run Collection** with delays between requests

### Problem: 500 Server Error
**Solution:**
- Check API server is running
- Verify `base_url` is correct
- Check server logs for detailed error

### Problem: Variables Not Working
**Solution:**
- Verify environment is selected (top-right dropdown)
- Check variable spelling (case-sensitive)
- Variables use `{{variable_name}}` syntax

---

## 12. POSTMAN COLLECTION STRUCTURE

```
Customer Segmentation API
├── Authentication
│   └── Login (Get JWT Token)
├── Segments
│   ├── Get CLV Segments
│   ├── Get Gender Segments
│   ├── Get Region Segments
│   ├── Get Age Group Segments
│   ├── Get Income Bracket Segments
│   ├── Get Purchase Tier Segments
│   └── Get Cluster Segments (Detailed)
├── Clusters
│   ├── List All Clusters
│   ├── Run Clustering (K-Means)
│   └── Check Clustering Job Status
├── Customers
│   ├── Get Customer Segment (ID: 12345)
│   └── Get Customer Segment (Variable ID)
├── Insights
│   ├── Get CLV Insights
│   ├── Get Gender Insights
│   ├── Get Region Insights
│   └── Get Cluster Insights
├── Exports
│   ├── Export CLV as CSV
│   ├── Export CLV as Excel
│   ├── Export CLV as PDF
│   ├── Export Gender as CSV
│   └── Export Region as Excel
└── Health & System
    └── API Health Check
```

---

## 13. RUNNING COLLECTIONS PROGRAMMATICALLY

### Using Postman CLI (newman)

**Install:**
```bash
npm install -g newman
```

**Run Collection:**
```bash
newman run Postman_Collection.json \
  --environment Postman_Environment.json \
  --reporters cli,json \
  --reporter-json-export results.json
```

**With Rate Limiting:**
```bash
newman run Postman_Collection.json \
  --environment Postman_Environment.json \
  --delay-request 1000
```

---

## 14. POSTMAN PRO FEATURES (Optional)

- **Monitoring**: Set up recurring collection runs
- **Mock Server**: Mock API before backend is ready
- **Documentation**: Auto-generate API docs
- **CI/CD Integration**: Run tests in pipeline

---

## Quick Reference

| Task | Steps |
|------|-------|
| Get Token | Auth → Login → Send |
| Get CLV Data | Segments → Get CLV Segments → Send |
| Run Clustering | Clusters → Run Clustering → Send |
| Check Status | Clusters → Check Job Status → Send |
| Export Data | Exports → Choose format → Send |
| View Headers | Response → Headers tab |
| Change Base URL | Environment dropdown → Edit → Change base_url |
| Monitor Rate Limit | Response → Headers → X-RateLimit-* |

