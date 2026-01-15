# RESTful API Design for Customer Segmentation System

## Overview
This document defines a RESTful API to expose segmentation data to external applications with proper authentication, rate limiting, and comprehensive endpoint documentation.

---

## 1. API ENDPOINTS SPECIFICATION

### 1.1 GET /api/segments/{type}
**Description**: Retrieve segmentation results for a specific segmentation type
**HTTP Method**: GET
**Authentication**: Required (Bearer Token)
**Rate Limit**: 100 requests/hour

**URL Parameters**:
- `type` (string, required): Gender, region, age_group, income_bracket, cluster, purchase_tier, clv

**Query Parameters**:
- `limit` (integer, optional): Max records to return (default: 50, max: 500)
- `offset` (integer, optional): Pagination offset (default: 0)
- `sort_by` (string, optional): Field to sort by (e.g., total_customers)
- `sort_order` (string, optional): ASC or DESC (default: DESC)

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "segmentation_type": "clv",
    "total_records": 4,
    "limit": 50,
    "offset": 0,
    "segments": [
      {
        "clv_tier": "Platinum",
        "total_customers": 45,
        "avg_purchase_amount": 5200.50,
        "avg_frequency": 8.2,
        "avg_clv": 15620.00,
        "avg_income": 85000.00,
        "avg_lifespan_years": 5.8
      },
      {
        "clv_tier": "Gold",
        "total_customers": 120,
        "avg_purchase_amount": 3800.25,
        "avg_frequency": 6.5,
        "avg_clv": 10200.00,
        "avg_income": 72000.00,
        "avg_lifespan_years": 4.2
      }
    ]
  },
  "metadata": {
    "request_id": "req_abc123def456",
    "timestamp": "2026-01-12T12:30:45Z",
    "api_version": "1.0"
  }
}
```

**Error Responses**:
- `400 Bad Request`: Invalid segmentation type
- `401 Unauthorized`: Missing or invalid authentication
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

---

### 1.2 GET /api/clusters
**Description**: Retrieve all available clusters with metadata
**HTTP Method**: GET
**Authentication**: Required (Bearer Token)
**Rate Limit**: 100 requests/hour

**Query Parameters**:
- `include_metadata` (boolean, optional): Include cluster feature descriptions (default: true)
- `include_stats` (boolean, optional): Include cluster statistics (default: true)

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "total_clusters": 3,
    "clusters": [
      {
        "cluster_id": 1,
        "cluster_label": "High-Value Premium Customers",
        "cluster_name": "Premium Segment",
        "customer_count": 150,
        "avg_income": 95000.00,
        "avg_purchase_amount": 4500.00,
        "description": "Affluent customers with high purchase frequency"
      },
      {
        "cluster_id": 2,
        "cluster_label": "Budget-Conscious Shoppers",
        "cluster_name": "Value Seekers",
        "customer_count": 280,
        "avg_income": 42000.00,
        "avg_purchase_amount": 1200.00,
        "description": "Price-sensitive customers looking for deals"
      }
    ]
  },
  "metadata": {
    "request_id": "req_xyz789abc",
    "timestamp": "2026-01-12T12:31:15Z",
    "api_version": "1.0"
  }
}
```

---

### 1.3 POST /api/clusters/run
**Description**: Trigger a new clustering analysis with optional parameters
**HTTP Method**: POST
**Authentication**: Required (Bearer Token)
**Rate Limit**: 10 requests/hour (Limited - expensive operation)

**Request Body**:
```json
{
  "n_clusters": 5,
  "algorithm": "kmeans",
  "features": ["income", "purchase_amount", "purchase_frequency"],
  "normalize": true,
  "random_state": 42
}
```

**Response (202 Accepted)**:
```json
{
  "success": true,
  "data": {
    "job_id": "job_clust_20260112_1",
    "status": "processing",
    "message": "Clustering job queued successfully",
    "estimated_time_seconds": 120
  },
  "metadata": {
    "request_id": "req_job123",
    "timestamp": "2026-01-12T12:32:00Z",
    "api_version": "1.0"
  }
}
```

**Polling Endpoint**: GET /api/clusters/jobs/{job_id}
- Returns current status and results when complete

---

### 1.4 GET /api/customers/{id}/segment
**Description**: Get segment classification for a specific customer
**HTTP Method**: GET
**Authentication**: Required (Bearer Token)
**Rate Limit**: 100 requests/hour

**URL Parameters**:
- `id` (integer, required): Customer ID

