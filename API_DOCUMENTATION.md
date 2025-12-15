# Tanin Reservations API Documentation

## Configuration

```
BASE_URL = https://tanin-backend-main-c7nzu9.laravel.cloud/
```

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
   - [Login](#login)
3. [Response Format](#response-format)
4. [Status Codes](#status-codes)
5. [Reservation Statuses](#reservation-statuses)
6. [Statistics API](#statistics-api)
   - [Daily Stats](#daily-stats)
7. [Bookings API](#bookings-api)
   - [List Bookings](#list-bookings)
   - [Create Booking](#create-booking)
   - [Get Booking](#get-booking)
   - [Approve Booking](#approve-booking)
   - [Decline Booking](#decline-booking)
8. [Private Reservations API](#private-reservations-api)
   - [List Private Reservations](#list-private-reservations)
   - [Create Private Reservation](#create-private-reservation)
   - [Get Private Reservation](#get-private-reservation)
   - [Approve Private Reservation](#approve-private-reservation)
   - [Decline Private Reservation](#decline-private-reservation)
9. [Error Handling](#error-handling)
10. [Race Condition Handling](#race-condition-handling)

---

## Overview

The Tanin Reservations API provides endpoints for managing two types of reservations:

1. **Bookings**: Standard table reservations for dining/drinks
2. **Private Reservations**: Private event inquiries (birthdays, weddings, corporate events, etc.)

All endpoints return JSON responses and use standard HTTP methods (GET, POST, PATCH).

---

## Authentication

The API provides a simple login endpoint to validate admin credentials.

### Login

Validate admin credentials. Returns `success: true` if credentials match, `success: false` otherwise.

**Endpoint**

```
POST {BASE_URL}/api/login
```

**Request Headers**

```
Content-Type: application/json
Accept: application/json
```

**Request Body**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | Yes | Admin email address |
| `password` | string | Yes | Admin password |

**Example Request**

```bash
curl -X POST "{BASE_URL}/api/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "tanin@admin.com",
    "password": "your_password_here"
  }'
```

**Success Response (200 OK)**

```json
{
    "success": true,
    "message": "Login successful."
}
```

**Failed Response (200 OK)**

```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

> **Note**: This is a simple credential check. For production use with session management, consider implementing Laravel Sanctum for token-based authentication.

---

## Response Format

### Success Response

```json
{
    "success": true,
    "message": "Optional success message",
    "data": { ... },
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

---

## Status Codes

| Code | Description |
|------|-------------|
| `200` | Success - Request completed successfully |
| `201` | Created - Resource created successfully |
| `400` | Bad Request - Invalid request parameters |
| `404` | Not Found - Resource does not exist |
| `409` | Conflict - Resource already processed (race condition) |
| `422` | Unprocessable Entity - Validation errors |
| `500` | Internal Server Error |

---

## Reservation Statuses

| Status Code | Label | Description |
|-------------|-------|-------------|
| `0` | Pending | Awaiting review |
| `1` | Accepted | Reservation approved |
| `2` | Declined | Reservation rejected |

---

## Statistics API

Get aggregated statistics for reservations.

### Daily Stats

Get reservation statistics for a specific date, including total reservations and headcount.

**Endpoint**

```
GET {BASE_URL}/api/stats/daily
```

**Query Parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `date` | string | Yes | Date to get stats for (YYYY-MM-DD) |

**Example Request**

```bash
curl -X GET "{BASE_URL}/api/stats/daily?date=2025-02-14" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "data": {
        "date": "2025-02-14",
        "total_reservations": 7,
        "total_accepted": 5,
        "total_headcount": 55,
        "bookings": {
            "count": 5,
            "accepted": 4,
            "headcount": 15
        },
        "private_reservations": {
            "count": 2,
            "accepted": 1,
            "headcount_estimate": 40
        }
    }
}
```

**Response Fields**

| Field | Description |
|-------|-------------|
| `total_reservations` | Total number of all reservations (bookings + private) for the date |
| `total_accepted` | Number of accepted reservations |
| `total_headcount` | Combined headcount from accepted bookings and estimated private event guests |
| `bookings.count` | Number of standard bookings |
| `bookings.accepted` | Number of accepted bookings |
| `bookings.headcount` | Exact guest count from accepted bookings |
| `private_reservations.count` | Number of private event inquiries |
| `private_reservations.accepted` | Number of accepted private reservations |
| `private_reservations.headcount_estimate` | Estimated guests based on people range |

**People Range Estimates**

Private reservations use people ranges, which are converted to estimates:

| Range | Estimate |
|-------|----------|
| `under10` | 5 people |
| `10to30` | 20 people |
| `30to50` | 40 people |
| `over50` | 60 people |

---

## Bookings API

Standard table reservations for dining and drinks.

### Data Model

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| `id` | integer | Unique identifier | Auto-generated |
| `date` | string | Reservation date (YYYY-MM-DD) | Yes |
| `time` | string | Reservation time (HH:MM) | Yes |
| `guests` | integer | Number of guests (1-10) | Yes |
| `reservation_type` | string | Type: `dining`, `drinks`, or `both` | Yes |
| `phone` | string | Contact phone number | Yes |
| `status` | integer | Status: 0, 1, or 2 | Auto-set to 0 |
| `created_at` | datetime | Creation timestamp | Auto-generated |
| `updated_at` | datetime | Last update timestamp | Auto-generated |

---

### List Bookings

Retrieve a paginated list of bookings with optional filters. Results are ordered by date ascending (soonest first), then by time.

**Endpoint**

```
GET {BASE_URL}/api/bookings
```

**Query Parameters**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | integer | Filter by status (0, 1, 2) | `?status=0` |
| `date` | string | Filter by exact date (YYYY-MM-DD) | `?date=2025-02-14` |
| `date_from` | string | Start date filter (YYYY-MM-DD) | `?date_from=2025-01-01` |
| `date_to` | string | End date filter (YYYY-MM-DD) | `?date_to=2025-12-31` |
| `reservation_type` | string | Filter by type | `?reservation_type=dining` |
| `per_page` | integer | Results per page (1-100, default: 15) | `?per_page=25` |
| `page` | integer | Page number | `?page=2` |

**Example Request**

```bash
curl -X GET "{BASE_URL}/api/bookings?status=0&per_page=10" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "date": "2025-02-14",
            "time": "19:00",
            "guests": 2,
            "reservation_type": "dining",
            "phone": "+381601234567",
            "status": 0,
            "created_at": "2025-01-15T10:30:00.000000Z",
            "updated_at": "2025-01-15T10:30:00.000000Z"
        },
        {
            "id": 2,
            "date": "2025-02-15",
            "time": "20:00",
            "guests": 4,
            "reservation_type": "both",
            "phone": "+381607654321",
            "status": 0,
            "created_at": "2025-01-15T11:45:00.000000Z",
            "updated_at": "2025-01-15T11:45:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 10,
        "total": 25
    }
}
```

---

### Create Booking

Create a new table reservation.

**Endpoint**

```
POST {BASE_URL}/api/bookings
```

**Request Headers**

```
Content-Type: application/json
Accept: application/json
```

**Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `date` | string | Yes | Format: YYYY-MM-DD, must be today or future |
| `time` | string | Yes | Format: HH:MM (e.g., "18:00", "19:30") |
| `guests` | integer | Yes | Min: 1, Max: 10 |
| `reservation_type` | string | Yes | One of: `dining`, `drinks`, `both` |
| `phone` | string | Yes | Max: 50 characters |

**Example Request**

```bash
curl -X POST "{BASE_URL}/api/bookings" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "date": "2025-02-20",
    "time": "19:00",
    "guests": 4,
    "reservation_type": "dining",
    "phone": "+381601234567"
  }'
```

**Example Response (201 Created)**

```json
{
    "success": true,
    "message": "Booking created successfully.",
    "data": {
        "id": 5,
        "date": "2025-02-20",
        "time": "19:00",
        "guests": 4,
        "reservation_type": "dining",
        "phone": "+381601234567",
        "status": 0,
        "created_at": "2025-01-20T14:30:00.000000Z",
        "updated_at": "2025-01-20T14:30:00.000000Z"
    }
}
```

**Validation Error Response (422 Unprocessable Entity)**

```json
{
    "message": "The reservation date must be today or a future date.",
    "errors": {
        "date": ["The reservation date must be today or a future date."],
        "guests": ["Maximum 10 guests allowed per booking."]
    }
}
```

---

### Get Booking

Retrieve a specific booking by ID.

**Endpoint**

```
GET {BASE_URL}/api/bookings/{id}
```

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Booking ID |

**Example Request**

```bash
curl -X GET "{BASE_URL}/api/bookings/5" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "data": {
        "id": 5,
        "date": "2025-02-20",
        "time": "19:00",
        "guests": 4,
        "reservation_type": "dining",
        "phone": "+381601234567",
        "status": 0,
        "created_at": "2025-01-20T14:30:00.000000Z",
        "updated_at": "2025-01-20T14:30:00.000000Z"
    }
}
```

**Error Response (404 Not Found)**

```json
{
    "success": false,
    "message": "Booking not found."
}
```

---

### Approve Booking

Approve a pending booking. Changes status from 0 (pending) to 1 (accepted).

**Endpoint**

```
PATCH {BASE_URL}/api/bookings/{id}/approve
```

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Booking ID |

**Example Request**

```bash
curl -X PATCH "{BASE_URL}/api/bookings/5/approve" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "message": "Booking approved successfully.",
    "data": {
        "id": 5,
        "date": "2025-02-20",
        "time": "19:00",
        "guests": 4,
        "reservation_type": "dining",
        "phone": "+381601234567",
        "status": 1,
        "created_at": "2025-01-20T14:30:00.000000Z",
        "updated_at": "2025-01-20T15:00:00.000000Z"
    }
}
```

**Error Response (409 Conflict)**

```json
{
    "success": false,
    "message": "Booking has already been processed. Current status: accepted"
}
```

---

### Decline Booking

Decline a pending booking. Changes status from 0 (pending) to 2 (declined).

**Endpoint**

```
PATCH {BASE_URL}/api/bookings/{id}/decline
```

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Booking ID |

**Example Request**

```bash
curl -X PATCH "{BASE_URL}/api/bookings/5/decline" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "message": "Booking declined successfully.",
    "data": {
        "id": 5,
        "date": "2025-02-20",
        "time": "19:00",
        "guests": 4,
        "reservation_type": "dining",
        "phone": "+381601234567",
        "status": 2,
        "created_at": "2025-01-20T14:30:00.000000Z",
        "updated_at": "2025-01-20T15:00:00.000000Z"
    }
}
```

---

## Private Reservations API

Private event inquiries for birthdays, weddings, corporate events, etc.

### Data Model

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| `id` | integer | Unique identifier | Auto-generated |
| `date` | string | Event date (YYYY-MM-DD) | Yes |
| `email` | string | Contact email | Yes |
| `event_type` | string | Event type | Yes |
| `people_range` | string | Expected guest count range | Yes |
| `budget` | string | Budget range | Yes |
| `message` | string | Additional details | No |
| `status` | integer | Status: 0, 1, or 2 | Auto-set to 0 |
| `created_at` | datetime | Creation timestamp | Auto-generated |
| `updated_at` | datetime | Last update timestamp | Auto-generated |

**Event Types**

| Value | Description |
|-------|-------------|
| `birthday` | Birthday celebration |
| `anniversary` | Anniversary event |
| `corporate` | Corporate/business event |
| `wedding` | Wedding reception |
| `other` | Other type of event |

**People Ranges**

| Value | Description |
|-------|-------------|
| `under10` | Less than 10 people |
| `10to30` | 10-30 people |
| `30to50` | 30-50 people |
| `over50` | More than 50 people |

**Budget Ranges**

| Value | Description |
|-------|-------------|
| `under1000` | Under 1,000 |
| `1000to3000` | 1,000 - 3,000 |
| `3000to5000` | 3,000 - 5,000 |
| `5000to10000` | 5,000 - 10,000 |
| `over10000` | Over 10,000 |

---

### List Private Reservations

Retrieve a paginated list of private reservations with optional filters. Results are ordered by date ascending (soonest first), then by creation time.

**Endpoint**

```
GET {BASE_URL}/api/private-reservations
```

**Query Parameters**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `status` | integer | Filter by status (0, 1, 2) | `?status=0` |
| `date` | string | Filter by exact date (YYYY-MM-DD) | `?date=2025-02-14` |
| `date_from` | string | Start date filter (YYYY-MM-DD) | `?date_from=2025-01-01` |
| `date_to` | string | End date filter (YYYY-MM-DD) | `?date_to=2025-12-31` |
| `event_type` | string | Filter by event type | `?event_type=wedding` |
| `per_page` | integer | Results per page (1-100, default: 15) | `?per_page=25` |
| `page` | integer | Page number | `?page=2` |

**Example Request**

```bash
curl -X GET "{BASE_URL}/api/private-reservations?status=0&event_type=corporate" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "date": "2025-03-15",
            "email": "events@company.com",
            "event_type": "corporate",
            "people_range": "30to50",
            "budget": "5000to10000",
            "message": "Annual company dinner for 40 employees",
            "status": 0,
            "created_at": "2025-01-10T09:00:00.000000Z",
            "updated_at": "2025-01-10T09:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

---

### Create Private Reservation

Create a new private event inquiry.

**Endpoint**

```
POST {BASE_URL}/api/private-reservations
```

**Request Headers**

```
Content-Type: application/json
Accept: application/json
```

**Request Body**

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `date` | string | Yes | Format: YYYY-MM-DD, must be today or future |
| `email` | string | Yes | Valid email, max 255 characters |
| `event_type` | string | Yes | One of: `birthday`, `anniversary`, `corporate`, `wedding`, `other` |
| `people_range` | string | Yes | One of: `under10`, `10to30`, `30to50`, `over50` |
| `budget` | string | Yes | One of: `under1000`, `1000to3000`, `3000to5000`, `5000to10000`, `over10000` |
| `message` | string | No | Max 2000 characters |

**Example Request**

```bash
curl -X POST "{BASE_URL}/api/private-reservations" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "date": "2025-06-20",
    "email": "bride@example.com",
    "event_type": "wedding",
    "people_range": "30to50",
    "budget": "over10000",
    "message": "Wedding reception for 45 guests. Would like outdoor seating if possible."
  }'
```

**Example Response (201 Created)**

```json
{
    "success": true,
    "message": "Private reservation created successfully.",
    "data": {
        "id": 10,
        "date": "2025-06-20",
        "email": "bride@example.com",
        "event_type": "wedding",
        "people_range": "30to50",
        "budget": "over10000",
        "message": "Wedding reception for 45 guests. Would like outdoor seating if possible.",
        "status": 0,
        "created_at": "2025-01-20T16:00:00.000000Z",
        "updated_at": "2025-01-20T16:00:00.000000Z"
    }
}
```

---

### Get Private Reservation

Retrieve a specific private reservation by ID.

**Endpoint**

```
GET {BASE_URL}/api/private-reservations/{id}
```

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Private reservation ID |

**Example Request**

```bash
curl -X GET "{BASE_URL}/api/private-reservations/10" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "data": {
        "id": 10,
        "date": "2025-06-20",
        "email": "bride@example.com",
        "event_type": "wedding",
        "people_range": "30to50",
        "budget": "over10000",
        "message": "Wedding reception for 45 guests. Would like outdoor seating if possible.",
        "status": 0,
        "created_at": "2025-01-20T16:00:00.000000Z",
        "updated_at": "2025-01-20T16:00:00.000000Z"
    }
}
```

---

### Approve Private Reservation

Approve a pending private reservation. Changes status from 0 (pending) to 1 (accepted).

**Endpoint**

```
PATCH {BASE_URL}/api/private-reservations/{id}/approve
```

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Private reservation ID |

**Example Request**

```bash
curl -X PATCH "{BASE_URL}/api/private-reservations/10/approve" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "message": "Private reservation approved successfully.",
    "data": {
        "id": 10,
        "date": "2025-06-20",
        "email": "bride@example.com",
        "event_type": "wedding",
        "people_range": "30to50",
        "budget": "over10000",
        "message": "Wedding reception for 45 guests. Would like outdoor seating if possible.",
        "status": 1,
        "created_at": "2025-01-20T16:00:00.000000Z",
        "updated_at": "2025-01-20T17:00:00.000000Z"
    }
}
```

---

### Decline Private Reservation

Decline a pending private reservation. Changes status from 0 (pending) to 2 (declined).

**Endpoint**

```
PATCH {BASE_URL}/api/private-reservations/{id}/decline
```

**Path Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Private reservation ID |

**Example Request**

```bash
curl -X PATCH "{BASE_URL}/api/private-reservations/10/decline" \
  -H "Accept: application/json"
```

**Example Response (200 OK)**

```json
{
    "success": true,
    "message": "Private reservation declined successfully.",
    "data": {
        "id": 10,
        "date": "2025-06-20",
        "email": "bride@example.com",
        "event_type": "wedding",
        "people_range": "30to50",
        "budget": "over10000",
        "message": "Wedding reception for 45 guests. Would like outdoor seating if possible.",
        "status": 2,
        "created_at": "2025-01-20T16:00:00.000000Z",
        "updated_at": "2025-01-20T17:00:00.000000Z"
    }
}
```

---

## Error Handling

### Validation Errors (422)

When request validation fails, the API returns detailed error messages:

```json
{
    "message": "The date field is required.",
    "errors": {
        "date": ["The date field is required."],
        "email": ["The email field must be a valid email address."],
        "event_type": ["Invalid event type. Must be: birthday, anniversary, corporate, wedding, or other."]
    }
}
```

### Not Found Errors (404)

When a resource doesn't exist:

```json
{
    "success": false,
    "message": "Booking not found."
}
```

### Conflict Errors (409)

When trying to approve/decline an already processed reservation:

```json
{
    "success": false,
    "message": "Booking has already been processed. Current status: accepted"
}
```

---

## Race Condition Handling

The API uses **MySQL row-level locking** with database transactions to prevent race conditions when approving or declining reservations.

### How It Works

1. When an approve/decline request is received, the API starts a database transaction
2. The target row is locked using `SELECT ... FOR UPDATE` (via Laravel's `lockForUpdate()`)
3. The status is checked - if already processed, the request is rejected with a 409 Conflict
4. If pending, the status is updated and the transaction is committed
5. The lock is released automatically when the transaction ends

### Concurrent Request Scenario

If two admins try to approve the same reservation simultaneously:

1. **Request A** acquires the lock first
2. **Request B** waits for the lock
3. **Request A** updates status to "accepted" and releases lock
4. **Request B** acquires lock, sees status is no longer "pending"
5. **Request B** returns 409 Conflict: "Booking has already been processed"

This ensures data integrity and prevents double-processing of reservations.

---

## Quick Reference

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/login` | Validate admin credentials |

### Statistics Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/stats/daily?date=YYYY-MM-DD` | Get daily reservation stats and headcount |

### Bookings Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/bookings` | List bookings (with filters) |
| `POST` | `/api/bookings` | Create booking |
| `GET` | `/api/bookings/{id}` | Get booking |
| `PATCH` | `/api/bookings/{id}/approve` | Approve booking |
| `PATCH` | `/api/bookings/{id}/decline` | Decline booking |

### Private Reservations Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/private-reservations` | List private reservations (with filters) |
| `POST` | `/api/private-reservations` | Create private reservation |
| `GET` | `/api/private-reservations/{id}` | Get private reservation |
| `PATCH` | `/api/private-reservations/{id}/approve` | Approve private reservation |
| `PATCH` | `/api/private-reservations/{id}/decline` | Decline private reservation |

---

## Setup Instructions

1. **Configure Database**: Update `.env` with your MySQL credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tanin
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

2. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

3. **Start Server**:
   ```bash
   php artisan serve
   ```

4. **Update BASE_URL**: Replace the `BASE_URL` at the top of this document with your production URL.

---

*Last Updated: December 2025*

