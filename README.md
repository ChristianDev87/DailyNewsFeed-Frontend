# DailyNewsFeed — Frontend

PHP 8.2+ Web-Interface für DailyNewsFeed. Discord OAuth2 Login, Dashboard zur Server-Verwaltung, Kanal- und Feed-Konfiguration.

## Voraussetzungen

- PHP 8.2 oder höher (empfohlen: 8.4)
- PHP-FPM (`php8.4-fpm`)
- Apache 2.4 mit `mod_proxy_fcgi` und `mod_rewrite`
- MySQL 8 / MariaDB 10.6+
- Eine Discord-Applikation mit OAuth2-Einrichtung

## Installation

### 1. Repository klonen

```bash
git clone https://github.com/ChristianDev87/DailyNewsFeed-Frontend.git /var/www/daily-news
cd /var/www/daily-news
```

`vendor/` ist bereits im Repository enthalten — kein `composer install` nötig.

### 2. Datenbank einrichten

Schema liegt im Hauptprojekt unter `database/schema.sql`. Tabellen anlegen:

```bash
mysql -u root -p daily_news < schema.sql
```

### 3. Umgebungsvariablen konfigurieren

```bash
cp .env.example .env
nano .env
```

| Variable | Beschreibung |
|---|---|
| `DISCORD_CLIENT_ID` | Client-ID der Discord-Applikation |
| `DISCORD_CLIENT_SECRET` | Client-Secret der Discord-Applikation |
| `DISCORD_REDIRECT_URI` | OAuth2-Callback-URL (z. B. `https://deine-domain.de/auth/callback`) |
| `DISCORD_BOT_TOKEN` | Bot-Token für Guild-Member-Abfragen |
| `DB_HOST` | Datenbank-Host (Standard: `localhost`) |
| `DB_PORT` | Datenbank-Port (Standard: `3306`) |
| `DB_NAME` | Datenbankname |
| `DB_USER` | Datenbankbenutzer |
| `DB_PASS` | Datenbankpasswort |
| `TOKEN_ENCRYPTION_KEY` | 32-Byte-Schlüssel als Base64 — **muss identisch mit dem Bot sein** |
| `SUPERADMIN_IDS` | Kommagetrennte Discord-User-IDs mit Admin-Zugriff |
| `APP_DEBUG` | `true` für Entwicklung, `false` für Produktion |

Schlüssel generieren:
```bash
openssl rand -base64 32
```

### 4. Discord-Applikation einrichten

1. [discord.com/developers/applications](https://discord.com/developers/applications) → Neue Applikation anlegen
2. **OAuth2 → Redirects**: Callback-URL eintragen (identisch mit `DISCORD_REDIRECT_URI`)
3. **Bot**: Bot anlegen, Token kopieren → `DISCORD_BOT_TOKEN`
4. **Bot → Privileged Gateway Intents**: `Server Members Intent` aktivieren

### 5. Apache2 konfigurieren

PHP-FPM installieren und starten:

```bash
apt install php8.4-fpm -y
systemctl enable --now php8.4-fpm
```

Apache-Module aktivieren:

```bash
a2enmod proxy_fcgi rewrite ssl
```

VirtualHost anlegen (Beispiel unter `/etc/apache2/sites-available/daily-news.conf`):

```apache
<VirtualHost *:80>
    ServerName deine-domain.de
    Redirect permanent / https://deine-domain.de/
</VirtualHost>

<VirtualHost *:443>
    ServerName deine-domain.de
    DocumentRoot /var/www/daily-news/public

    <Directory /var/www/daily-news/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php8.4-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    SSLEngine on
    SSLCertificateFile     /etc/letsencrypt/live/deine-domain.de/fullchain.pem
    SSLCertificateKeyFile  /etc/letsencrypt/live/deine-domain.de/privkey.pem

    ErrorLog  ${APACHE_LOG_DIR}/daily-news-error.log
    CustomLog ${APACHE_LOG_DIR}/daily-news-access.log combined
</VirtualHost>
```

Site aktivieren und Apache neu laden:

```bash
a2ensite daily-news.conf
systemctl reload apache2
```

### 6. HTTPS mit Let's Encrypt

```bash
apt install certbot python3-certbot-apache -y
certbot --apache -d deine-domain.de
```

> **Hinweis:** Der PHP-FPM Socket-Pfad variiert je nach PHP-Version. Bei PHP 8.2 lautet er `/var/run/php/php8.2-fpm.sock`, bei PHP 8.4 entsprechend `/var/run/php/php8.4-fpm.sock`.

## Verzeichnisstruktur

```
/var/www/daily-news/
├── public/              ← Document Root (einziges öffentlich erreichbares Verzeichnis)
│   ├── index.php        ← Einstiegspunkt (Slim 4 Front Controller)
│   ├── .htaccess        ← URL-Rewriting für Apache
│   └── assets/          ← CSS, JS
├── src/                 ← PHP-Klassen (Actions, Middleware, Services)
├── templates/           ← PHP-Templates
├── vendor/              ← Composer-Abhängigkeiten (bereits eingecheckt)
├── .env.example         ← Vorlage für Umgebungsvariablen
├── apache2.conf         ← Apache2 VirtualHost Referenz-Konfiguration
└── nginx.conf           ← Nginx VirtualHost Referenz-Konfiguration
```

## Technologie-Stack

- **Framework:** Slim 4
- **Dependency Injection:** PHP-DI 7
- **Templating:** PHP-Templates (kein Twig)
- **Auth:** Discord OAuth2, Sliding Sessions (DB-backed)
- **CSRF:** HMAC-SHA256 (stateless, abgeleitet aus Session-Token)
