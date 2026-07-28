# Filipino Cookbook — Client Application

> **ITTE 105A · DMMMSU Mid-La Union Campus**
> Part 6: Driver / Client Program uploaded to GitHub

---

## 1. Application Title

**Filipino Cookbook Client** — _Lutuin Mo Na._
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

| Layer           | Technology                                                                 |
| --------------- | -------------------------------------------------------------------------- |
| Markup          | HTML5                                                                      |
| Styling         | CSS3 (custom design system, CSS variables, responsive grid)                |
| Scripting       | Vanilla JavaScript (ES2020+, Fetch API, sessionStorage)                    |
| Fonts           | Google Fonts — Playfair Display, Inter, DM Mono                            |
| Server          | PHP 8+ built-in server _or_ Apache/XAMPP (via Slim routing in `index.php`) |
| API consumed    | Slim Framework 4 REST API (classmate's repository)                         |
| Version control | Git / GitHub                                                               |

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

**2. Place the project in your server directory**

If using XAMPP, copy the `cookbook-out/` folder into your `htdocs/` directory:

```
C:\xampp\htdocs\filipino-cookbook-client-pacano\
```

**3. Configure the API connection**

Open `cookbook-out/public/index.php` and confirm the database credentials under `getDbConnection()` match your local MySQL setup:

```php
$host = '127.0.0.1';
$db   = 'filipino_cookbook_api';
$user = 'YOUR_DATABASE_USERNAME';   // default XAMPP: root
$pass = 'YOUR_DATABASE_PASSWORD';   // default XAMPP: (empty)
```

**4. Import the database**

- Open phpMyAdmin or your preferred MySQL client
- Import `cookbook-out/filipino_foods_relational.sql`
- This creates the `filipino_cookbook_api` database with all tables and seed data

**5. Start Apache and MySQL** (XAMPP Control Panel)

**6. Run the application**

Alternatively, use the PHP built-in server:

```bash
cd cookbook-out/public
php -S localhost:8000
```

**7. Open the access portal**

Navigate to:

```
http://localhost:8000/
```

or (XAMPP):

```
http://localhost/filipino-cookbook-client-pacano/cookbook-out/public/
```

**8. Generate an access key and enter the app**

Click **Generate Access Key** → **Enter Cookbook**.

---

## 5. API Endpoints Used

The following endpoints of the classmate's Filipino Cookbook API are consumed by this application:

| Method | Endpoint                   | Where Used                                             |
| ------ | -------------------------- | ------------------------------------------------------ |
| `GET`  | `/api/status`              | Sidebar API status indicator (no auth required)        |
| `GET`  | `/api/foods`               | All Foods view — loads and displays the food grid      |
| `GET`  | `/api/foods/{id}`          | Food detail modal — fetches full details on card click |
| `GET`  | `/api/foods/search/{name}` | Search bar — live filtered food results                |
| `GET`  | `/api/categories`          | Categories view + Add Food form dropdown               |
| `GET`  | `/api/ingredients`         | Ingredients view + Add Food ingredient checklist       |
| `POST` | `/api/foods`               | Add Food form — submits a new dish to the database     |

All authenticated endpoints require the header:

```
Authorization: Bearer dmmmsu-cookbook-token-2026
```

---

## 6. Screenshots

> **Note:** Screenshots below should be added to a `screenshots/` folder in the repository root and linked here. Add one screenshot per item listed.

| Screenshot                         | Description                                          |
| ---------------------------------- | ---------------------------------------------------- |
| `screenshots/01-access-portal.png` | Access portal — token generation screen              |
| `screenshots/02-overview.png`      | App main screen — Overview / endpoint list           |
| `screenshots/03-all-foods.png`     | All Foods view — food cards grid                     |
| `screenshots/04-food-modal.png`    | Food detail modal with instructions and ingredients  |
| `screenshots/05-search.png`        | Live search results filtered by name                 |
| `screenshots/06-categories.png`    | Categories list view                                 |
| `screenshots/07-ingredients.png`   | Ingredients list view                                |
| `screenshots/08-add-food.png`      | Add Food form with category/ingredient selectors     |
| `screenshots/09-add-success.png`   | Successful food submission confirmation              |
| `screenshots/10-expired-token.png` | Expired token state — Enter Cookbook button disabled |

---

## 7. API Source and Acknowledgment

This client application consumes the Filipino Cookbook REST API developed by a classmate as part of the same ITTE 105A activity.

```
API Source

This client application uses the Filipino Cookbook API developed by:

  Developer:         [Name of Classmate]
  GitHub Username:   [GitHub Username]
  GitHub Repository: [Repository Link]
  Base URL:          https://github.com/norylonacap/filipino-cookbook-client-pacano.git

The API is used for educational purposes with the permission of the developer.
```

> **To complete this section:** replace the bracketed placeholders with your classmate's actual name, GitHub username, and repository link.

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
│       ├── .htaccess           # Apache rewrite rules (routes everything through index.php)
│       ├── index.php           # Slim Framework router + API proxy + UI routes
│       ├── index.html          # Access portal — token generator
│       ├── app.html            # Main cookbook UI
│       ├── app.css             # Shared design system
│       └── app.js              # API client logic (fetch, view router, modal)
├── screenshots/                # UI screenshots (add before submitting)
└── README.md                   # This file
```

---

## Developer Information

| Field                | Detail                                                                                                 |
| -------------------- | ------------------------------------------------------------------------------------------------------ |
| **Student Name**     | Stradlin Kurt N. Rillera                                                                               |
| **Course & Section** | BSIT — ITTE 105A                                                                                       |
| **Institution**      | DMMMSU Mid-La Union Campus                                                                             |
| **GitHub Username**  | exilleon                                                                                               |
| **Repository**       | [filipino-cookbook-client-pacano](<(https://github.com/exilleon/filipino-cookbook-client-pacano.git)>) |
| **Date Completed**   | July 2026                                                                                              |
