# E-Commerce API Documentation

## Overview

This document provides comprehensive documentation for the E-Commerce REST API. The API follows RESTful conventions and uses JSON for all requests and responses.

**Base URL:** `https://your-domain.com/api/v1`

**Authentication:** Bearer Token (Sanctum)
**Content-Type:** `application/json`

## Authentication

### Login
```http
POST /api/v1/login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com"
        }
    }
}
```

### Register
```http
POST /api/v1/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

### Logout
```http
POST /api/v1/logout
```

**Headers:** `Authorization: Bearer {token}`

---

## Categories

### Get All Categories
```http
GET /api/v1/categories
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max: 100)
- `include` (string): Include relationships (products)

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Electronics",
            "slug": "electronics",
            "description": "Electronic devices and accessories",
            "image": "https://example.com/images/electronics.jpg",
            "parent_id": null,
            "is_active": true,
            "created_at": "2023-01-01T00:00:00.000000Z",
            "updated_at": "2023-01-01T00:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 50,
        "last_page": 4
    }
}
```

### Get Category Tree
```http
GET /api/v1/categories/tree
```

### Get Single Category
```http
GET /api/v1/categories/{id}
```

### Create Category (Admin Only)
```http
POST /api/v1/admin/categories
```

**Request Body:**
```json
{
    "name": "New Category",
    "slug": "new-category",
    "description": "Category description",
    "image": "https://example.com/image.jpg",
    "parent_id": 1,
    "is_active": true,
    "meta_title": "Meta Title",
    "meta_description": "Meta Description"
}
```

### Update Category (Admin Only)
```http
PUT /api/v1/admin/categories/{id}
```

### Delete Category (Admin Only)
```http
DELETE /api/v1/admin/categories/{id}
```

---

## Products

### Get All Products
```http
GET /api/v1/products
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `category_id` (integer): Filter by category
- `search` (string): Search term
- `sort` (string): Sort field (name, price, created_at)
- `order` (string): Sort direction (asc, desc)
- `min_price` (float): Minimum price filter
- `max_price` (float): Maximum price filter
- `in_stock` (boolean): Only in-stock products

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Laptop",
            "slug": "laptop",
            "description": "High-performance laptop",
            "short_description": "Powerful laptop for professionals",
            "sku": "LAPTOP-001",
            "price": 999.99,
            "sale_price": 899.99,
            "cost_price": 700.00,
            "stock_quantity": 50,
            "weight": 2.5,
            "dimensions": {
                "length": 35,
                "width": 25,
                "height": 2
            },
            "images": [
                "https://example.com/images/laptop1.jpg",
                "https://example.com/images/laptop2.jpg"
            ],
            "is_active": true,
            "is_featured": false,
            "category": {
                "id": 1,
                "name": "Electronics"
            },
            "created_at": "2023-01-01T00:00:00.000000Z",
            "updated_at": "2023-01-01T00:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

### Get Featured Products
```http
GET /api/v1/products/featured
```

### Search Products
```http
GET /api/v1/products/search
```

**Query Parameters:**
- `q` (string, required): Search query
- `category_id` (integer): Filter by category

### Get Related Products
```http
GET /api/v1/products/{id}/related
```

### Get Single Product
```http
GET /api/v1/products/{id}
```

### Create Product (Admin Only)
```http
POST /api/v1/admin/products
```

**Request Body:**
```json
{
    "name": "New Product",
    "slug": "new-product",
    "description": "Product description",
    "short_description": "Short description",
    "sku": "PROD-001",
    "price": 99.99,
    "sale_price": 79.99,
    "cost_price": 50.00,
    "stock_quantity": 100,
    "weight": 1.5,
    "dimensions": {
        "length": 10,
        "width": 8,
        "height": 5
    },
    "category_id": 1,
    "is_active": true,
    "is_featured": false,
    "seo": {
        "meta_title": "SEO Title",
        "meta_description": "SEO Description",
        "meta_keywords": "keyword1,keyword2"
    }
}
```

### Update Product (Admin Only)
```http
PUT /api/v1/admin/products/{id}
```

### Delete Product (Admin Only)
```http
DELETE /api/v1/admin/products/{id}
```

---

## Shopping Cart