**Query Parameters**:
- `include_insights` (boolean, optional): Include segment insights (default: false)

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "customer_id": 12345,
    "name": "John Smith",
    "email": "john@example.com",
    "segments": {
      "gender": "Male",
      "region": "North America",
      "age_group": "26-40",
      "income_bracket": "High Income (>70k)",
      "cluster_id": 2,
      "cluster_label": "High-Value Premium Customers",
      "purchase_tier": "High Spender (>3k)",
      "clv_tier": "Platinum",
      "calculated_clv": 15620.00
    },
    "insights": {
      "value_score": 95,
      "churn_risk": "Low",
      "product_affinity": ["Electronics", "Premium Services"]
    }
  },
  "metadata": {
    "request_id": "req_cust12345",
    "timestamp": "2026-01-12T12:33:20Z",
    "api_version": "1.0"
  }
}
```

---

### 1.5 GET /api/insights/{type}
**Description**: Retrieve AI-generated insights for a segmentation type
**HTTP Method**: GET
**Authentication**: Required (Bearer Token)
**Rate Limit**: 100 requests/hour

**URL Parameters**:
- `type` (string, required): Segmentation type (gender, region, clv, etc.)

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "segmentation_type": "clv",
    "generated_at": "2026-01-12T12:34:00Z",
    "insights": [
      {
        "title": "Platinum Tier Dominance",
        "description": "Platinum customers (45) generate 42% of total revenue despite being only 8% of the customer base",
        "severity": "high",
        "recommendation": "Implement VIP retention program for Platinum customers"
      },
      {
        "title": "Silver-to-Gold Migration Opportunity",
        "description": "150 Silver tier customers are 1 purchase away from Gold tier threshold",
        "severity": "medium",
        "recommendation": "Create targeted upsell campaigns for Silver customers"
      }
    ],
    "key_metrics": {
      "total_customers": 560,
      "avg_clv": 8450.00,
      "revenue_concentration": "Gini coefficient: 0.68"
    }
  },
  "metadata": {
    "request_id": "req_insights_clv",
    "timestamp": "2026-01-12T12:34:30Z",
    "api_version": "1.0"
  }
}
```

---

### 1.6 POST /api/export/{type}
**Description**: Request export of segmentation data in various formats
**HTTP Method**: POST
**Authentication**: Required (Bearer Token)
**Rate Limit**: 20 requests/hour

**URL Parameters**:
- `type` (string, required): Segmentation type

**Request Body**:
```json
{
  "format": "csv",
  "include_charts": true,
  "columns": ["segment_name", "total_customers", "avg_purchase_amount"],
  "filters": {
    "min_customers": 10
  }
}
```

**Response (202 Accepted)**:
```json
{
  "success": true,
  "data": {
    "export_id": "exp_20260112_001",
    "status": "processing",
    "format": "csv",
    "estimated_size_mb": 2.5,
    "download_url": "/api/exports/exp_20260112_001/download",
    "expires_at": "2026-01-13T12:34:30Z"
  }
}
```

---

### 1.7 GET /api/health
**Description**: Health check endpoint (no authentication required)
**HTTP Method**: GET
**Authentication**: Not required
**Rate Limit**: Unlimited

**Response (200 OK)**:
```json
{
  "status": "healthy",
  "timestamp": "2026-01-12T12:35:00Z",
  "components": {
    "database": "connected",
    "cache": "operational",
    "api_version": "1.0"
  }
}
```

---

## 2. DETAILED JSON RESPONSE FORMAT FOR /api/segments/cluster

