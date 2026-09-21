# Yasmine Artistry Booking (WordPress Plugin) 🌸✨

[![WordPress Tested](https://img.shields.io/badge/WordPress-5.8%20to%206.7+-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20|%208.0%20|%208.1%20|%208.2%20|%208.3-indigo.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Release](https://img.shields.io/badge/Release-v1.2.0-rose.svg)](https://github.com)

> **Production-grade, mobile-first salon & beauty appointment booking engine for WordPress.** Engineered specifically for mobile beauty artists, hair stylists, and aesthetic studios offering **both home-service and studio appointments**.

---

## 🌟 Key Highlights

- 💇‍♀️ **Studio & Mobile Appointments**: Native support for travel fees, coverage zones, and automatic commute buffer times.
- 💳 **Paystack Payment Gateway**: Upfront deposit collection (percentage, fixed amount, or 100% full payment) with webhook cryptographic verification (`X-Paystack-Signature`).
- 🔒 **Atomic Slot Locking**: 15-minute temporary reservation lock preventing double bookings during checkout.
- 🎨 **Elementor Page Builder Integration**: Custom drag-and-drop Elementor widget with live styling controls.
- 📅 **Interactive Month Calendar & Time Picker**: Visual calendar view with weekday headers, live slot availability, and zero page jumps.
- 🔁 **Customer Self-Service Rescheduling**: Token-protected portal for clients to reschedule or cancel without requiring a WordPress login.
- 🗓️ **Google Calendar & RFC 5545 `.ics` Sync**: Two-way sync readiness with automated `.ics` calendar invitation attachments in confirmation emails.
- 📬 **Customizable Email Templates**: Dynamic placeholders (`{client_name}`, `{service_name}`, `{booking_date}`, etc.) for confirmations, reminders, and payment receipts.

---

## 📸 Screenshots & Architecture

```
yasmine-artistry-booking/
├── yasmine-artistry-booking.php    # Plugin bootstrap, DB tables setup & REST hooks
├── uninstall.php                   # Safe DB table cleanup on explicit deletion
├── readme.txt                      # WordPress.org standard plugin repository readme
├── admin/                          # Admin Dashboard, Service & Settings Controllers
│   ├── class-admin.php             # Menu hooks, assets, dashboard summary
│   ├── class-admin-services.php    # Services manager & WP Media Library uploader
│   ├── class-admin-categories.php  # Category taxonomy controller
│   ├── class-admin-locations.php   # Coverage zones & dynamic travel fees
│   ├── class-admin-bookings.php    # Appointment calendar, manual booking, CSV export
│   └── views/                      # Modern, high-contrast admin dashboard views
├── includes/                       # Core Architecture & Engine
│   ├── class-database.php          # 8 isolated custom MySQL tables (CREATE IF NOT EXISTS)
│   ├── class-booking.php           # Atomic slot transaction engine & state machine
│   ├── class-service.php           # Service model & pricing calculator
│   ├── class-category.php          # Category hierarchy & location addons
│   ├── class-availability.php      # Business hours, special days & buffer validation
│   ├── class-paystack.php          # Paystack API integration & webhook handler
│   ├── class-calendar.php          # Google Calendar API & ICS file builder
│   ├── class-email.php             # HTML email dispatcher & templating engine
│   ├── class-elementor.php         # Elementor Widget Registration
│   ├── class-cron.php              # Automated lock release & 24h reminders
│   ├── class-security.php          # Nonce verification, sanitization, rate limiting
│   └── class-rest-api.php          # WordPress REST API endpoints (/wp-json/yab/v1/...)
├── frontend/                       # Client-Facing Controller & Views
│   ├── class-frontend.php          # Shortcode registration ([yasmine_booking])
│   └── views/                      # Multi-step horizontal stepper & confirmation screens
└── assets/                         # Frontend & Admin Stylesheets and Scripts
    ├── css/yab-frontend.css        # Responsive, modern beauty styling (CSS variables)
    ├── css/yab-admin.css           # Elevated WordPress Admin stylesheet
    ├── js/yab-frontend.js          # In-place step navigation & Paystack popup
    └── js/yab-admin.js             # Admin AJAX operations & media picker modal
```

---

## 🚀 Installation

### Option 1: WordPress Admin (Recommended)
1. Download the latest `yasmine-artistry-booking.zip` release.
2. Log in to your WordPress Admin dashboard (`/wp-admin`).
3. Navigate to **Plugins** → **Add New** → **Upload Plugin**.
4. Choose the `yasmine-artistry-booking.zip` file and click **Install Now**.
5. Click **Activate Plugin**.

### Option 2: FTP / SFTP / SSH
1. Extract `yasmine-artistry-booking.zip`.
2. Upload the `yasmine-artistry-booking/` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin in **Plugins** → **Installed Plugins**.

---

## ⚙️ Configuration & Setup

### 1. General & Business Settings
Go to **Yasmine Booking** → **Settings**:
- Set your **Business Name**, **Contact Email**, and **Currency** (e.g., NGN, USD, GBP, EUR).
- Set your standard **Working Hours** and **Timezone**.
- Configure default **Buffer Times** (e.g., 15-30 minutes between appointments).

### 2. Paystack Gateway Setup
1. In **Settings** → **Paystack**, enter your **Public Key** and **Secret Key**.
2. Set your **Payment Mode** (`Live` or `Test`).
3. Copy your unique Webhook URL:
   ```
   https://yourdomain.com/wp-json/yab/v1/paystack-webhook
   ```
4. In your [Paystack Dashboard](https://dashboard.paystack.com/#/settings/developer), paste this into the **Live Webhook URL** field and save changes.

### 3. Services & Media Library
- Go to **Yasmine Booking** → **Services**.
- Click **Add New Service**: enter title, description, duration, price, and required deposit (Percentage, Fixed, or Full).
- Click **Select Image** to choose or upload photos directly from the **WordPress Media Library**.

### 4. Dynamic Locations & Travel Fees
- Go to **Yasmine Booking** → **Locations & Fees**.
- Add your coverage zones (e.g., *In-Studio (Lekki Phase 1)*: ₦0 fee, *Home Service (Ikoyi / Victoria Island)*: +₦10,000 travel fee).

---

## 💻 Embedding the Booking Form

### Shortcode
Add this shortcode to any WordPress page, post, or block:
```text
[yasmine_booking]
```
*(Alias: `[yasmine_booking_form]`)*

### Elementor Page Builder
1. Edit any page with **Elementor**.
2. Search for **Yasmine Booking Form** in the elements panel.
3. Drag and drop the widget into your section.
4. Customize container width, background, and alignment using Elementor's visual panel.

---

## 🛡️ Database & Security

### Custom Database Schema
The plugin creates 8 dedicated, optimized MySQL tables with foreign keys and indexes:
- `wp_yab_categories`: Service categories
- `wp_yab_services`: Service pricing, duration, and image attachments
- `wp_yab_locations`: Coverage areas and travel surcharges
- `wp_yab_business_hours`: Per-day operating schedules
- `wp_yab_special_days`: Holidays, vacations, and blackout dates
- `wp_yab_bookings`: Customer appointments, status, tokens, and address
- `wp_yab_payments`: Transaction logs, Paystack references, deposit amounts
- `wp_yab_logs`: Audit trail for cron, webhooks, and email dispatches

> **Zero Data Loss on Updates**: All tables are created with `CREATE TABLE IF NOT EXISTS`. Updating or replacing plugin files **never** deletes or overwrites existing services, bookings, or client data.

### Security Architecture
- **CSRF Protection**: All frontend and admin requests are guarded by WordPress nonces (`wp_create_nonce`).
- **Signature Verification**: Incoming Paystack webhooks are validated using HMAC SHA-512 signatures (`hash_hmac('sha512', $payload, $secret_key)`).
- **Sanitization & Escaping**: Strict parameter filtering (`sanitize_text_field`, `sanitize_email`, `esc_html`, `intval`).
- **Cryptographic Tokens**: Reschedule and cancellation links use high-entropy random tokens (`wp_generate_password(32, false)`).

---

## 🕒 Automated WP-Cron Tasks

The plugin registers automated background tasks:
1. `yab_release_pending_slots`: Runs every 15 minutes to automatically release unpaid bookings held in pending status.
2. `yab_send_reminders`: Runs hourly to send 24-hour advance email reminders to upcoming clients.

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!
1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the **GNU General Public License v2.0 or later** (GPL-2.0-or-later). See the [LICENSE](LICENSE) file for details.
