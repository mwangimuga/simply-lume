# Simply Lume

Jewellery boutique for **Simply Lume** — PHP 8.1+ and MySQL, ready for cPanel.

## What it does

**Clients** browse jewellery by type and gender, search and filter (including price), save a wishlist, then order. The WhatsApp message is always in this form:

`Hello, I'd like to order _ and _`

They can also pay through **Paystack** if you add keys in Settings.

**Owner / staff** manage catalogue, stock, orders (manual fulfil), expenses, invoices, receipts, payments, and users. The dashboard refreshes sales, inventory pressure, and pending orders every few seconds.

### Roles

| Role | Access |
| --- | --- |
| Owner (`christineanyango`) | Everything |
| IT | Everything |
| Finance | Dashboard, orders (view), expenses, invoices, receipts, payments |
| Sales | Dashboard, jewellery, inventory, orders + fulfil, clients, invoices |
| Client | Shop, wishlist, own orders |

Owner login: **christineanyango** / **Christine@2026** — change this after first sign-in.

## cPanel install

1. Create a MySQL database and user in cPanel, and grant the user all privileges on that database.
2. In **Select PHP Version**, choose **8.1** or newer. Enable `pdo_mysql`, `curl`, `gd` (optional), `fileinfo`.
3. Upload this project into `public_html` (or a subdomain folder).
4. Confirm **mod_rewrite** is on. If the shop sits in a subfolder, set `RewriteBase /your-folder/` in `.htaccess`.
5. Make `uploads/products` and `includes` writable by the web user (`755` is usually enough; `includes` only needs write during install).
6. Open `https://your-domain/install.php`.
7. Enter database details, the public URL, and the WhatsApp number with country code (for example `2547XXXXXXXX`).
8. Sign in at `/login`, then **delete `install.php`**.
9. In **Settings**, add Paystack public and secret keys. Set the Paystack webhook URL to `https://your-domain/api/?action=paystack_webhook`.

Point the domain at this folder. You do **not** need a Node build step.

## Local preview

```bash
php -S localhost:8080 router.php
```

Then run `install.php` against a local MySQL database.

## Security already in place

- Passwords hashed with `password_hash`
- Prepared statements (PDO)
- CSRF tokens on forms and JSON actions
- Login throttling
- Staff role checks
- Upload allow-list (JPG/PNG/WEBP) and PHP disabled in `uploads/`
- Config, SQL, and PHP includes blocked from the web
- HttpOnly session cookies and basic security headers

Keep PHP updated, use HTTPS in cPanel (AutoSSL), and store Paystack live keys only on the server.
