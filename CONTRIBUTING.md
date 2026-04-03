# Contributing to UsafiKonect

Thank you for your interest in contributing to UsafiKonect!

## Getting Started

1. Fork the repository
2. Clone your fork into `C:\xampp\htdocs\`
3. Import `sql/install.sql` via phpMyAdmin
4. Run `composer install`
5. Visit `http://localhost/UsafiKonect`

## Development Guidelines

### Code Style
- PHP 8.0+ syntax (named arguments, match expressions, null-safe operator)
- 4-space indentation in PHP files
- Use `e()` for all HTML output escaping
- Use `sanitize_input()` for all user input
- Use parameterized PDO queries — never interpolate variables into SQL

### Security Requirements
- All form submissions must use POST with CSRF tokens (`csrf_field()` + `validate_csrf_token()`)
- All state-changing operations must be POST requests, never GET
- File uploads must go through `upload_image()` which validates type and size
- Sensitive settings must not be rendered in HTML value attributes

### Database
- Schema lives in `sql/install.sql`
- All queries use PDO prepared statements
- New settings go in the `site_settings` table (key-value)
- Use `get_setting()` / `update_setting()` to access configuration

### Frontend
- Tailwind CSS via CDN (configured in `includes/header.php`)
- Custom colors: `orange-500`, `deepblue-800`, `teal-600`
- Use Font Awesome 6 Free icons only
- GSAP animations via `assets/js/gsap-init.js`

### File Organization
- Public pages go in the project root
- Role-specific pages go in `customer/`, `provider/`, or `admin/`
- API endpoints go in `api/`
- Shared partials go in `includes/`
- Helper functions go in `config/functions.php`

## Pull Requests

1. Create a feature branch from `main`
2. Keep changes focused — one feature or fix per PR
3. Test all three roles (customer, provider, admin) if your change affects shared code
4. Ensure no PHP errors or warnings in the error log

## Reporting Issues

Open a GitHub issue with:
- Steps to reproduce
- Expected vs actual behavior
- Browser and PHP version
- Relevant error log entries from `logs/error.log`

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
