=== Yasmine Artistry Booking ===
Contributors: customsolutions
Donate link: https://yasmineartistry.com/
Tags: booking, salon, appointments, paystack, home service, beauty, scheduling
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-grade salon and home-service appointment engine with dynamic location pricing, Paystack deposits, atomic availability locking, and self-service rescheduling.

== Description ==

**Yasmine Artistry Booking** is a custom, production-ready salon and beauty booking plugin engineered specifically for beauty professionals providing **home services and studio appointments**.

Unlike generic scheduling plugins that assume clients visit a fixed brick-and-mortar storefront, Yasmine Artistry Booking models the real-world operational logistics of mobile artists:
* **Dynamic Location & Travel Surcharges**: Define coverage zones and areas with flat travel fees or percentage surcharges.
* **Travel Buffers & Cleanup Windows**: Automatically enforce mandatory buffer periods between bookings so artists have sufficient transit and setup time.
* **Paystack Payment Gateway**: Direct integration with Paystack Inline for upfront deposit collection (percentage, fixed amount, or 100% full payment) with webhook signature verification.
* **Atomic Slot Locking**: 15-minute temporary reservation lock prevents double bookings while clients complete payment on Paystack.
* **Customer Self-Service Portal**: Clients receive a cryptographically secure token link to view, reschedule, or cancel appointments without needing a WordPress user account.
* **RFC 5545 Calendar Integration**: Automatically generates `.ics` calendar invitation attachments for Apple Calendar, Google Calendar, and Outlook.
* **Automated Background Maintenance**: Built-in WP-Cron tasks release abandoned reservation slots and dispatch 24-hour advance appointment reminders.

== Installation ==

1. Upload the `yasmine-artistry-booking` folder to your `/wp-content/plugins/` directory, or install the `.zip` archive via **Plugins > Add New > Upload Plugin** in your WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Yasmine Booking > Settings** to configure your business name, currency, and Paystack API credentials.
4. Add your services in **Yasmine Booking > Services** and your coverage areas in **Yasmine Booking > Locations & Fees**.
5. Embed the booking form on any page or post using the shortcode:
   `[yasmine_booking]`

== Setup Paystack Webhook ==

1. Go to **Yasmine Booking > Settings > Paystack Gateway**.
2. Copy the displayed Webhook URL:
   `https://yourdomain.com/wp-json/yab/v1/paystack-webhook`
3. Log in to your Paystack Dashboard, navigate to **Settings > API Keys & Webhooks**, and paste this URL into the **Live Webhook URL** field.

== Shortcodes ==

* `[yasmine_booking]` - Renders the 4-step responsive booking flow, confirmation screens, and self-service management portal.

== Frequently Asked Questions ==

= Does the plugin create custom database tables? =
Yes, upon activation the plugin provisions 8 isolated, indexed custom MySQL tables (`yab_categories`, `yab_services`, `yab_locations`, `yab_business_hours`, `yab_special_days`, `yab_bookings`, `yab_payments`, `yab_logs`).

= What happens if a customer abandons the Paystack checkout? =
The reserved time slot is held in `pending_payment` status for 15 minutes. If no verified payment is received, the automated hourly WP-Cron worker expires the hold and frees the slot for other clients.

= Can customers reschedule their appointments? =
Yes. Each confirmation email includes a private secure management link. Clients can pick a new date and available time slot within the allowed reschedule limit and advance notice rules configured by the administrator.

== Changelog ==

= 1.2 =
* Feature: Support for 100% full payment upfront alongside standard deposit option.
* Feature: Fully customizable email notification templates with live tag placeholders for clients and administrators.
* Feature: Toggle to require or make upfront deposit optional.
* Enhancement: Modernized admin UI styling with refined typography, rounded cards, and elevated contrast.
* Enhancement: Streamlined pricing calculation and frontend payment choice radio cards.

= 1.0.0 =
* Initial production release.
