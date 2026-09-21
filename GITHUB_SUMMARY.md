# Yasmine Artistry Booking — GitHub Project Description & Summary

Use the information below when creating or configuring your repository on GitHub.

---

## 📌 Repository Overview

- **Repository Name**: `yasmine-artistry-booking`
- **Short Tagline / About (for GitHub Repo settings)**: 
  > Production-ready WordPress booking & scheduling plugin for beauty salons, hair stylists, and mobile home-service artists with Paystack payments, dynamic location pricing, and Elementor integration.
- **Topics / Tags**:
  `wordpress-plugin` · `booking-system` · `appointment-scheduling` · `paystack` · `elementor-addon` · `salon-booking` · `beauty-salon` · `mobile-services` · `php` · `mysql` · `rest-api`

---

## 🎯 Project Summary

**Yasmine Artistry Booking** is a custom WordPress plugin developed to solve the real-world operational challenges of beauty professionals and salons providing both **in-studio appointments and mobile home services**.

Traditional booking systems are built for fixed storefronts and struggle with travel fees, transit buffers, and upfront payment collections. This plugin provides a complete, modern booking solution tailored for high-end beauty, hair, and aesthetic services.

### Core Architectural Capabilities:
1. **Multi-Step Booking Stepper**: A 4-step horizontal progress flow (Service Selection → Schedule → Customer Details → Payment) designed with zero page-jumping and smooth responsive transitions.
2. **Interactive Month Calendar**: Visual calendar picker with weekday headers, live slot availability, and disabled past dates.
3. **Dynamic Travel & Location Surcharges**: Define studio locations (₦0 extra) vs. home service coverage zones (+₦X travel fee) that calculate dynamically on checkout.
4. **Flexible Paystack Payment Modes**: Support for percentage deposits (e.g., 50%), fixed deposits (e.g., ₦15,000), or 100% full upfront payments with real-time Paystack inline checkout and secure webhook validation.
5. **Atomic Slot Locking**: Holds appointment slots in pending state for 15 minutes while client completes checkout, preventing double bookings.
6. **WP Media Library Integration**: Directly attach and preview high-resolution service images from WordPress admin.
7. **Elementor Drag-and-Drop Widget**: Native Elementor widget support alongside the standard shortcode `[yasmine_booking]`.
8. **Automated Reminders & 2-Way Calendar Sync**: WP-Cron scheduled 24-hour advance email notifications and RFC 5545 `.ics` calendar invitation generation.
9. **Safe Updates Architecture**: 8 isolated, indexed MySQL tables with `CREATE TABLE IF NOT EXISTS` protection ensuring zero data loss during plugin updates.
