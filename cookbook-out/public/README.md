# Filipino Cookbook — Client Application

> **ITTE 105A · DMMMSU Mid-La Union Campus**
> Part 6: Driver / Client Program uploaded to GitHub

---

## 1. Application Title

**Filipino Cookbook Client** — *Lutuin Mo Na.*
A token-secured web client for the Filipino Cookbook REST API.

---

## 2. Application Description

This is a browser-based client application built for the **ITTE 105A Collaborative API Development and Integration Activity**. It consumes the Filipino Cookbook REST API developed by a classmate and presents the data through a polished, interactive user interface.

**What it does:**

- Authenticates with the API using a session-scoped Bearer token generated through an access portal
- Browses all Filipino foods stored in the database, with search support
- Views detailed instructions and ingredients for any dish in a modal dialog
- Lists all food categories and ingredients
- Submits new food entries via a form that POSTs to the API

The application never connects directly to the classmate's MySQL database — all data flows through the REST API endpoints using JSON.

**Intended users:** ITTE 105A students evaluating or demonstrating the Filipino Cookbook API.

---

## 3. Technologies Used

| Layer | Technology |
|---|---|
| Markup | HTML5 |
| Styling | CSS3 (custom design system, CSS variables, responsive grid) |
| Scripting | Vanilla JavaScript (ES2020+, Fetch API, sessionStorage) |
| Fonts | Google Fonts — Playfair Display, Inter, DM Mono |
| Server | PHP 8+ built-in server or Apache/XAMPP (via Slim routing in `index.php`) |
| API consumed | Slim Framework 4 REST API (classmate's repository) |
| Version control | Git / GitHub |

No front-end build tools or npm packages are required — the client runs as plain HTML/CSS/JS files served by PHP.

---

## 4. Installation Instructions

### Prerequisites

- PHP 8.0 or later
- The classmate's Filipino Cookbook API installed and running locally (see **API Source** below)
- A web browser (Chrome, Firefox, Edge)

### Steps

**1. Clone this repository**

```bash
git clone https://github.com/Stradlin/filipino-cookbook-client-pacano.git
cd filipino-cookbook-client-pacano
```

**2. Install dependencies**

```bash
cd cookbook-out
composer install
```

**3. Configure the database connection**

Open `cookbook-out/public/index.php` and update the credentials under `getDbConnection()`:

```php
$host = '127.0.0.1';
$db   = 'filipino_cookbook_api';
$user = 'YOUR_DATABASE_USERNAME';
$pass = 'YOUR_DATABASE_PASSWORD';
```

**4. Import the database**

- Open phpMyAdmin or your MySQL client
- Import `cookbook-out/filipino_foods_relational.sql`
- This creates the `filipino_cookbook_api` database with all tables and seed data

**5. Run the application**

Using the PHP built-in server:

```bash
php -S localhost:8000 -t cookbook-out/public
```

Or with XAMPP — place the project in `htdocs/` and start Apache and MySQL.

**6. Open the access portal**

```
http://localhost:8000/
```

**7. Generate an access key and enter the app**

Click **Generate Access Key** → wait for the token → click **Enter Cookbook**.

---

## 5. API Endpoints Used

| Method | Endpoint | Where Used |
|---|---|---|
| `GET` | `/api/status` | Sidebar API status indicator (no auth required) |
| `GET` | `/api/foods` | All Foods view — loads and displays the food grid |
| `GET` | `/api/foods/{id}` | Food detail modal — fetches full details on card click |
| `GET` | `/api/foods/search/{name}` | Search bar — live filtered food results |
| `GET` | `/api/categories` | Categories view + Add Food form dropdown |
| `GET` | `/api/ingredients` | Ingredients view + Add Food ingredient checklist |
| `POST` | `/api/foods` | Add Food form — submits a new dish to the database |

All authenticated endpoints require the header:

```
Authorization: Bearer dmmmsu-cookbook-token-2026
```

---

## 6. Screenshots

### Access Portal — Token Generator
> *Screenshot needed: Take a screenshot of `http://localhost:8000/` showing the Generate Access Key button and token display.*

---

### Overview
![Overview](screenshots/ss-overview.png)
*Main screen showing the app layout, sidebar navigation, bearer token display with countdown, and the API endpoint reference list.*

---

### All Foods
![All Foods](screenshots/ss-all-foods.png)
*Food card grid displaying all 15 Filipino dishes with category badges, origin, truncated instructions, and ingredient pills.*

---

### Food Detail Modal
> *Screenshot needed: Click any food card and take a screenshot of the modal showing the dish name, full instructions, and ingredient list.*

---

### Search
> *Screenshot needed: Type a food name in the search bar (e.g. "adobo") and take a screenshot of the filtered results.*

---

### Categories
![Categories](screenshots/ss-categories.png)
*Categories list showing all 7 food categories retrieved from the API.*

---

### Ingredients
![Ingredients](screenshots/ss-ingredients.png)
*Full ingredients list showing all 60 ingredients retrieved from the API.*

---

### Add Food Form
> *Screenshot needed: Click Add Food in the sidebar and take a screenshot of the form with the fields and ingredient checkboxes.*

---

### Add Food — Success
> *Screenshot needed: Submit a valid food entry and take a screenshot of the green success message.*

---

### Expired Token
> *Screenshot needed: Wait for the token to expire (or generate one and wait) and take a screenshot showing the red "Expired" badge and disabled Enter Cookbook button on the portal.*

---

## 7. API Source and Acknowledgment

This client application consumes the Filipino Cookbook REST API developed by a classmate as part of the same ITTE 105A activity.

```
API Source

This client application uses the Filipino Cookbook API developed by:

  Developer:         [Name of Classmate]
  GitHub Username:   [GitHub Username]
  GitHub Repository: [Repository Link]
  Base URL:          http://localhost/filipino-cookbook-api-[surname]/public

The API is used for educational purposes with the permission of the developer.
```

---

## Project Structure

```
filipino-cookbook-client-pacano/
├── cookbook-out/
│   ├── composer.json
│   ├── composer.lock
│   ├── filipino_foods_relational.sql
│   ├── intrucs.md
│   └── public/
│       ├── .htaccess           # Apache rewrite rules
│       ├── index.php           # Slim Framework router + API routes + UI routes
│       ├── index.html          # Access portal — token generator
│       ├── app.html            # Main cookbook UI
│       ├── app.css             # Shared design system
│       └── app.js              # API client logic
├── screenshots/                # UI screenshots
└── README.md                   # This file
```

---

## Developer Information

| Field | Detail |
|---|---|
| **Student Name** | Stradlin Kurt N. Rillera |
| **Course & Section** | BSIT — ITTE 105A |
| **Institution** | DMMMSU Mid-La Union Campus |
| **GitHub Username** | Stradlin |
| **Repository** | [filipino-cookbook-client-pacano](https://github.com/Stradlin/filipino-cookbook-client-pacano) |
| **Date Completed** | July 2026 |
