# Violin Rental Agency  
**CPSC 3660 — Winter 2026**  
**Team:** Nawal Mohamuud, Aaron Amoso, Chidumebi Obioha  

---

## How to Run

1. **Import `schema.sql`** into phpMyAdmin on vcandle:
    - Select the `twog3669` database.
    - Go to the **Import** tab and upload the `schema.sql` file.
2. **Visit `setup_password.php`** once in the browser to set passwords, then **delete it**.
3. **Go to `login.php`** to start using the system.

**URL:**  
[http://vcandle.cs.uleth.ca/~twog3669/ViolinRental-db/login.php](http://vcandle.cs.uleth.ca/~twog3669/ViolinRental-db-main/login.php)

---

## Login Credentials

| **Username** | **Password**   | **Role**   |
|--------------|----------------|------------|
| admin        | admin123       | Admin      |
| alice        | password123    | Customer   |
| bob          | password123    | Customer   |
| carol        | password123    | Customer   |
| david        | password123    | Customer   |
| eva          | password123    | Customer   |

---

## Pages

| **File**                | **What it does**                                              |
|-------------------------|---------------------------------------------------------------|
| `login.php`             | Login for all users                                           |
| `admin_dashboard.php`   | Admin home with stats                                         |
| `query.php`             | Search and view customers                                     |
| `insert.php`            | Add a customer                                                |
| `update.php`            | Edit a customer                                               |
| `delete.php`            | Delete a customer                                             |
| `products.php`          | Add, edit, delete products                                    |
| `rentals.php`           | View, edit, delete rentals                                    |
| `rental_insert.php`     | Create a new rental with payment                              |
| `queries.php`           | 17 advanced SQL queries                                       |
| `customer_dashboard.php`| Customer profile and rental history                           |

---

## Database

### Tables:
- `CUSTOMER`
- `USERS`
- `PRODUCT`
- `MANUFACTURER_DISTRIBUTOR`
- `RENTAL`
- `RENTAL_ITEM`
- `RECEIPT`
- `PAYMENT`
- `MAINTENANCE_LOG`

### Views:
- `vw_active_rentals`
- `vw_revenue_by_customer`
- `vw_product_rental_count`

### Computed Column:
- `RENTAL.total_days` — auto-calculated from rental dates

### Weak Entity:
- `MAINTENANCE_LOG` depends on `PRODUCT`

---

## Queries (`queries.php`)

| **#** | **Topic**                         | **Keywords used**                                   |
|-------|-----------------------------------|-----------------------------------------------------|
| 1     | Rentals per customer             | `GROUP BY`, `COUNT`                                 |
| 2     | Customers with 2+ rentals        | `HAVING`                                            |
| 3     | Price stats                      | `AVG`, `MIN`, `MAX`                                 |
| 4     | Revenue by payment method        | `GROUP BY`, `SUM`                                   |
| 5     | Rentals in date range            | `BETWEEN`                                           |
| 6     | Manufacturers missing email      | `IS NULL`                                           |
| 7     | Cities matching pattern          | `LIKE`                                              |
| 8     | Never paid cash                  | `NOT IN`                                            |
| 9     | Rented full-size violin          | `IN`, subquery                                      |
| 10    | Above average price              | `ALL`                                               |
| 11    | Never rented products            | `NOT EXISTS`                                        |
| 12    | All names combined               | `UNION`                                             |
| 13    | Active rentals today             | `JOIN`, `CURDATE`                                   |
| 14    | Customers who rented AND paid    | `INTERSECT`                                         |
| 15    | Renters who never paid cash      | `EXCEPT`                                            |
| 16    | Rental length category           | `CASE` expression                                   |
| 17    | Create audit log table           | DDL — `CREATE TABLE`                                |

---

## Testing Notes

1. **New rental (`rental_insert.php`)** automatically creates a `RECEIPT` and `PAYMENT` record.
2. **Customers can only see their own data** — accessing admin pages redirects to `unauthorized.php`.