# UsafiKonect 🧺

**UsafiKonect** is a full-featured laundry service marketplace connecting customers with laundry providers across Nairobi, Kenya. Built with pure PHP, MySQL, Tailwind CSS, and GSAP animations.

> *"Usafi"* means cleanliness in Swahili — UsafiKonect connects you to clean.

---

## Features

### Three-Role System
- **Customers** — Browse providers, book laundry services, pay via M-Pesa or wallet, earn loyalty rewards
- **Providers** — Manage bookings, track earnings, set pricing, handle subscriptions
- **Admins** — Platform oversight, user management, reports, system settings

### Core Functionality
- Multi-step booking flow (provider → service → schedule → payment → confirmation)
- M-Pesa STK Push integration (sandbox simulation with production scaffold)
- Wallet system with top-up, deductions, and refunds
- Loyalty program — every 5th booking earns a free wash
- Real-time notifications via AJAX polling (30s interval)
- Provider approval workflow
- CSV report exports (users, bookings, earnings)
- Maintenance mode toggle
- Cookie consent banner

### Frontend
- Responsive design (320px – 1920px) with Tailwind CSS
- GSAP ScrollTrigger animations, parallax effects, animated counters
- Chart.js dashboards (revenue, bookings, user growth)
- Dark mode support
- Skeleton loading states and toast notifications

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+ (no framework) |
| Database | MySQL with PDO (utf8mb4) |
| CSS | Tailwind CSS (CDN) |
| Animations | GSAP + ScrollTrigger |
| Charts | Chart.js |
| Icons | Font Awesome |
| Email | PHPMailer (Gmail SMTP) |
| Payments | M-Pesa Daraja API (simulated) |
| Server | XAMPP (Apache + MySQL) |

---

## Project Structure

```
UsafiKonect/
├── admin/              # Admin dashboard & management
├── api/                # AJAX endpoints (bookings, M-Pesa callback, notifications)
├── assets/
│   ├── css/            # Custom styles, animations, dark mode
│   ├── js/             # GSAP init, main.js, notifications polling
│   └── uploads/        # User profile images
├── auth/               # Login, register, password reset
├── config/
│   ├── database.php    # PDO connection (singleton)
│   ├── functions.php   # Helpers (redirect, flash, CSRF, pagination, etc.)
│   ├── mpesa.php       # M-Pesa configuration
│   └── security.php    # CSRF tokens, sanitization, rate limiting
├── customer/           # Customer dashboard, bookings, wallet, loyalty
├── includes/           # Shared partials (header, footer, navbar, sidebar)
├── logs/               # Error logs
├── provider/           # Provider dashboard, earnings, reviews
├── sql/
│   └── install.sql     # Full schema + seed data (10 tables)
├── index.php           # Landing page
├── about.php           # About page
├── contact.php         # Contact form
├── pricing.php         # Subscription plans
├── terms.php           # Terms of Service
├── privacy.php         # Privacy Policy
├── cookies.php         # Cookie Policy
├── refund.php          # Refund Policy
├── 404.php             # Custom error page
├── 500.php             # Custom error page
├── composer.json
├── robots.txt
└── sitemap.xml
```

---

## Installation

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL/MariaDB, Apache)
- [Composer](https://getcomposer.org/) (for PHPMailer)

### Setup

1. **Clone the repository** into your XAMPP `htdocs` folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/your-username/UsafiKonect.git
   ```

2. **Install dependencies:**
   ```bash
   cd UsafiKonect
   composer install
   ```

3. **Create the database:**
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Import `sql/install.sql` — this creates the `usafikonect` database, all tables, and seed data

4. **Configure the application:**
   - Database config is in `config/database.php` (defaults: `root` / no password)
   - M-Pesa settings in `config/mpesa.php`
   - Update `APP_URL` in `config/database.php` if needed

5. **Access the app:**
   ```
   http://localhost/UsafiKonect
   ```

### Default Accounts (from seed data)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@usafikonect.co.ke` | `Admin@123` |

---

## Security

- CSRF token protection on all forms
- Parameterized PDO queries (no raw SQL interpolation)
- Bcrypt password hashing (cost factor 12)
- Input sanitization and output escaping
- Session security: 1-hour timeout, session regeneration, IP binding
- Rate limiting on login (5 attempts per 15 minutes)
- File upload validation (2MB limit, jpg/png/webp only)

---

## Color Palette

| Color | Hex | Usage |
|---|---|---|
| Orange | `#F97316` | Primary / CTA |
| Deep Blue | `#1E3A8A` | Headers / Nav |
| Teal | `#0D9488` | Accents / Success |
| Cream | `#FEF3C7` | Background |

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

Copyright © 2026 Lucky Nakola