### Get Cart
```http
GET /api/v1/cart
```

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 1,
        "session_id": null,
        "items": [
            {
                "id": 1,
                "product_id": 1,
                "product_name": "Laptop",
                "product_sku": "LAPTOP-001",
                "quantity": 2,
                "unit_price": 999.99,
                "total_price": 1999.98,
                "product": {
                    "id": 1,
                    "name": "Laptop",
                    "image": "https://example.com/laptop.jpg"
                }
            }
        ],
        "subtotal": 1999.98,
        "tax_amount": 160.00,
        "shipping_amount": 15.00,
        "discount_amount": 0.00,
        "total": 2174.98,
        "currency": "USD",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "updated_at": "2023-01-01T00:00:00.000000Z"
    }
}
```

### Add Item to Cart
```http
POST /api/v1/cart/items
```

**Request Body:**
```json
{
    "product_id": 1,
    "quantity": 2,
    "product_variant_id": null
}
```

### Update Cart Item
```http
PUT /api/v1/cart/items/{itemId}
```

**Request Body:**
```json
{
    "quantity": 3
}
```

### Remove Cart Item
```http
DELETE /api/v1/cart/items/{itemId}
```

### Clear Cart
```http
DELETE /api/v1/cart
```

### Apply Coupon
```http
POST /api/v1/cart/coupon
```

**Request Body:**
```json
{
    "code": "SAVE10"
}
```

### Remove Coupon
```http
DELETE /api/v1/cart/coupon
```

### Get Cart Summary
```http
GET /api/v1/cart/summary
```

---

## Orders

### Get User Orders
```http
GET /api/v1/orders
```

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `status` (string): Filter by status

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "order_number": "ORD-2023-001",
            "user_id": 1,
            "status": "delivered",
            "payment_status": "paid",
            "subtotal": 1999.98,
            "tax_amount": 160.00,
            "shipping_amount": 15.00,
            "discount_amount": 0.00,
            "total": 2174.98,
            "currency": "USD",
            "shipping_address": {
                "first_name": "John",
                "last_name": "Doe",
                "address_line_1": "123 Main St",
                "city": "New York",
                "state": "NY",
                "postal_code": "10001",
                "country": "USA"
            },
            "items": [
                {
                    "id": 1,
                    "product_id": 1,
                    "product_name": "Laptop",
                    "product_sku": "LAPTOP-001",
                    "quantity": 2,
                    "unit_price": 999.99,
                    "total_price": 1999.98
                }
            ],
            "created_at": "2023-01-01T00:00:00.000000Z",
            "updated_at": "2023-01-01T00:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 10,
        "last_page": 1
    }
}
```

### Create Order
```http
POST /api/v1/orders
```

**Request Body:**
```json
{
    "shipping_address": {
        "first_name": "John",
        "last_name": "Doe",
        "address_line_1": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "USA",
        "phone": "+1234567890",
        "email": "john@example.com"
    },
    "billing_address": {
        "first_name": "John",
        "last_name": "Doe",
        "address_line_1": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "USA"
    },
    "coupon_code": "SAVE10",
    "notes": "Please deliver after 5 PM"
}
```

### Get Single Order
```http
GET /api/v1/orders/{id}
```

### Cancel Order
```http
POST /api/v1/orders/{id}/cancel
```

**Request Body:**
```json
{
    "reason": "Customer requested cancellation"
}
```

### Track Order
```http
GET /api/v1/orders/{id}/track
```

---

## Payments

### Get User Payments
```http
GET /api/v1/payments
```

**Headers:** `Authorization: Bearer {token}`

### Process Payment
```http
POST /api/v1/orders/{id}/payment
```

**Request Body:**
```json
{
    "payment_method": "credit_card",
    "card_number": "4111111111111111",
    "card_expiry_month": 12,
    "card_expiry_year": 2025,
    "card_cvv": "123",
    "card_holder_name": "John Doe",
    "billing_address": {
        "first_name": "John",
        "last_name": "Doe",
        "address_line_1": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "USA"
    },
    "save_payment_method": false
}
```

### Refund Payment
```http
POST /api/v1/payments/{id}/refund
```

**Request Body:**
```json
{
    "amount": 100.00,
    "reason": "Customer requested refund",
    "notify_customer": true
}
```

### Get Payment Methods
```http
GET /api/v1/payments/methods
```

### Calculate Fees
```http
POST /api/v1/payments/calculate-fees
```

**Request Body:**
```json
{
    "amount": 100.00,
    "payment_method": "credit_card",
    "currency": "USD"
}
```

---

## Addresses

### Get User Addresses
```http
GET /api/v1/addresses
```

**Headers:** `Authorization: Bearer {token}`

### Create Address
```http
POST /api/v1/addresses
```

**Request Body:**
```json
{
    "type": "shipping",
    "first_name": "John",
    "last_name": "Doe",
    "company": "Acme Corp",
    "address_line_1": "123 Main St",
    "address_line_2": "Apt 4B",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "USA",
    "phone": "+1234567890",
    "email": "john@example.com",
    "is_default": true
}
```

