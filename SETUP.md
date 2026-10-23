# Setup Guide

This guide takes you from the downloaded kit to a running shop, one step at a time. You do not need to be a programmer. Copy each command exactly as shown, paste it, and press **Enter**.

Need every setting or a special server? See [docs/SETUP_ADVANCED.md](docs/SETUP_ADVANCED.md).

## Contents

1. [What you need](#1-what-you-need)
2. [Install the tools on your computer](#2-install-the-tools-on-your-computer) ([Windows](#windows), [Mac](#mac), [Linux](#linux-ubuntu-or-debian))
3. [Run the shop on your computer](#3-run-the-shop-on-your-computer)
4. [Put the shop online](#4-put-the-shop-online) ([cPanel](#option-a-cpanel-shared-hosting), [VPS](#option-b-vps-ubuntu-server))
5. [Common problems](#5-common-problems)

## 1. What you need

| Tool | Version | What it does |
|---|---|---|
| PHP | 8.4 | Runs the shop |
| Composer | 2 | Downloads the PHP parts of the shop |
| Node.js | 22 LTS | Builds the design files (CSS and JavaScript) |
| MySQL | 8 or newer (or MariaDB 10.6+) | Stores products, orders and customers |

PHP also needs these extensions: `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `gd`, `zip`, `exif`, `dom` and `curl`. The steps below install them for you.

**How to open a terminal** (the window where you type commands):

- **Windows:** open the kit folder in File Explorer, right click an empty space and pick **Open in Terminal**.
- **Mac:** open **Terminal** from Applications → Utilities. Type `cd ` (with a space), drag the kit folder into the window and press Enter.
- **Linux:** open the kit folder, right click an empty space and pick **Open in Terminal**.

## 2. Install the tools on your computer

Follow only the section for your system. You do this once.

### Windows

1. **PHP, Composer and Node.js:** download and install **Laravel Herd** from [herd.laravel.com](https://herd.laravel.com). Open Herd, go to **PHP** and make sure **8.4** is selected. Herd installs Composer and Node.js too.
2. **MySQL:** download **MySQL Community Server** (Windows MSI installer) from [dev.mysql.com/downloads/mysql](https://dev.mysql.com/downloads/mysql/). During setup, pick a **root password** and write it down.
3. **Check it worked.** Open a new terminal and run:

   ```bash
   php -v
   composer -V
   node -v
   ```

   Each command should print a version number. PHP must show `8.4`.

### Mac

1. **Homebrew** (an installer for developer tools). Open Terminal and run:

   ```bash
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   ```

   When it finishes, run the two `echo` and `eval` lines it prints under **Next steps**.

2. **PHP, Composer, Node.js and MySQL:**

   ```bash
   brew install php@8.4 composer node@22 mysql
   brew link --force --overwrite php@8.4 node@22
   brew services start mysql
   ```

3. **Set a MySQL root password** (pick your own and write it down):

   ```bash
   mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY 'your-password';"
   ```

4. **Check it worked:** close Terminal, open it again and run `php -v`, `composer -V` and `node -v`. PHP must show `8.4`.

Prefer an app with buttons? Install **Laravel Herd** for Mac from [herd.laravel.com](https://herd.laravel.com) and **DBngin** from [dbngin.com](https://dbngin.com) for MySQL instead.

### Linux (Ubuntu or Debian)

1. **PHP 8.4 and its extensions:**

   ```bash
   sudo apt update
   sudo apt install -y software-properties-common curl unzip git
   sudo add-apt-repository -y ppa:ondrej/php
   sudo apt update
   sudo apt install -y php8.4-cli php8.4-mysql php8.4-mbstring php8.4-intl php8.4-gd php8.4-zip php8.4-xml php8.4-curl
   ```

   On Debian, use the [packages.sury.org](https://packages.sury.org/php/) repository instead of the `ppa` line.

2. **Composer:**

   ```bash
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

3. **Node.js 22:**

   ```bash
   curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
   sudo apt install -y nodejs
   ```

4. **MySQL:**

   ```bash
   sudo apt install -y mysql-server
   sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'your-password';"
   ```

   Pick your own password and write it down.

5. **Check it worked:** run `php -v`, `composer -V` and `node -v`. PHP must show `8.4`.

## 3. Run the shop on your computer

Open a terminal **inside the kit folder** (see [How to open a terminal](#1-what-you-need)) and follow these steps in order.

### Step 1: Create the database

```bash
mysql -u root -p -e "CREATE DATABASE amar_ecom_lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Type your MySQL root password when asked. Nothing shows while you type, that is normal.

**Windows:** if `mysql` is "not recognized", open **MySQL Command Line Client** from the Start menu instead, type your root password, then type this and press Enter:

```sql
CREATE DATABASE amar_ecom_lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Download the shop's parts

```bash
composer install
npm install
```

This can take a few minutes the first time.

### Step 3: Create your settings file

- **Windows:** `copy .env.example .env`
- **Mac and Linux:** `cp .env.example .env`

Then run:

```bash
php artisan key:generate
```

### Step 4: Fill in your settings

Open the `.env` file in any text editor (Notepad, TextEdit or VS Code) and change these lines:

```dotenv
APP_NAME="My Shop"

DB_DATABASE=amar_ecom_lite
DB_USERNAME=root
DB_PASSWORD=your-password

APP_CURRENCY=BDT
APP_CURRENCY_SYMBOL=৳
```

Use your MySQL root password for `DB_PASSWORD`, and your own currency code and symbol. Save the file.

### Step 5: Fill the shop with sample data

```bash
php artisan migrate --seed
php artisan storage:link
```

This creates the tables, sample products and sample accounts.

### Step 6: Start the shop

```bash
composer dev
```

Open [http://localhost:8200](http://localhost:8200) in your browser. Keep the terminal open while you use the shop, and press `Ctrl + C` in it to stop.

**Sample accounts** (password `123456` for all):

| Who | Email | Login page |
|---|---|---|
| Admin | `admin@example.com` | [localhost:8200/backoffice/login](http://localhost:8200/backoffice/login) |
| Moderator | `moderator@example.com` | [localhost:8200/backoffice/login](http://localhost:8200/backoffice/login) |
| Customer | `customer@example.com` | [localhost:8200/user/login](http://localhost:8200/user/login) |

Next time, you only need Step 6.

## 4. Put the shop online

Before you start, you need a **domain** (for example `myshop.com`) and **hosting** with PHP 8.4 and MySQL.

**Build the files on your computer first.** In the kit folder, run:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### Option A: cPanel (shared hosting)

Your host must offer PHP 8.4 and **Terminal** in cPanel.

1. **Set PHP 8.4:** cPanel → **MultiPHP Manager** → pick your domain → **PHP 8.4**.
2. **Create the database:** cPanel → **MySQL Databases**. Create a database and a user, then add the user to the database with **All Privileges**. Write down all three names and the password.
3. **Upload the kit:** zip the kit folder (leave out `node_modules`), upload it in cPanel → **File Manager** to your home folder (not inside `public_html`) and extract it. Name the folder `amar-ecommerce-lite`.
4. **Point the domain to the shop:** cPanel → **Domains** → your domain → set **Document Root** to `amar-ecommerce-lite/public`.
5. **Create the settings file:** in File Manager, click **Settings** and tick **Show Hidden Files**. Open the shop folder, copy `.env.example` to `.env` and edit it:

   ```dotenv
   APP_NAME="My Shop"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://myshop.com
   APP_IS_IN_PRODUCTION=true
   DEV_CAN_MIGRATE_FRESH=false
   SESSION_SECURE_COOKIE=true

   DB_DATABASE=your_database
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   DB_SEEDING_ENABLED=false
   DB_SEED_ADMIN_EMAIL=you@myshop.com
   DB_SEED_ADMIN_PASSWORD=a-strong-password

   MEDIA_DISK=assets
   ```

6. **Finish the setup:** open cPanel → **Terminal** and run:

   ```bash
   cd ~/amar-ecommerce-lite
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan optimize
   chmod -R 775 storage bootstrap/cache
   ```

7. **Sign in** at `https://myshop.com/backoffice/login` with the email and password from step 5. Change your password, then delete the `DB_SEED_ADMIN_PASSWORD` value in `.env`.

### Option B: VPS (Ubuntu server)

1. Install the tools as in [Linux](#linux-ubuntu-or-debian), plus a web server: `sudo apt install -y nginx php8.4-fpm`.
2. Upload the built kit to `/var/www/amar-ecommerce-lite`.
3. Create the database (as in [Step 1](#step-1-create-the-database)) and the `.env` file (as in cPanel step 5, without the `MEDIA_DISK` line).
4. Run:

   ```bash
   cd /var/www/amar-ecommerce-lite
   php artisan key:generate
   php artisan migrate --force --seed
   php artisan storage:link
   php artisan optimize
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

5. Set up Nginx and HTTPS with the ready-made config in [docs/SETUP_ADVANCED.md](docs/SETUP_ADVANCED.md#4-nginx).

> **Never run `php artisan migrate:fresh` or `php artisan app:fresh` on a live shop.** They delete every order, customer and product.

## 5. Common problems

| Problem | Fix |
|---|---|
| `php` or `composer` is "not recognized" or "not found" | Close the terminal and open a new one. If it still fails, install the tools again (step 2) |
| PHP shows a version other than 8.4 | Windows: pick 8.4 in Herd. Mac: run the `brew link` line again. Linux: `sudo update-alternatives --set php /usr/bin/php8.4` |
| "Access denied for user" | The `DB_USERNAME` or `DB_PASSWORD` in `.env` is wrong |
| "Unknown database" | Create the database first ([Step 1](#step-1-create-the-database)) and check `DB_DATABASE` in `.env` |
| The page has no styles | Local: keep `composer dev` running. Online: run `npm run build` on your computer and upload the `public/build` folder again |
| Product images do not show | Local: run `php artisan storage:link`. cPanel: set `MEDIA_DISK=assets` in `.env` |
| "500 Server Error" online | Run `chmod -R 775 storage bootstrap/cache`, then `php artisan optimize:clear` and `php artisan optimize` |
| Changed `.env` online but nothing changed | Run `php artisan optimize` again |

Still stuck? Read [docs/SETUP_ADVANCED.md](docs/SETUP_ADVANCED.md) or contact SIOKRAFT support.
