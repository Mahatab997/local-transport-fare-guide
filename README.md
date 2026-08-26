# Local Transport Fair Guide

A PHP / MySQL transport guide application for local route, fare, and user management.

## Setup
1. Copy this project into `C:\xampp\htdocs\LTFG`.
2. Start Apache and MySQL from XAMPP.
3. Import `database/transport.sql` into MySQL using phpMyAdmin or the `mysql` CLI.
4. Update `config/config.php` if your MySQL credentials differ.
5. Open `http://localhost/LTFG` in your browser.

## Default Admin Login
- Email: `admin@localguide.test`
- Password: `Admin123!`

## Project Structure
- `auth/` — login, register, logout
- `admin/` — admin dashboard and management areas
- `user/` — user dashboard and transport tools
- `includes/` — shared layout and helper files
- `config/` — database and application configuration
- `assets/` — CSS, JS, icons, images
- `database/transport.sql` — schema and seed data

## Notes
- Use secure credentials for production deployments.
- Add admin CRUD forms and API endpoints as needed.