### Update Address
```http
PUT /api/v1/addresses/{id}
```

### Delete Address
```http
DELETE /api/v1/addresses/{id}
```

### Set Default Address
```http
POST /api/v1/addresses/{id}/set-default
```

### Validate Address
```http
POST /api/v1/addresses/validate
```

### Get Countries
```http
GET /api/v1/addresses/countries
```

### Get States
```http
GET /api/v1/addresses/countries/{countryCode}/states
```

---

## Wishlists

### Get User Wishlists
```http
GET /api/v1/wishlists
```

**Headers:** `Authorization: Bearer {token}`

### Create Wishlist
```http
POST /api/v1/wishlists
```

**Request Body:**
```json
{
    "name": "My Wishlist",
    "description": "Products I want to buy",
    "is_public": false
}
```

### Add Item to Wishlist
```http
POST /api/v1/wishlists/{id}/items
```

**Request Body:**
```json
{
    "product_id": 1,
    "quantity": 1,
    "priority": "high",
    "notes": "Really want this!"
}
```

### Remove Item from Wishlist
```http
DELETE /api/v1/wishlists/{id}/items/{itemId}
```

### Move to Cart
```http
POST /api/v1/wishlists/{id}/items/{itemId}/move-to-cart
```

---

## Coupons

### Validate Coupon
```http
POST /api/v1/coupons/validate
```

**Request Body:**
```json
{
    "code": "SAVE10",
    "subtotal": 100.00,
    "user_id": 1
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "discount_amount": 10.00,
        "discount_type": "percentage",
        "message": "Coupon applied successfully"
    }
}
```

### Apply Coupon
```http
POST /api/v1/coupons/apply
```

**Headers:** `Authorization: Bearer {token}`

---

## Error Responses

All error responses follow this format:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "email": ["The email field is required."],
            "password": ["The password field is required."]
        }
    }
}
```

### Common Error Codes

- `UNAUTHORIZED` (401): Authentication failed
- `FORBIDDEN` (403): Permission denied
- `NOT_FOUND` (404): Resource not found
- `VALIDATION_ERROR` (422): Validation failed
- `RATE_LIMIT_EXCEEDED` (429): Too many requests
- `SERVER_ERROR` (500): Internal server error

---

## Rate Limiting

API requests are limited to:
- **60 requests per minute** per authenticated user
- **100 requests per minute** for unauthenticated requests

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Total requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `X-RateLimit-Reset`: Time when limit resets (Unix timestamp)

---

## Pagination

Paginated responses include meta information:

```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "from": 1,
        "to": 15
    },
    "links": {
        "first": "https://api.example.com/products?page=1",
        "last": "https://api.example.com/products?page=7",
        "prev": null,
        "next": "https://api.example.com/products?page=2"
    }
}
```

---

## Webhooks

### Order Status Webhook

When an order status changes, a webhook can be sent to your configured URL:

**Endpoint:** Your configured webhook URL
**Method:** POST
**Headers:** `X-Webhook-Signature`: HMAC signature

**Payload:**
```json
{
    "event": "order.status.changed",
    "data": {
        "order_id": 1,
        "order_number": "ORD-2023-001",
        "old_status": "processing",
        "new_status": "shipped",
        "timestamp": "2023-01-01T12:00:00Z"
    }
}
```

---

## SDK Examples

### JavaScript (Axios)

```javascript
const api = axios.create({
    baseURL: 'https://your-domain.com/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Add authentication token
api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

// Get products
const getProducts = async (page = 1) => {
    try {
        const response = await api.get('/products', {
            params: { page, per_page: 15 }
        });
        return response.data;
    } catch (error) {
        console.error('API Error:', error.response.data);
        throw error;
    }
};

// Create order
const createOrder = async (orderData) => {
    try {
        const response = await api.post('/orders', orderData);
        return response.data;
    } catch (error) {
        console.error('Order creation failed:', error.response.data);
        throw error;
    }
};
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api/v1',
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token
    ]
]);

// Get products
$response = $client->get('products', [
    'query' => ['page' => 1, 'per_page' => 15]
]);
$products = json_decode($response->getBody(), true);

// Create order
$response = $client->post('orders', [
    'json' => $orderData
]);
$order = json_decode($response->getBody(), true);
```

---

## Testing

### Postman Collection

A Postman collection is available at:
`/docs/postman/E-Commerce-API.postman_collection.json`

### Environment Variables

- `base_url`: https://your-domain.com/api/v1
- `token`: Your authentication token

---

## Support

For API support and questions:
- Email: api-support@your-domain.com
- Documentation: https://docs.your-domain.com
- Status Page: https://status.your-domain.com

---

*Last updated: January 2024*
