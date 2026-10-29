# OpenFTP / Free File Server

OpenFTP is a lightweight, user-oriented file server written in PHP. It provides account registration and login, per-user file uploads, file downloads, renaming, and deletion through a simple web interface.

## Features

- Registration and login with email and password authentication
- Argon2id password hashing with a unique random authentication salt
- Automatic upgrade from legacy MD5 password records after login
- Sessions that expire after 2 hours by default and refresh on activity
- Session binding to the originating IP address and browser user-agent
- HTTP-only session cookies with SameSite=Lax protection
- Secure, prepared database queries for users and files
- Per-user file isolation in the dashboard
- File upload limit of 5 MB
- Sanitized uploaded and renamed filenames
- Apache-friendly routes and a custom 404 page
- Guided database setup through a browser-based installer

## Requirements

- PHP 8.1 or newer
- PHP MySQLi extension
- MySQL 8 or MariaDB
- Apache 2 with `mod_rewrite` enabled
- A web server account with write access to the project and `files/` directory

## Quick Installation

1. Clone the project into the Apache document root:

   ```bash
git clone https://github.com/lopessx/free-file-server.git
cd free-file-server
   ```

2. Configure the database, session timeout, upload limit, and password settings in [`config.php`](config.php).

   At minimum, update:

   ```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'free_file_server');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_secure_password');
define('setup_project', true);
   ```

3. Ensure Apache has permissions to write the application files needed by setup and the uploaded-file directory:

   ```bash
sudo chown -R www-data:www-data /var/www/free-file-server
sudo chmod 755 /var/www/free-file-server
sudo chmod 775 /var/www/free-file-server/files
   ```

4. Enable Apache rewrite support:

   ```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
   ```

5. Open the application in a browser. The application redirects you to `setup.php` while setup mode is enabled.
6. Select **Test Database Connection**, then **Install**.
7. The installer creates the `users` and `files` tables and disables setup mode by changing `setup_project` to `false` in `config.php`.
8. Open `index.php` and register the first account, or use an account that already exists in the database.

For a complete server deployment, Apache virtual host, file permissions, database, and HTTPS procedure, see [`deploy.md`](deploy.md).

## Default Configuration

| Setting | Default |
| --- | --- |
| Database host | `localhost` |
| Database name | `free_file_server` |
| Character set | `utf8mb4` |
| Session timeout | 7200 seconds |
| Maximum upload size | 5 MB |
| Password algorithm | Argon2id |
| Authentication salt | 32 random bytes |
| Minimum password length | 12 characters |
| Maximum password length | 128 characters |

## Using the Application

- Registration: create an account on `register.php`.
- Login: sign in on `index.php`.
- Dashboard: upload files and view only the files associated with your account.
- Edit: rename a file from the dashboard's **Edit** action.
- Delete: remove the stored file and its database record.
- Download: select a filename from the dashboard.

## Project Structure

```text
config.php             Database, session, upload, and password settings
setup.php              Database and table installer
index.php              Login page
register.php           Account registration page
dashboard.php          Authenticated file dashboard
edit.php               File rename interface
404.php                Custom missing-page response
.htaccess              Apache rewrite rules
src/application.php    Application, session, auth, and file operations
src/password_auth.php  Password hashing helpers
src/router.php         Application route guard
src/error_messages.php Shared form error rendering
css/                   Stylesheets
files/                 Uploaded file storage
thumbnails/            Images and application branding
```

## Security Notes

- Set a strong database password and do not commit real credentials.
- Keep `setup_project` set to `false` after installation.
- Serve the application over HTTPS in production.
- Give Apache only the permissions necessary for uploading files.
- Restrict access to `config.php` and database backups at the web-server layer.
- Existing MD5 passwords remain supported only for one-time transparent migration after a successful login.

## Repository

- Source: <https://github.com/lopessx/free-file-server>
- Deployment guide: [`deploy.md`](deploy.md)
