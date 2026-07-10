# Deployment Guide

## 1. Server

- PHP 8.1+ (`pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `openssl`)
- MySQL 5.7+ / MariaDB 10.3+
- Apache + `mod_rewrite`, or Nginx

## 2. Files

Upload the project and point the web root at the project folder (the front
controller is `index.php`). Ensure `storage/` and `config/` are writable:

```bash
chmod -R 775 storage public/uploads
```

## 3. Install

Visit `/install/` and complete the wizard, **or** copy `config/config.sample.php`
to `config/config.php`, set credentials, and run:

```bash
php cli/migrate.php --seed
```

Then **delete `/install`** and set `config/config.php` → `app.env = production`,
`app.debug = false`.

## 4. Web server config

### Apache
The bundled `.htaccess` handles rewriting and security headers. Ensure
`AllowOverride All` is set for the directory.

### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
}
# Block sensitive paths
location ~* \.(sql|log|md)$ { deny all; }
location /storage/ { deny all; }
location /config/  { deny all; }
```

## 5. Cron

```cron
* * * * * php /var/www/hotelcrm/cli/worker.php >> /var/www/hotelcrm/storage/logs/worker.log 2>&1
```

## 6. Production Hardening Checklist

- [ ] `app.debug = false`, `app.env = production`
- [ ] HTTPS enforced; `session.secure = true` in config
- [ ] Strong, unique `app.key`
- [ ] `/install` directory removed
- [ ] DB user limited to the app database only
- [ ] `storage/`, `config/`, `database/` not web-accessible
- [ ] Regular backups (`Settings → Create Database Backup`, or `BackupService`)
- [ ] Rotate `storage/logs/*`
- [ ] Configure SMTP, WhatsApp, payment and OTA credentials in **Settings**
- [ ] Review roles/permissions for each staff account

## 7. Backups & Restore

- Create: **Settings → Create Database Backup** (writes to `storage/backups/`).
- Restore: `App\Services\BackupService::restore('/path/to/backup.sql')` or import via mysql CLI.

## 8. Scaling Notes

- Enable a persistent object cache by swapping the file cache in `RateLimiter`.
- Run the queue worker on a dedicated process/host for high OTA volume.
- Add MySQL indexes are already defined on hot columns (dates, status, phone/email).
