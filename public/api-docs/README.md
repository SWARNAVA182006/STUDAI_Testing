# StudAI Career Platform API Documentation

## Overview

The StudAI Career Platform API allows you to programmatically manage jobs, applications, and company data. This RESTful API uses standard HTTP methods and returns JSON responses.

**Base URL**: `https://api.studai.com/api/v1`

## Authentication

All API requests require authentication using an API token. Include your token in the `Authorization` header:

```http
Authorization: Bearer YOUR_API_TOKEN
```

### Generating API Tokens

1. Log in to your employer dashboard
2. Navigate to Settings → API Tokens
3. Click "Generate New Token"
4. Select permissions and rate limit
5. Copy the token (it will only be shown once!)

### Token Permissions

Tokens can have the following abilities:

- `jobs.read` - Read job data
- `jobs.write` - Create, update, and delete jobs
- `applications.read` - Read application data
- `applications.write` - Update application statuses
- `company.read` - Read company profile
- `company.write` - Update company profile
- `webhooks.manage` - Manage webhooks
- `*` - All permissions

## Rate Limiting

API requests are rate-limited per token. Default limits:

- **Free tier**: 60 requests/minute
- **Pro tier**: 300 requests/minute
- **Enterprise tier**: 1000 requests/minute

Rate limit headers are included in all responses:

```http
X-RateLimit-Limit: 300
X-RateLimit-Remaining: 299
X-RateLimit-Reset: 1672531200
```

When the limit is exceeded, you'll receive a `429 Too Many Requests` response with a `Retry-After` header.

## Webhooks

Webhooks allow you to receive real-time notifications about events in your account.

### Available Events

- `application.received` - New application submitted
- `application.status_changed` - Application status updated
- `interview.scheduled` - Interview scheduled
- `interview.completed` - Interview completed
- `interview.cancelled` - Interview cancelled
- `job.published` - Job published
- `job.closed` - Job closed
- `candidate.hired` - Candidate hired

### Setting Up Webhooks

1. Navigate to Settings → Webhooks
2. Click "Create Webhook"
3. Enter your endpoint URL
4. Select events to subscribe to
5. Save and copy the webhook secret

### Webhook Payload Format

```json
{
  "event": "application.received",
  "timestamp": "2024-01-18T10:30:00Z",
  "data": {
    "application_id": 123,
    "job_id": 456,
    "job_title": "Senior Software Engineer",
    "candidate_id": 789,
    "candidate_name": "John Doe",
    "candidate_email": "john@example.com",
    "applied_at": "2024-01-18T10:30:00Z",
    "status": "received"
  }
}
```

### Verifying Webhook Signatures

All webhook requests include an `X-Webhook-Signature` header. Verify it to ensure the request came from StudAI:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (hash_equals($expectedSignature, $signature)) {
    // Valid webhook
} else {
    // Invalid signature
}
```

## API Endpoints

### Company

#### Get Company Profile
```http
GET /company
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Tech Corp",
    "description": "Leading technology company",
    "industry": "Technology",
    "website": "https://techcorp.com",
    "location": "San Francisco, CA"
  }
}
```

#### Get Company Statistics
```http
GET /company/statistics
```

**Response**:
```json
{
  "success": true,
  "data": {
    "total_jobs": 25,
    "active_jobs": 12,
    "total_applications": 487,
    "new_applications_today": 15,
    "total_hires": 38
  }
}
```

### Jobs

#### List Jobs
```http
GET /jobs?status=published&per_page=20
```

**Query Parameters**:
- `status` - Filter by job status (draft, published, closed)
- `category` - Filter by job category
- `employment_type` - Filter by employment type
- `search` - Search in title and description
- `per_page` - Results per page (max 100)

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Senior Software Engineer",
      "category": "engineering",
      "employment_type": "full_time",
      "work_mode": "remote",
      "status": "published",
      "published_at": "2024-01-15T09:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

#### Create Job
```http
POST /jobs
```

**Request Body**:
```json
{
  "title": "Senior Software Engineer",
  "description": "We are looking for...",
  "category": "engineering",
  "location": "San Francisco, CA",
  "work_mode": "remote",
  "employment_type": "full_time",
  "required_skills": ["Python", "Django", "PostgreSQL"],
  "salary_min": 120000,
  "salary_max": 180000,
  "application_method": "internal",
  "expires_in_days": 30
}
```

**Response**: `201 Created`

#### Update Job
```http
PUT /jobs/{job_id}
```

**Request Body**:
```json
{
  "status": "closed"
}
```

### Applications

#### List Applications
```http
GET /applications?job_id=123&status=shortlisted
```

**Query Parameters**:
- `job_id` - Filter by job
- `status` - Filter by status
- `from_date` - Filter by date range
- `to_date` - Filter by date range
- `match_score_min` - Filter by minimum match score
- `per_page` - Results per page (max 100)

#### Update Application Status
```http
PUT /applications/{application_id}/status
```

**Request Body**:
```json
{
  "status": "interview_scheduled",
  "notes": "Scheduled for next Tuesday"
}
```

#### Bulk Update Status
```http
POST /applications/bulk-status
```

**Request Body**:
```json
{
  "application_ids": [123, 456, 789],
  "status": "rejected",
  "notes": "Position filled"
}
```

## Error Handling

The API uses standard HTTP status codes:

- `200 OK` - Request successful
- `201 Created` - Resource created
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Invalid or missing API token
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

**Error Response Format**:
```json
{
  "error": "Forbidden",
  "message": "This API token does not have the 'jobs.write' permission"
}
```

## Code Examples

### PHP

```php
$token = 'your_api_token';
$baseUrl = 'https://api.studai.com/api/v1';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/jobs');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);
```

### JavaScript

```javascript
const token = 'your_api_token';
const baseUrl = 'https://api.studai.com/api/v1';

fetch(`${baseUrl}/jobs`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Python

```python
import requests

token = 'your_api_token'
base_url = 'https://api.studai.com/api/v1'

headers = {
    'Authorization': f'Bearer {token}',
    'Content-Type': 'application/json'
}

response = requests.get(f'{base_url}/jobs', headers=headers)
data = response.json()
```

## Support

For API support, contact: api@studai.com
