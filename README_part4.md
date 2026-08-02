# Part 4 — Selected Classmate API

**Activity:** ITTE 105A — GitHub Activity (API Development)
**Student:** Rillera, Stradlin Kurt Nimer
**GitHub:** [exilleon](https://github.com/exilleon)
**Client Repository:** [filipino-cookbook-client-rillera](https://github.com/exilleon/filipino-cookbook-client-rillera)

---

## API Information Record

| Field | Details |
|---|---|
| **Name of API Developer** | Pacano, Lyron Dave M. |
| **GitHub Username** | `norylonacap` |
| **Repository Name** | `filipino-cookbook-api-pacano` |
| **Repository Link** | https://github.com/norylonacap/filipino-cookbook-api-pacano |
| **Base URL** | `http://localhost:8000/api` |
| **Authentication Method** | Bearer token — `Authorization: Bearer dmmmsu-cookbook-token-2026` |

---

## Available Endpoints

| Method | Endpoint | Auth Required | Description |
|---|---|---|---|
| GET | `/` | No | Welcome message |
| GET | `/api/status` | No | Health check |
| GET | `/api/foods` | Yes | All foods with category, origin, and ingredients |
| GET | `/api/foods/random` | Yes | One randomly selected food |
| GET | `/api/foods/{id}` | Yes | Single food by numeric ID |
| GET | `/api/foods/search/{name}` | Yes | Search foods by name (partial match) |
| GET | `/api/categories` | Yes | All food categories |
| GET | `/api/ingredients` | Yes | All ingredients |
| POST | `/api/foods` | Yes | Add a new food (returns 201 Created) |

---

## Selected Endpoints

The following endpoints were chosen as the data source for the client application:

| Endpoint | Used For |
|---|---|
| `GET /api/foods` | Browse all Filipino dishes |
| `GET /api/foods/random` | "Surprise Me" discovery feature |
| `GET /api/foods/{id}` | View full dish details |
| `GET /api/foods/search/{name}` | Search bar |
| `GET /api/categories` | Category filter / navigation |
| `POST /api/foods` | Add a new dish form |

---

## Installation Instructions

### Requirements

- PHP >= 8.0
- MySQL (via XAMPP or standalone)
- Composer (optional — `vendor/` is already committed)

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/norylonacap/filipino-cookbook-api-pacano.git
cd filipino-cookbook-api-pacano

# 2. Install dependencies (skip if vendor/ is already present)
composer install

# 3. Copy and configure the config file
cp config.example.php config.php
```

Edit `config.php` with your local database credentials:

```php
return [
    'db_host'    => '127.0.0.1',
    'db_name'    => 'filipino_cookbook_api',
    'db_user'    => 'root',
    'db_pass'    => '',
    'db_charset' => 'utf8mb4',
    'api_token'  => 'dmmmsu-cookbook-token-2026',
];
```

```bash
# 4. Import the database
mysql -u root -p < filipino_foods_relational.sql

# 5. Start the API server
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`.

---

## Authentication

All `/api/*` routes require a Bearer token in the request header:

```
Authorization: Bearer dmmmsu-cookbook-token-2026
```

Missing or incorrect token returns `401 Unauthorized`:

```json
{
  "status": "error",
  "message": "Unauthorized access. Valid API token is required."
}
```

---

## API Testing Results

### Testing Tool Used
Thunder Client (VS Code extension)

### Test Results

| Check | Result |
|---|---|
| Repository can be cloned | ✅ Cloned successfully |
| SQL file is present | ✅ `filipino_foods_relational.sql` included |
| Dependencies can be installed | ✅ `vendor/` already committed; `composer install` also works |
| API runs successfully | ✅ `php -S localhost:8000 -t public` |
| Authentication works | ✅ Bearer token validated by middleware |
| Endpoints return valid JSON | ✅ All responses use `Content-Type: application/json` |
| Error responses are understandable | ✅ Consistent `{"status":"error","message":"..."}` format |
| Documentation matches actual behavior | ✅ README, `API_DOCUMENTATION.md`, and `public/index.php` are consistent |

### Sample Test — GET /api/foods

**Request:**
```
GET http://localhost:8000/api/foods
Authorization: Bearer dmmmsu-cookbook-token-2026
```

**Response (200 OK):**
```json
[
  {
    "food_id": 1,
    "food_name": "Adobo",
    "category_name": "Main Dish",
    "origin_name": "Manila",
    "instructions": "Simmer pork or chicken in soy sauce, vinegar, and garlic.",
    "ingredients": ["Bay Leaves", "Garlic", "Pork", "Soy Sauce", "Vinegar"]
  }
]
```

### Sample Test — GET /api/foods/random

**Request:**
```
GET http://localhost:8000/api/foods/random
Authorization: Bearer dmmmsu-cookbook-token-2026
```

**Response (200 OK):**
```json
{
  "food_id": 7,
  "food_name": "Sinigang",
  "category_name": "Soup",
  "origin_name": "Batangas",
  "instructions": "Boil pork or shrimp in a tamarind-based sour broth with vegetables.",
  "ingredients": ["Kangkong", "Pork", "Radish", "Tamarind", "Tomato"]
}
```

### Sample Test — Invalid Token

**Request:**
```
GET http://localhost:8000/api/foods
Authorization: Bearer wrong-token
```

**Response (401 Unauthorized):**
```json
{
  "status": "error",
  "message": "Unauthorized access. Valid API token is required."
}
```

---

## Problems Encountered

No problems were encountered during installation or testing.

- SQL file was present and imported without errors
- `config.example.php` template was included with clear instructions
- `vendor/` folder was committed, so `composer install` is optional
- CORS headers are enabled — browser-based clients can call the API directly
- Rate limiting is documented (30 requests per 60 seconds per IP) and implemented in the source code

---

## Notes for Client Development

1. **Rate limiting is enforced.** The API allows 30 requests per 60 seconds per IP. The client handles `429 Too Many Requests` responses with a user-friendly message.
2. **No login step required.** The Bearer token is static and documented — it is stored in the client's config and sent with every request.
3. **CORS is enabled** (`Access-Control-Allow-Origin: *`). Browser-based fetch calls work without a proxy.
4. **`GET /api/foods/random`** is a custom enhancement by the developer — used as a "Surprise Me" feature in the client.
5. **`POST /api/foods`** requires a JSON body with `food_name`, `category_id`, `origin_id`, `instructions`, and `ingredient_ids` (array of integer IDs).

---

## HTTP Status Codes Reference

| Code | Meaning |
|---|---|
| 200 | Request completed successfully |
| 201 | Resource created successfully |
| 400 | Invalid request or missing parameter |
| 401 | Missing or invalid authentication token |
| 404 | Requested resource was not found |
| 429 | Too many requests (rate limit exceeded) |
| 500 | Internal server error |
