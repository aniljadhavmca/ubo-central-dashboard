# UBO Central Dashboard — Project Notes

Date: June 2025
Project: WordPress Plugin for unitedbyom.com
Developer: Anil Jadhav

---

## What We Built

A custom WordPress plugin called **UBO Central Dashboard** installed on the main site `unitedbyom.com` that pulls live WooCommerce data from two regional sites:

- 🇺🇸 `us.unitedbyom.com` — US Store
- 🇮🇳 `in.unitedbyom.com` — India Store

---

## Plugin Features

### 1. Central Dashboard (Overview Page)
- 8 stat boxes in 2 rows showing:
  - US Orders, India Orders, US Products, India Products
  - US Revenue, India Revenue, US Out of Stock, India Out of Stock
- Recent 5 orders from each site side by side
- Color-coded order status badges
- Last loaded timestamp
- All boxes are clickable and link to filtered pages

### 2. Orders Page
- Switch between US / India site
- Filter by order status (pending, processing, completed, on-hold, cancelled, refunded, failed)
- Filter by date (orders after a specific date)
- Full order table showing:
  - Order #, Date, Status, Customer Name, Email
  - Items ordered, Total, Payment Method, Shipping Address

### 3. Inventory Page
- Switch between US / India site
- Filter by stock status (in stock, out of stock, on backorder)
- Full product table showing:
  - ID, Name, SKU, Type, Price, Sale Price
  - Stock Status, Quantity, Categories
- Variable products show all variations indented below with individual stock qty

### 4. Settings Page
- Secure form to enter API credentials for both sites
- Fields: Site URL, Consumer Key, Consumer Secret
- Keys stored in WordPress options table — never hardcoded

---

## Technical Details

### File Structure
```
ubo-central-dashboard/
├── ubo-central-dashboard.php       # Main plugin bootstrap + menu registration
├── includes/
│   ├── class-api-client.php        # WooCommerce REST API handler + x-wp-total header
│   ├── class-orders.php            # Orders fetch + get_totals for dashboard stats
│   └── class-inventory.php        # Products + variations fetch
└── admin/
    ├── dashboard-page.php          # Modern overview UI
    ├── orders-page.php             # Orders table with filters
    ├── inventory-page.php          # Inventory table with filters
    └── settings-page.php          # API credentials form
```

### API Used
- WooCommerce REST API v3
- Endpoints: `orders`, `products`, `products/{id}/variations`, `reports/sales`
- Auth: Basic Auth (Consumer Key + Consumer Secret)
- Product/Order counts via `x-wp-total` response header

---

## GitHub Repository

- Repo: https://github.com/aniljadhavmca/ubo-central-dashboard
- Branch: main
- First commit: "Initial commit: UBO Central Dashboard plugin"

---

## Time Spent

| Task                                      | Time       |
|-------------------------------------------|------------|
| Planning & architecture                   | 45 mins    |
| Backend / PHP development                 | 4 hrs 30 mins |
| Frontend / UI (Dashboard)                 | 2 hrs      |
| DevOps (Git setup + push)                 | 30 mins    |
| Bug fixes & review                        | 45 mins    |
| **Total**                                 | **8 hrs 30 mins** |

---

## Known Issues / Limitations

1. API keys need Read permission minimum — Read/Write needed for reports/sales revenue
2. Variable products fetch variations in a loop — can be slow with large catalogs
3. No caching yet — every page load hits remote APIs (8 calls on dashboard)
4. Arrow functions used — requires PHP 7.4+ on server

---

## Pending Tasks

1. Add WP transient caching (30 min refresh) — 1 hr
2. PHP 7.x arrow function compatibility fix — 30 mins
3. Auto-deploy via GitHub Actions to unitedbyom.com — 1 hr
4. Pagination for orders and inventory pages — 1 hr
5. Export orders/inventory to CSV — 1.5 hrs
6. Live testing on production with real API keys — 1 hr

---

## How to Install

1. Zip the `ubo-central-dashboard` folder
2. Upload to `unitedbyom.com` → Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Go to UBO Dashboard → Settings
5. Enter API credentials for US and India sites
6. Save and visit UBO Dashboard → Overview

## How to Update (via Git)

```bash
cd /Users/anil.jadhav/Desktop/WP/ubo-central-dashboard
git add .
git commit -m "your update message"
git push
```
