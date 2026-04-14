# File Connection Map — Violin Rental Database

## ✅ Core Connection Files

### db.php
**Purpose:** Database connection configuration  
**Requires:** None  
**Used by:** ALL other PHP files  
**Status:** ✅ Connected

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'user');
define('DB_PASS', 'password');
define('DB_NAME', 'violin_rental');
```

### session.php
**Purpose:** Session management and authentication helpers  
**Requires:** None  
**Used by:** login.php, logout.php, admin_dashboard.php, customer_dashboard.php, insert.php, query.php, update.php, delete.php, unauthorized.php  
**Status:** ✅ Connected

## ✅ Authentication Flow

### login.php
- **Requires:** session.php, db.php
- **Redirects to:**
  - admin_dashboard.php (if role='admin')
  - customer_dashboard.php (if role='customer')
- **Status:** ✅ Connected

### logout.php
- **Requires:** session.php
- **Redirects to:** login.php
- **Status:** ✅ Connected

### unauthorized.php
- **Requires:** session.php
- **Purpose:** Display 403 error
- **Status:** ✅ Connected

## ✅ Admin Dashboard

### admin_dashboard.php
- **Requires:** session.php, db.php
- **Authentication:** `require_login('admin')`
- **Navigation Links:**
  - query.php (View Customers) ✅
  - insert.php (Add Customer) ✅
  - admin_dashboard.php (Dashboard) ✅
- **Status:** ✅ Connected

## ✅ Customer Dashboard

### customer_dashboard.php
- **Requires:** session.php, db.php
- **Authentication:** `require_login('customer')`
- **Status:** ✅ Connected

## ✅ CRUD Operations (Admin Only)

### query.php (READ)
- **Requires:** session.php ✅, db.php ✅
- **Authentication:** `require_login('admin')` ✅
- **Navigation:**
  - insert.php (Add Customer) ✅
  - update.php (Edit) ✅
  - delete.php (Delete) ✅
  - admin_dashboard.php (Back) ✅
- **Status:** ✅ FIXED & Connected

### insert.php (CREATE)
- **Requires:** session.php ✅, db.php ✅
- **Authentication:** `require_login('admin')` ✅
- **Navigation:**
  - query.php (View) ✅
  - admin_dashboard.php (Back) ✅
- **Status:** ✅ FIXED & Connected

### update.php (UPDATE)
- **Requires:** session.php ✅, db.php ✅
- **Authentication:** `require_login('admin')` ✅
- **SQL Injection Fix:** Using prepared statement ✅
- **Navigation:**
  - query.php (View) ✅
  - admin_dashboard.php (Back) ✅
- **Status:** ✅ FIXED & Connected

### delete.php (DELETE)
- **Requires:** session.php ✅, db.php ✅
- **Authentication:** `require_login('admin')` ✅
- **SQL Injection Fix:** Using prepared statement ✅
- **Navigation:**
  - query.php (View) ✅
  - admin_dashboard.php (Back) ✅
- **Status:** ✅ FIXED & Connected

## ✅ Setup Script

### setup_password.php
- **Requires:** db.php
- **Purpose:** Initialize user password hashes (run ONCE after schema.sql import)
- **Status:** ✅ Connected
- **⚠️ WARNING:** Delete or restrict access after setup

## ✅ Database Schema

### schema.sql
- **Creates:** violin_rental database with 10 tables
- **Sample Data:** Included for testing
- **Tables:**
  - MANUFACTURER_DISTRIBUTOR
  - PRODUCT
  - MAINTENANCE_LOG (weak entity)
  - CUSTOMER
  - RECEIPT
  - PAYMENT
  - RENTAL
  - RENTAL_ITEM (associative/N-to-M)
  - USERS
  - (MAINTENANCE_LOG already listed)
- **Status:** ✅ Ready

## 📋 Setup Instructions

1. **Import Database Schema:**
   ```bash
   mysql -u user -p < schema.sql
   ```

2. **Initialize Password Hashes:**
   - Visit: `http://localhost/violin-rental/setup_password.php`
   - Delete or restrict access to setup_password.php after running

3. **Login:**
   - URL: `http://localhost/violin-rental/login.php`
   - Admin: username=`admin`, password=`admin123`
   - Customer: username=`alice`, password=`password123`

4. **Navigate:**
   - Admin: `admin_dashboard.php`
   - Customer: `customer_dashboard.php`

## 🐛 Issues Fixed

- ✅ Added `require_once 'session.php'` to all CRUD pages
- ✅ Added `require_login('admin')` to all CRUD pages
- ✅ Fixed SQL injection in update.php (prepared statement)
- ✅ Fixed SQL injection in delete.php (prepared statement)
- ✅ Changed `include` to `require_once` for safety
- ✅ Added back-navigation links to admin_dashboard

## 📝 Summary

**Total Files:** 15 PHP files  
**Connected Files:** 15/15 ✅  
**Security Issues Fixed:** 2 (SQL injection)  
**Access Control Added:** 4 files  
**Navigation Links Added:** 4 files
