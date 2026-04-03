# UsafiKonect 🧺

**UsafiKonect** is a full-featured laundry service marketplace connecting customers with laundry providers across Nairobi, Kenya. Built with pure PHP, MySQL, Tailwind CSS, and GSAP animations.

> *"Usafi"* means cleanliness in Swahili — UsafiKonect connects you to clean.

---

## Features

### Three-Role System
- **Customers** — Browse providers, book laundry services, pay via M-Pesa or wallet, earn loyalty rewards
- **Providers** — Manage bookings, track earnings, set pricing, view analytics, handle subscriptions
- **Admins** — Platform oversight, user management, reports with CSV export, system settings, broadcast notifications

### Core Functionality
- Multi-step booking flow (provider → service → schedule → payment → confirmation)
- M-Pesa STK Push integration (Daraja API — sandbox with production scaffold)
- Wallet system with top-up, deductions, and refunds
- Loyalty program — every 5th completed booking earns a free wash
- Real-time notifications via AJAX polling (30s interval)
- Provider approval workflow with admin review
- CSV report exports for admin (bookings, revenue, providers)
- Subscription plans (Weekly / Monthly / Yearly) with DB-configurable pricing
- Support ticket system with admin replies
- Maintenance mode toggle
- Cookie consent banner

### Security
- CSRF token protection on all forms (POST-only state mutations)
- Parameterized PDO queries (no raw SQL interpolation)
- Bcrypt password hashing (cost factor 12)
- Input sanitization and output escaping via `e()` helper
- Session security: 1-hour timeout, session regeneration, IP binding
- Rate limiting on login (5 attempts per 15 minutes)
- File upload validation (2 MB limit, jpg/png/webp only)
- Safaricom IP whitelist on M-Pesa callback endpoint
- SSL verification tied to `APP_DEBUG` (enforced in production)
- `SELECT ... FOR UPDATE` locks to prevent race conditions on payments/cancellations
- Sensitive credentials masked in admin settings UI

### Frontend
- Responsive design (320px – 1920px) with Tailwind CSS
- GSAP ScrollTrigger animations, parallax effects, animated counters
- Chart.js dashboards (revenue, bookings, ratings, service breakdown)
- Skeleton loading states and toast notifications

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.0+ (no framework) |
| Database | MySQL / MariaDB with PDO (utf8mb4) |
| CSS | Tailwind CSS (CDN) |
| Animations | GSAP + ScrollTrigger |
| Charts | Chart.js |
| Icons | Font Awesome 6 |
| Email | PHPMailer (Gmail SMTP) |
| Payments | M-Pesa Daraja API |
| Server | XAMPP (Apache + MySQL) |

---

## Project Structure

```
UsafiKonect/
├── admin/                  # Admin dashboard (9 files)
│   ├── bookings.php            All bookings + view/action
│   ├── dashboard.php           Metrics + charts
│   ├── notifications.php       Notifications + broadcast
│   ├── providers.php           Approve/reject providers
│   ├── reports.php             Revenue reports + CSV export
│   ├── settings.php            Site config, M-Pesa, SMTP
│   ├── subscriptions.php       Manage subscriptions
│   ├── support.php             Support tickets
│   └── users.php               User management
│
├── api/                    # AJAX endpoints (3 files)
│   ├── bookings.php            Booking stats + data
│   ├── mpesa-callback.php      M-Pesa payment webhook
│   └── notifications.php       Mark read / fetch count
│
├── assets/
│   ├── css/style.css           Custom styles + animations
│   ├── images/                 Favicon, OG image (add before deploy)
│   ├── js/
│   │   ├── gsap-init.js        ScrollTrigger animations
│   │   ├── main.js             Toast, modals, dark mode
│   │   └── notifications.js    30s polling for unread count
│   └── uploads/profiles/       User profile images
│
├── auth/                   # Authentication (5 files)
│   ├── forgot-password.php     Password reset request
│   ├── login.php               Login + rate limiting
│   ├── logout.php              Session destroy
│   ├── register.php            Registration (all roles)
│   └── reset-password.php      Token-based password reset
│
├── config/                 # Application configuration (4 files)
│   ├── database.php            PDO connection + constants
│   ├── functions.php           All helper functions (~50+)
│   ├── mpesa.php               M-Pesa Daraja API integration
│   └── security.php            CSRF, sanitization, rate limiting
│
├── customer/               # Customer dashboard (10 files)
│   ├── book.php                Multi-step booking wizard
│   ├── booking-detail.php      Booking status + timeline + rate
│   ├── bookings.php            Booking history + filters
│   ├── dashboard.php           Overview, loyalty, quick actions
│   ├── loyalty.php             Points + free booking progress
│   ├── notifications.php       Notification center
│   ├── pay.php                 M-Pesa / wallet payment
│   ├── profile.php             Edit profile + image upload
│   ├── providers.php           Browse providers + filters
│   └── wallet.php              Balance + transaction history
│
├── includes/               # Shared partials (6 files)
│   ├── dashboard-header.php    Dashboard greeting bar
│   ├── footer.php              Footer + social links
│   ├── header.php              HTML head + SEO + Tailwind config
│   ├── maintenance.php         Maintenance mode page
│   ├── navbar.php              Responsive nav + mobile menu
│   └── sidebar.php             Role-aware sidebar + mobile nav
│
├── provider/               # Provider dashboard (11 files)
│   ├── analytics.php           Charts: trends, services, ratings
│   ├── booking-action.php      Accept/reject/update bookings
│   ├── booking-detail.php      Booking details + actions
│   ├── bookings.php            Manage bookings + status flow
│   ├── dashboard.php           Earnings, stats, charts
│   ├── earnings.php            Revenue summary + history
│   ├── notifications.php       Notification center
│   ├── pricing.php             Set service prices
│   ├── profile.php             Edit business profile
│   ├── reviews.php             View customer reviews
│   └── subscription.php        Subscribe to plans
│
├── sql/install.sql         # Full schema (12 tables) + seed data
│
├── index.php               # Landing page
├── about.php               # About page
├── contact.php             # Contact form
├── pricing.php             # Subscription plans
├── terms.php               # Terms of Service
├── privacy.php             # Privacy Policy
├── cookies.php             # Cookie Policy
├── refund.php              # Refund Policy
├── 404.php                 # Custom 404 page
├── 500.php                 # Custom 500 page
├── .htaccess               # Security headers + rewrites
├── robots.txt              # Search engine directives
├── sitemap.xml             # SEO sitemap
└── composer.json           # PHPMailer dependency
```