```json
{
  "success": true,
  "data": {
    "segmentation_type": "cluster",
    "request_summary": {
      "total_records": 3,
      "limit": 50,
      "offset": 0,
      "has_more": false
    },
    "segments": [
      {
        "cluster_id": 1,
        "cluster_label": "High-Value Premium Customers",
        "cluster_name": "Premium Segment",
        "cluster_priority": 1,
        "customer_count": 150,
        "customer_percentage": 26.8,
        "demographics": {
          "avg_age": 42,
          "age_range_min": 35,
          "age_range_max": 58,
          "predominant_gender": "Male",
          "gender_distribution": {
            "Male": 65,
            "Female": 85
          },
          "regions": ["North America", "Europe"]
        },
        "financial_metrics": {
          "avg_income": 95000.00,
          "median_income": 92000.00,
          "income_range": {
            "min": 70000,
            "max": 150000
          },
          "avg_purchase_amount": 4500.00,
          "total_purchases": 675000.00,
          "avg_purchase_frequency": 12.5,
          "avg_clv": 18750.00
        },
        "behavioral_metrics": {
          "avg_customer_lifespan_months": 48,
          "repeat_purchase_rate": 0.92,
          "churn_rate": 0.08,
          "engagement_score": 92
        },
        "features": {
          "top_products": ["Premium Services", "Electronics", "Home Goods"],
          "preferred_channels": ["Online", "Mobile"],
          "avg_order_value": 450.00,
          "avg_cart_value": 520.00
        },
        "metadata": {
          "created_at": "2026-01-05T10:15:30Z",
          "last_updated": "2026-01-12T08:30:00Z",
          "member_count_change": "+5",
          "description": "Affluent customers with high purchase frequency and strong loyalty"
        }
      },
      {
        "cluster_id": 2,
        "cluster_label": "Budget-Conscious Shoppers",
        "cluster_name": "Value Seekers",
        "cluster_priority": 2,
        "customer_count": 280,
        "customer_percentage": 50.0,
        "demographics": {
          "avg_age": 35,
          "age_range_min": 25,
          "age_range_max": 50,
          "predominant_gender": "Female",
          "gender_distribution": {
            "Male": 120,
            "Female": 160
          },
          "regions": ["North America", "Asia"]
        },
        "financial_metrics": {
          "avg_income": 42000.00,
          "median_income": 40000.00,
          "income_range": {
            "min": 25000,
            "max": 70000
          },
          "avg_purchase_amount": 1200.00,
          "total_purchases": 336000.00,
          "avg_purchase_frequency": 6.2,
          "avg_clv": 4200.00
        },
        "behavioral_metrics": {
          "avg_customer_lifespan_months": 24,
          "repeat_purchase_rate": 0.45,
          "churn_rate": 0.55,
          "engagement_score": 52
        },
        "features": {
          "top_products": ["Home Goods", "Apparel", "Basics"],
          "preferred_channels": ["In-store", "Email"],
          "avg_order_value": 120.00,
          "avg_cart_value": 145.00
        },
        "metadata": {
          "created_at": "2026-01-05T10:15:30Z",
          "last_updated": "2026-01-12T08:30:00Z",
          "member_count_change": "+12",
          "description": "Price-sensitive customers looking for deals and value"
        }
      }
    ]
  },
  "metadata": {
    "request_id": "req_cluster_20260112_001",
    "timestamp": "2026-01-12T12:36:00Z",
    "api_version": "1.0",
    "response_time_ms": 245
  },
  "links": {
    "self": "/api/segments/cluster",
    "insights": "/api/insights/cluster",
    "export": "/api/export/cluster",
    "cluster_details": "/api/clusters"
  }
}
```

---

## 3. AUTHENTICATION IMPLEMENTATION

### 3.1 Recommended: JWT (JSON Web Token) Authentication

**Flow**:
1. Client requests token via POST /api/auth/login
2. Server validates credentials, issues JWT token
3. Client includes token in Authorization header for subsequent requests
4. Server validates token signature and expiration

**Implementation Details**:

```
POST /api/auth/login
Content-Type: application/json

{
  "username": "user@example.com",
  "password": "secure_password"
}

Response (200 OK):
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Token Structure**:
- Header: Algorithm (HS256) and token type
- Payload: user_id, email, roles, iat (issued at), exp (expiration)
- Signature: HMAC-SHA256(header.payload, secret_key)

**Usage in API Calls**:
```
GET /api/segments/clv HTTP/1.1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Token Validation**:
- Check signature validity
- Verify not expired
- Validate user has required permissions/scopes

---

### 3.2 Alternative: API Key Authentication

**Implementation**:
- Client generates API key via dashboard
- Include key in header: `X-API-Key: sk_live_abc123def456`
- Server validates key against database
- Simpler but less secure (no expiration)

---

### 3.3 OAuth 2.0 (for Third-Party Apps)

**Flow**:
1. Third-party app redirects user to authorization endpoint
2. User grants permissions
3. App receives authorization code
4. App exchanges code for access token
5. App uses token to access API

---

## 4. RATE LIMITING PSEUDOCODE

### 4.1 Rate Limiter Implementation (Token Bucket Algorithm)

