# Violin Rental Agency - Database Application
CPSC 3660 Group Project  
**Team:** Nawal Mohamuud, Aaron Amoso, Chidumebi Obioha

## Setup Instructions
Import the database schema
bashmysql -u twog3669 -p twog3669 < schema.sql


## Overview
A web-based database application for managing violin rental operations, including inventory management, customer records, rental tracking, and payment processing.

## Tech Stack
- **PHP:** 8.3
- **Database:** MySQL 8.0
- **Server:** Apache2 (WSL/Ubuntu or local)
- **Frontend:** HTML/CSS (styled with Cormorant Garamond & DM Mono fonts)

## Project Structure

### Core Files
- `schema.sql` — Database schema with 10 tables and sample data
- `db.php` — Database connection configuration
- `session.php` — Session management and authentication helpers

### Authentication
- `login.php` — Admin and customer login portal
- `logout.php` — Logout handler
- `unauthorized.php` — 403 unauthorized error page

### Dashboards
- `admin_dashboard.php` — Admin panel (stats, recent rentals, navigation)
- `customer_dashboard.php` — Customer profile and rental history

### Admin CRUD Operations (Customer Management)
- `query.php` — Search and view all customers
- `insert.php` — Add a new customer
- `update.php` — Edit customer information
- `delete.php` — Remove a customer

### Setup
- `setup_password.php` — Initialize password hashes (run ONCE, then delete/restrict)

## Database Schema

Tables: MANUFACTURER_DISTRIBUTOR, PRODUCT, MAINTENANCE_LOG, CUSTOMER, RECEIPT, PAYMENT, RENTAL, RENTAL_ITEM, USERS

**See `CONNECTION_MAP.md` for detailed entity relationships.**

## Quick Start

### 1. Import Database Schema
```bash
mysql -u user -p < schema.sql
```

### 2. Configure Database Connection
Edit `db.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'user');
define('DB_PASS', 'password');
define('DB_NAME', 'violin_rental');
```

### 3. Start Services (WSL/Ubuntu)
```bash
sudo service apache2 start
sudo service mysql start
```

### 4. Initialize Password Hashes
1. Visit: `http://localhost/violin-rental/setup_password.php`
2. After setup, delete or restrict access to `setup_password.php`

### 5. Login
- **URL:** `http://localhost/violin-rental/login.php`
- **Admin Account:**
  - Username: `admin`
  - Password: `admin123`
- **Sample Customer Accounts:**
  - alice / password123
  - bob / password123
  - carol / password123
  - david / password123
  - eva / password123

## File Connections

All files are properly connected with:
- ✅ Consistent `require_once` includes
- ✅ Role-based access control (admin/customer)
- ✅ Prepared statements for SQL injection prevention
- ✅ Session management across all pages
- ✅ Navigation links between pages

**See `CONNECTION_MAP.md` for full connection details.**

## Security Features

- Password hashing with bcrypt (PASSWORD_BCRYPT)
- Prepared statements to prevent SQL injection
- Session regeneration on login
- Role-based page access control
- XSS prevention with htmlspecialchars()

## Known Limitations

Currently implemented:
- Customer management (CRUD)
- Basic admin dashboard

Not yet implemented:
- Product/inventory management pages
- Rental management interface
- Payment processing UI
- Advanced reporting features
- Customer-facing rental interface
