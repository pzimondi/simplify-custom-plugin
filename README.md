# Simplify Custom Plugin

A small, standalone WordPress plugin built during my internship at Simplify Biz LLC. It does two things:

1. **Form → JSON → Webhook automation** – when a chosen Gravity Form is submitted, the entry is mapped to clean, named fields, encoded as JSON, and POSTed to an external webhook (Zapier, Make, a CRM endpoint, an AI assistant, etc.).
2. **Custom assets** – loads the site's custom `style.css` and `script.js` without touching the theme's `functions.php`.

## How the webhook flow works

```
Visitor submits Gravity Form (ID 2)
        │
        ▼
gform_after_submission hook fires
        │
        ▼
Entry fields mapped to readable keys
  (name, email, phone, address_*, preferred_contact_method, …)
        │
        ▼
wp_json_encode() → JSON body
        │
        ▼
wp_remote_post() → SIMPLIFY_WEBHOOK_URL
        │
        ▼
Response code checked; errors written to the PHP error log
```

Example payload sent to the webhook:

```json
{
  "name": "Jane Doe",
  "name_first": "Jane",
  "name_last": "Doe",
  "email": "jane@example.com",
  "message": "I'd like a quote.",
  "address_full": "123 Main St, Rexburg, ID 83440, USA",
  "address_street": "123 Main St",
  "address_line2": "",
  "address_city": "Rexburg",
  "address_state": "ID",
  "address_zip": "83440",
  "address_country": "USA",
  "phone": "+1 208 555 0100",
  "preferred_contact_method": "Email",
  "best_time_to_call": "Morning"
}
```

## Configuration

No URLs are hard-coded. Set the webhook target and form ID either in `wp-config.php`:

```php
define('SIMPLIFY_WEBHOOK_URL', 'https://hooks.example.com/abc123');
define('SIMPLIFY_WEBHOOK_FORM_ID', 2);
```

or as WordPress options (e.g. via WP-CLI):

```bash
wp option update simplify_webhook_url "https://hooks.example.com/abc123"
wp option update simplify_webhook_form_id 2
```

Constants take priority over options. If no URL is set, the plugin logs a notice and does nothing.

## Installation

1. Copy the `simplify-custom-plugin` folder to `wp-content/plugins/`.
2. Activate **Simplify Custom Plugin** in the WordPress admin.
3. Set the webhook URL as described above.
4. Submit the form once and confirm the payload arrives at your endpoint.

## Deployment

`.github/workflows/deploy.yml` deploys the plugin to the sandbox site over FTP on every push to `master`, using repository secrets for the server credentials.

## Requirements

- WordPress 5.0+
- [Gravity Forms](https://www.gravityforms.com/)
- PHP 7.4+

## Files

```
simplify-custom-plugin/
├── simplify-custom-plugin.php   # Plugin logic (asset loading + webhook)
├── assets/
│   ├── style.css
│   └── script.js
└── .github/workflows/deploy.yml # FTP deploy on push
```
