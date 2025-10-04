# MyTacho

MyTacho is a PHP + MariaDB + AdminLTE web application for managing tachograph data and DDD files, with a custom Go parser (`dddparser`) for DDD data.

---

## Table of Contents

- Features
- Tech Stack
- Project Structure
- Setup / Build
- Development Notes
- User Management
- Language Support
- Troubleshooting

---

## Features

- User authentication with roles (`admin` / `user`)
- User profile page with language selection
- AdminLTE v3 dashboard
- Upload and parse DDD files using a Go parser
- Multi-language support
- Integrated phpMyAdmin for database management

---

## Tech Stack

- PHP 8.2 + Apache
- MariaDB 11
- AdminLTE 3.2
- Go 1.21 (for `dddparser`)
- Python 3 (for PKS data scripts)
- Node.js / npm (used for AdminLTE plugins if needed)

---

## Project Structure

```
/src/               # PHP web app
/inc/               # Includes (header, footer, db, lang, etc.)
/lang/              # Language files (*.php)
/uploads/           # Uploaded DDD files
/docker/            # Dockerfiles / scripts
/dddparser/         # Go parser binary
/entrypoint.sh      # Container entrypoint
```

---

## Setup / Build

### 1. Build Docker image

```
docker build -t mytachodata .
```

### 2. Run container

```
docker run -p 8085:80 -p 3306:3306 mytachodata
```

- Apache served at `http://localhost:8085`
- MariaDB accessible externally on port `3306`

**Optional volumes for persistence** (dev only):

```
docker run -p 8085:80 -p 3306:3306 \
  -v ~/mytachodata/mysql:/var/lib/mysql \
  -v ~/mytachodata/html:/var/www/html \
  mytachodata
```

> For production, rebuilding the image on web changes is recommended.

---

## Development Notes

- **Sessions**: `session_start()` is handled in `header.php`.
- **Database connection**: Use PDO via `db.php`.
- **Language files**: Located in `/lang/*.php`, auto-detected.
- **User roles**:
  - `admin`: Can edit username & password
  - `user`: Username is read-only

- **Docker Entrypoint** (`entrypoint.sh`) handles:
  - MariaDB initialization & users creation
  - Admin user creation with hashed password
  - Starts Apache in foreground

- **PhpMyAdmin** connects via TCP (`127.0.0.1:3306`) for external access.

- **DDD parser**:
  - Built in Go during Docker build
  - Accessible via `/usr/local/bin/dddparser`
  - Python scripts download PKS data before building

---

## User Management

- Default admin credentials:
  ```
  Username: admin
  Password: admin
  Role: admin
  ```
- Users table structure:
  - `id` INT PRIMARY KEY AUTO_INCREMENT
  - `username` VARCHAR(50) UNIQUE
  - `password` VARCHAR(255) (hashed)
  - `role` VARCHAR(20) (`admin` or `user`)
  - `language` VARCHAR(5) (default: `en`)
  - `created_at` TIMESTAMP

---

## Language Support

- Language files stored in `/lang/*.php`
- Users can select language in `user.php`
- New language: simply create a new PHP file in `/lang/`

---

## Troubleshooting

- **Cannot connect to MariaDB externally**
  - Ensure entrypoint binds MariaDB to `0.0.0.0`
  - Use `mysql -h 127.0.0.1 -P 3306 -u mytacho_user -p`

- **AdminLTE CSS/JS not loading**
  - Check that `/adminlte` folder exists and is accessible
  - Ensure paths in `header.php` and `footer.php` are correct

- **PHP session warnings**
  - `session_start()` must not be called twice; handled in `header.php`.

---

## Notes for Next Session

- Can provide context about current Docker setup, PHP web app structure, and database initialization.
- Multi-language, user roles, and AdminLTE integration are already implemented.
- `user.php`, `login.php`, and `entrypoint.sh` are the main points for session and DB logic.
- Go parser (`dddparser`) is prebuilt and ready to use.

---

