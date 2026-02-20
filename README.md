# CSV Importer

**Overview**
- **Purpose:** Import a CSV file into a MySQL database using PDO and raw SQL.
- **Command:** `app:import-csv` (invoked via the console script).

**Prerequisites**
- **PHP:** PHP with `pdo_mysql` extension installed.
- **Composer:** project dependencies installed via `composer install`.

**Configuration**
- **Env file:** Copy or edit the project `.env` file ([.env](.env)) and set:
  - `DB_HOST` — database host (default: `127.0.0.1`)
  - `DB_PORT` — database port (default: `3306`)
  - `DB_NAME` — database name (example: `csv_demo`)
  - `DB_USER` — database user (example: `csv_user`)
  - `DB_PASS` — database password

**Database setup (example)**
Run these statements in MySQL (adjust names/passwords as needed):

```sql
CREATE DATABASE csv_demo;
CREATE USER 'csv_user'@'localhost' IDENTIFIED BY 'StrongPasswordHere';
GRANT ALL PRIVILEGES ON csv_demo.* TO 'csv_user'@'localhost';
FLUSH PRIVILEGES;

USE csv_demo;
CREATE TABLE customers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  csv_index INT NOT NULL,
  customer_id INT NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  company VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  country VARCHAR(100) DEFAULT NULL,
  phone1 VARCHAR(50) DEFAULT NULL,
  phone2 VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  subscription_date DATE DEFAULT NULL,
  website VARCHAR(255) DEFAULT NULL
);
```

**Usage**

Install dependencies:

```bash
composer install
```

Run the importer (example):

```bash
php bin/console.php app:import-csv customers.csv
```

Notes
- The console script loads `.env` (see [bin/console.php](bin/console.php)).
- If you see an "Access denied" error for user `''@'localhost'`, ensure `DB_USER` and `DB_PASS` are set in `.env` (or exported into the environment) before running the command.

If you want, I can also add a sample `mysql` client command to apply the SQL or validate the connection from the command line.
