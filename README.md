# UBO Central Dashboard

WordPress plugin for `unitedbyom.com` to pull WooCommerce orders and inventory from US and India sites.

## Installation

1. Upload the `ubo-central-dashboard` folder to `/wp-content/plugins/` on `unitedbyom.com`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Go to **UBO Dashboard → Settings**
4. Enter the API credentials for both sites:
   - Site URL (e.g. `https://us.unitedbyom.com`)
   - Consumer Key (`ck_...`)
   - Consumer Secret (`cs_...`)
5. Save Settings

## Features

### Orders
- Filter by site (US / India)
- Filter by order status
- Filter by date
- Shows: Order #, Date, Status, Customer, Email, Items, Total, Payment Method, Shipping Address

### Inventory
- Filter by site (US / India)
- Filter by stock status
- Shows: ID, Name, SKU, Type, Price, Sale Price, Stock Status, Quantity, Categories
- Variable products show all variations with individual stock qty

## WooCommerce REST API Key Setup (on US & India sites)

1. WooCommerce → Settings → Advanced → REST API
2. Click **Add Key**
3. Description: `UBO Central Dashboard`
4. Permissions: **Read**
5. Click **Generate API Key** — copy both keys immediately