```pseudocode
CLASS RateLimiter:
    FUNCTION __init__(max_requests_per_hour, cleanup_interval_minutes = 60):
        this.max_requests = max_requests_per_hour
        this.window_seconds = 3600  // 1 hour
        this.token_bucket = {}      // user_id -> {tokens, last_refill}
        this.cleanup_interval = cleanup_interval_minutes * 60
        START_BACKGROUND_CLEANUP(this.cleanup_expired_entries, this.cleanup_interval)
    
    FUNCTION check_rate_limit(user_id):
        current_time = GET_CURRENT_TIMESTAMP()
        
        IF user_id NOT IN this.token_bucket:
            this.token_bucket[user_id] = {
                tokens: this.max_requests,
                last_refill: current_time,
                created_at: current_time
            }
            RETURN {
                allowed: true,
                remaining: this.max_requests - 1,
                reset_at: current_time + this.window_seconds
            }
        
        user_bucket = this.token_bucket[user_id]
        time_passed = current_time - user_bucket.last_refill
        
        // Refill tokens based on time passed
        IF time_passed >= this.window_seconds:
            user_bucket.tokens = this.max_requests
            user_bucket.last_refill = current_time
        ELSE:
            // Proportional refill (tokens regenerate over time)
            tokens_to_add = (time_passed / this.window_seconds) * this.max_requests
            user_bucket.tokens = MIN(this.max_requests, user_bucket.tokens + tokens_to_add)
            user_bucket.last_refill = current_time
        
        // Check if request is allowed
        IF user_bucket.tokens >= 1:
            user_bucket.tokens -= 1
            remaining = FLOOR(user_bucket.tokens)
            RETURN {
                allowed: true,
                remaining: remaining,
                reset_at: current_time + this.window_seconds,
                retry_after_seconds: null
            }
        ELSE:
            retry_after = this.window_seconds - time_passed + 1
            RETURN {
                allowed: false,
                remaining: 0,
                reset_at: current_time + this.window_seconds,
                retry_after_seconds: retry_after
            }
    
    FUNCTION cleanup_expired_entries():
        current_time = GET_CURRENT_TIMESTAMP()
        expired_users = []
        
        FOR EACH user_id, bucket IN this.token_bucket:
            IF current_time - bucket.created_at > 86400:  // 24 hours
                expired_users.APPEND(user_id)
        
        FOR EACH user_id IN expired_users:
            DELETE this.token_bucket[user_id]
            LOG("Cleaned up rate limit bucket for user: " + user_id)


// Middleware to enforce rate limiting
FUNCTION rate_limit_middleware(request, response, next):
    user_id = EXTRACT_USER_ID_FROM_TOKEN(request)
    endpoint = request.path
    
    // Different limits for different endpoints
    IF endpoint.STARTS_WITH("/api/clusters/run"):
        limiter = GET_RATE_LIMITER(10)  // 10 req/hour
    ELSE IF endpoint.STARTS_WITH("/api/export"):
        limiter = GET_RATE_LIMITER(20)  // 20 req/hour
    ELSE IF endpoint.EQUALS("/api/health"):
        CALL next()  // No limit for health check
        RETURN
    ELSE:
        limiter = GET_RATE_LIMITER(100)  // 100 req/hour (default)
    
    limit_result = limiter.check_rate_limit(user_id)
    
    // Set response headers
    response.setHeader("X-RateLimit-Limit", limiter.max_requests)
    response.setHeader("X-RateLimit-Remaining", limit_result.remaining)
    response.setHeader("X-RateLimit-Reset", limit_result.reset_at)
    
    IF NOT limit_result.allowed:
        response.setHeader("Retry-After", limit_result.retry_after_seconds)
        response.status(429)
        response.json({
            success: false,
            error: "Too Many Requests",
            message: "Rate limit exceeded. Try again after " + limit_result.retry_after_seconds + " seconds",
            retry_after_seconds: limit_result.retry_after_seconds
        })
        RETURN
    
    CALL next()
```

### 4.2 Rate Limit Response Headers

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1673532645
Retry-After: 300  (when rate limit exceeded)
```

---

## 5. API ERROR CODES

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 202 | Accepted | Request accepted for async processing |
| 400 | Bad Request | Invalid parameters |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Duplicate request/resource conflict |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service maintenance |

---

## 6. SECURITY BEST PRACTICES

1. **HTTPS Only**: All endpoints use HTTPS
2. **CORS**: Configure Cross-Origin Resource Sharing
3. **Input Validation**: Validate all inputs server-side
4. **SQL Injection Prevention**: Use parameterized queries
5. **XSS Prevention**: Sanitize outputs
6. **CSRF Protection**: Include CSRF tokens for state-changing operations
7. **Logging**: Log all API access and errors
8. **Monitoring**: Monitor for suspicious activity
9. **API Versioning**: Support multiple API versions for backward compatibility

