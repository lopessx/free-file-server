# Deployment Guide

This guide describes deployment on Ubuntu/Debian with Apache, PHP, and MariaDB. Adapt the commands if your server uses another Linux distribution or hosting control panel.

## 1. Install server packages

Install Apache, PHP, the PHP MySQL extension, MariaDB, Git, and Certbot if HTTPS is not provided by your hosting platform:

```bash
sudo apt update
sudo apt install apache2 mariadb-server php php-mysqli php-mbstring git unzip certbot python3-certbot-apache
sudo systemctl enable --now apache2 mariadb
sudo a2enmod rewrite
sudo systemctl reload apache2
```

Confirm that PHP reports MySQL support:

```bash
php -m | grep mysqli
```

## 2. Create a database and account

Open the MariaDB shell:

```bash
sudo mariadb
```

Create a dedicated database user and replace the example password:

```sql
CREATE DATABASE free_file_server CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'free_file_server'@'localhost' IDENTIFIED BY 'replace-with-a-strong-password';
GRANT ALL PRIVILEGES ON free_file_server.* TO 'free_file_server'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

The application setup page can also create the database and tables, but a dedicated pre-created database and user is the recommended production setup.

## 3. Deploy the application files

Clone the project into the desired web directory. For a standard Apache deployment:

```bash
sudo mkdir -p /var/www/free-file-server
sudo git clone https://github.com/lopessx/free-file-server.git /var/www/free-file-server
```

Set ownership to the Apache/PHP process user and keep source directories writable only when the setup installer needs to update `config.php`:

```bash
sudo chown -R www-data:www-data /var/www/free-file-server
sudo find /var/www/free-file-server -type d -exec chmod 755 {} \;
sudo find /var/www/free-file-server -type f -exec chmod 644 {} \;
sudo chmod 775 /var/www/free-file-server/files
sudo chmod 664 /var/www/free-file-server/config.php
```

After installation, reduce write access to the application root and `config.php` if you manage future code deployments separately. `files/` must remain writable by Apache.

## 4. Configure application settings

Edit `config.php` and replace the database values:

```bash
sudo nano /var/www/free-file-server/config.php
```

Update these settings at minimum:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'free_file_server');
define('DB_USER', 'free_file_server');
define('DB_PASS', 'replace-with-a-strong-password');
define('DB_CHARSET', 'utf8mb4');
define('setup_project', true);
```

Review the session and upload configuration before deployment:

```php
define('session_timeout', 7200);
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
```

The password defaults are Argon2id with a 64 MiB memory cost, time cost 4, three threads, and a minimum password length of 12 characters. These values should remain conservative for the server's available memory and CPU.

## 5. Configure Apache

Create a virtual host. Replace `files.example.com` with the production domain:

```bash
sudo nano /etc/apache2/sites-available/free-file-server.conf
```

Use the following configuration initially:

```apache
<VirtualHost *:80>
    ServerName files.example.com
    DocumentRoot /var/www/free-file-server

    <Directory /var/www/free-file-server>
        AllowOverride All
        Options -Indexes +FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/free-file-server-error.log
    CustomLog ${APACHE_LOG_DIR}/free-file-server-access.log combined
</VirtualHost>
```

Enable the site and reload Apache:

```bash
sudo a2ensite free-file-server.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

The `.htaccess` file redirects requests for unknown paths to `404.php` and supports the application's route guard. Apache must allow overrides with `AllowOverride All`.

## 6. Run the installer

1. Visit `http://files.example.com/setup.php`, or the server IP path if DNS is not ready yet.
2. Select **Test Database Connection**.
3. If the test succeeds, select **Install**.
4. Confirm that the installer creates the `users` and `files` tables.
5. Confirm that it reports setup mode has been disabled.
6. Verify the following line in `config.php`:

   ```php
   define('setup_project', false);
   ```

The installer also updates existing tables by adding missing timestamps and login-tracking columns when needed.

## 7. Enable HTTPS

Configure DNS for the domain before requesting a certificate, then run:

```bash
sudo certbot --apache -d files.example.com
```

Verify automatic renewal:

```bash
sudo systemctl status certbot.timer
sudo certbot renew --dry-run
```

On HTTPS deployments, the application automatically marks the session cookie as secure while accepting normal HTTP during initial setup.

## 8. Verify the deployment

Test these workflows:

1. Register a new account.
2. Log out and log back in.
3. Upload a small test file under 5 MB.
4. Verify that the file appears in the dashboard.
5. Rename the file and confirm the name changes.
6. Download the file.
7. Delete it and confirm that it is removed from storage and the database.
8. Open an invalid URL and confirm that the custom 404 response is used.
9. Log in as a second user and confirm that the first user's files are not visible or manageable.

Review PHP and Apache logs if a step fails:

```bash
sudo tail -n 100 /var/log/apache2/free-file-server-error.log
sudo journalctl -u apache2 -n 100 --no-pager
```

## 9. Production hardening checklist

- Use HTTPS and redirect port 80 to port 443.
- Replace placeholder database credentials with a unique strong password.
- Restrict the database user to the application database from localhost.
- Keep `setup_project` set to `false` after installation.
- Keep `.git/`, configuration backups, and database dumps outside `DocumentRoot` when possible.
- Do not expose uploaded files to public execution; serving images or scripts may be hazardous depending on Apache MIME configuration. Add the following to the Apache `<Directory>` block if direct execution must be prevented:

  ```apache
  <FilesMatch "\.(php|phtml|phar)$">
      Require all denied
  </FilesMatch>
  ```

  Apply this to `/var/www/free-file-server/files` with an additional `<Directory>` block rather than only the application root.

- Back up the database and `files/` directory regularly, including corresponding timestamps.
- Monitor disk usage and configure the upload limit in both PHP and Apache/PHP settings if `MAX_FILE_SIZE` is increased.

For PHP upload compatibility, the following `php.ini` values may need to be at least as large as the application limit:

```ini
upload_max_filesize = 6M
post_max_size = 8M
```

Restart Apache after changing `php.ini`:

```bash
sudo systemctl reload apache2
```

## 10. Updating an existing deployment

Before updating, back up the database and uploaded files:

```bash
sudo mariadb-dump free_file_server > /var/backups/free_file_server.sql
sudo cp -a /var/www/free-file-server/files /var/backups/free-file-server-files
```

Deploy the update while preserving `config.php` and `files/`:

```bash
cd /var/www/free-file-server
sudo -u www-data git pull --ff-only origin main
```

Run PHP syntax checks and reload Apache:

```bash
find /var/www/free-file-server -name '*.php' -not -path '*/vendor/*' -print0 | xargs -0 -n1 php -l
sudo systemctl reload apache2
```

If a database schema was changed in the new release, open `setup.php` after setting `setup_project` back to `true` only for the duration of the migration. Set it to `false` again immediately after the upgrade.