**Total: 58 PHP files, 3 JS files, 1 CSS file, 1 SQL file**

---

## Database Schema

12 tables in the `usafikonect` database:

| Table | Purpose |
|-------|---------|
| `users` | All accounts (customer, provider, admin) |
| `provider_details` | Business info, pricing, approval status |
| `bookings` | Service bookings with status tracking |
| `ratings` | Customer reviews and star ratings |
| `subscriptions` | Weekly / monthly / yearly plans |
| `wallet_transactions` | Top-ups, payments, refunds |
| `loyalty_points` | Points balance and free booking tracking |
| `notifications` | In-app notification system |
| `user_sessions` | Session token management |
| `support_tickets` | Customer support with admin replies |
| `site_settings` | Key-value config store (26 settings) |
| `login_attempts` | Brute-force rate limiting |

---

## Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL/MariaDB, Apache)
- [Composer](https://getcomposer.org/) (for PHPMailer)

### Setup

1. **Clone the repository** into your XAMPP `htdocs` folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/WAIYAH/UsafiKonect.git
   ```

2. **Install dependencies:**
   ```bash
   cd UsafiKonect
   composer install
   ```

3. **Create the database:**
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Import `sql/install.sql` — this creates the `usafikonect` database, all 12 tables, and seed data

4. **Configure the application:**
   - Database config is in `config/database.php` (defaults: `root` / no password)
   - M-Pesa sandbox settings in `config/mpesa.php`
   - Update `APP_URL` in `config/database.php` if your path differs

5. **Access the app:**
   ```
   http://localhost/UsafiKonect
   ```

### Test Accounts (from seed data)

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@usafikonect.co.ke` | `Admin@123` |
| **Provider** | `mama.fua@example.com` | `Password@123` |
| **Provider** | `sparkle@example.com` | `Password@123` |
| **Provider** | `freshclean@example.com` | `Password@123` |
| **Provider** | `wanjiku@example.com` | `Password@123` |
| **Provider** | `nguosafi@example.com` | `Password@123` |
| **Customer** | `john@example.com` | `Password@123` |
| **Customer** | `mary@example.com` | `Password@123` |
| **Customer** | `peter@example.com` | `Password@123` |
| **Customer** | `grace@example.com` | `Password@123` |

*Plus 6 more customer accounts — see `sql/install.sql` for the full list.*

### Seed Data Summary
- 1 admin, 5 providers (across Roysambu, Umoja, Donholm, Kilimani, Langata), 10 customers
- 25 bookings across all statuses (pending → delivered, plus cancelled)
- 10 ratings/reviews, 3 active subscriptions, wallet transactions, loyalty points
- 26 site settings pre-configured

---

## Production Deployment

When deploying to a live server, update these settings:

1. In `config/database.php`:
   - Set `APP_DEBUG` to `false`
   - Set `APP_URL` to your production domain (e.g., `https://usafikonect.co.ke`)

2. In `.htaccess`:
   - Uncomment the HTTPS redirect rule
   - Uncomment the HSTS header

3. In `robots.txt` and `sitemap.xml`:
   - Replace `http://localhost/usafikonect/` with your production URL

4. Via `admin/settings.php`:
   - Enter real SMTP credentials (Gmail app password)
   - Enter M-Pesa Daraja production API credentials
   - Set callback URL to `https://yourdomain.com/api/mpesa-callback.php`

5. Add actual image assets:
   - `assets/images/favicon.png` (32×32 / 64×64)
   - `assets/images/og-image.jpg` (1200×630)

6. Run `composer install` on the server

---

## Color Palette

| Color | Hex | Usage |
|-------|-----|-------|
| Orange | `#F97316` | Primary / CTA buttons |
| Deep Blue | `#1E3A8A` | Headers / Navigation |
| Teal | `#0D9488` | Accents / Success states |
| Cream | `#FEF3C7` | Background highlights |

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

Copyright © 2026 Lucky Nakola
