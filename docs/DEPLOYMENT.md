# Deployment

How to put this site on a server and keep it safe. Written for the person who
installs it, not for the person who wrote it.

## What the server needs

| Requirement | Minimum | Why |
|---|---|---|
| PHP | 8.1 (8.3 recommended) | The plugin uses enums and readonly properties |
| MySQL / MariaDB | MySQL 8.0 / MariaDB 10.6 | WooCommerce HPOS |
| WordPress | 6.6 | Block checkout field API |
| WooCommerce | 8.9 | `woocommerce_register_additional_checkout_field()` |
| TLS | Required | Payments, logins and report links all travel over it |
| Cron | Real system cron | Delivery retries must not wait for a visitor |
| Mail | Authenticated SMTP with SPF, DKIM and DMARC | A report that lands in spam is an undelivered report |

Shared hosting is acceptable only if it provides a private directory outside
the document root, a real cron and authenticated SMTP.

## 1. Report storage — do this before taking an order

Report PDFs must never be reachable by URL. The plugin stores them, in order of
preference:

1. the path in the `VR_PRIVATE_STORAGE_DIR` constant;
2. a directory one level above the WordPress root;
3. the uploads folder, protected by `.htaccess` and an unguessable name.

**Option 3 is a fallback, not a plan.** nginx ignores `.htaccess` entirely. Set
the constant in `wp-config.php` and point it somewhere the web server does not
serve:

```php
define( 'VR_PRIVATE_STORAGE_DIR', '/var/www/vehicle-reports-private' );
```

```bash
sudo mkdir -p /var/www/vehicle-reports-private
sudo chown www-data:www-data /var/www/vehicle-reports-private
sudo chmod 750 /var/www/vehicle-reports-private
```

Confirm it afterwards: **Vehicle Reports → Settings** shows the path in use and
says whether it is outside the web root.

If you must use the uploads fallback on nginx, add this and reload:

```nginx
location ~* /wp-content/uploads/vehicle-reports-.*/ { deny all; return 404; }
```

Verify with a real request before launch — upload a report, then try to fetch
the file path directly. It must not return 200.

## 2. Install

```bash
git clone git@github.com:usman362/vehicle-report-management.git
cd vehicle-report-management
composer install --no-dev          # dev tooling is not needed on the server
wp core download --skip-content    # core is not in the repository
wp config create --dbname=… --dbuser=… --dbpass=… --dbhost=…
wp core install --url=https://… --title="…" --admin_user=… --admin_email=…
wp plugin install woocommerce --activate
wp plugin activate vehicle-report-orders
wp theme activate vehicle-report-theme
wp rewrite structure '/%postname%/' --hard
wp eval-file scripts/seed-content.php
```

Activation creates the three plugin tables, the three staff roles and the
private storage directory. The last two commands set the permalink structure
the page links rely on and create the storefront and legal pages from
`content/pages/`, which is also how you roll a wording change forward: edit the
file, commit it, run the seeder again.

## 3. wp-config.php additions

```php
define( 'VR_PRIVATE_STORAGE_DIR', '/var/www/vehicle-reports-private' );
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'FORCE_SSL_ADMIN', true );
```

Never commit `wp-config.php` or `.env`. Secrets belong in the server's own
configuration.

## 4. Cron

WordPress's built-in pseudo-cron only runs when someone visits. Delivery
retries cannot depend on traffic:

```php
define( 'DISABLE_WP_CRON', true );
```

```cron
*/5 * * * * cd /path/to/site && wp cron event run --due-now --quiet
```

## 5. Mail

Use an authenticated SMTP service with SPF, DKIM and DMARC on the sending
domain. Send a live test to an external mailbox and check it does not land in
spam before launch; a report nobody receives is a refund waiting to happen.

## 6. Settings and go-live

In **Vehicle Reports → Settings**, supply: delivery window and working days,
support address, registered trading name, terms version, approved data sources,
and the WooCommerce product with its real price.

Also supply a link to a real example report you are permitted to publish, with
the vehicle and the customer removed from it, and tick **Legal pages → Copy
reviewed** once a UK legal adviser has settled the terms, privacy, cookie and
refund wording. Until that box is ticked those pages carry a visible draft
notice and the store cannot go live.

The go-live checkbox cannot be ticked while any of those are missing, and the
storefront refuses to start an order until they are supplied. That is deliberate.

### Let customers in

WooCommerce installs new stores in **coming-soon** mode, which serves the shop,
cart and checkout only to signed-in administrators. Staff testing an order while
signed in see a working checkout and never notice. Check it before launch and
again after any WooCommerce onboarding step:

```bash
wp option get woocommerce_coming_soon      # expect: no
```

The plugin's own screens show a warning while the store is hidden.

Separately confirm with the payment provider that manually fulfilled vehicle
report services are an acceptable merchant category, and keep the gateway in
test mode until the merchant account is genuinely approved.

## 7. Backups and restore

Back up the database **and the private report directory** — report files are
not in the database and not in the repository.

```bash
wp db export backup-$(date +%F).sql
tar czf reports-$(date +%F).tar.gz /var/www/vehicle-reports-private
```

Test a restore on staging before launch, and confirm afterwards that a report
delivered before the backup can still be downloaded.

## 8. Caching

Exclude these from any page cache: `/checkout/`, `/cart/`, `/my-account/`,
order-received pages, and any URL carrying `vr-report`. The download route sets
`no-store` and `noindex` itself, but a cache in front of it can still misbehave.

## 9. Updates

Test WordPress, WooCommerce and PHP upgrades on staging first, and re-run the
checkout and the delivery flow there. WooCommerce changes its checkout
internals between major versions; the registration field is the first thing
to re-check.

```bash
./scripts/check.sh    # coding standards and unit tests, on the deployed commit
```
