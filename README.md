## SaaS Starter Kit (Laravel + Vue)

This project is a modern SaaS (Software as a Service) starter kit built with Laravel 12 (PHP 8.4) and Vue 3.5 (TypeScript), designed for rapid development of multi-tenant SaaS applications.

### Features

- Multi-tenant architecture with robust tenant/user management
- Admin and customer portals with role-based access control
- Secure authentication (Laravel Sanctum), impersonation, and 2FA
- File uploads, activity/audit logs, notifications, and billing modules
- RESTful API endpoints for all major resources (admin and customer scopes)
- Real-time features, background jobs, and event broadcasting
- Modern UI with Vite, Pinia, and Tailwind CSS
- Pest-powered test suite and CI workflow


---

## Usage Guide

### 1. Requirements
- PHP 8.4+
- Composer
- Node.js (v18+ recommended)
- npm or yarn
- SQLite/MySQL/Postgres (for database)

### 2. Installation
Clone the repository and install dependencies:

```bash
git clone https://github.com/your-org/saas-starter-kit.git
cd saas-starter-kit
composer install
npm install
```

### 3. Environment Setup
Copy the example environment file and set your configuration:

```bash
cp .env.example .env
php artisan key:generate
```
Edit `.env` for your database and mail settings as needed.

### 4. Database & Seeders
Run migrations and seeders:

```bash
php artisan migrate --seed
```

### 5. Running the App
Start the development servers:

```bash
# Start Laravel backend (Herd or built-in server)
php artisan serve

# Start Vite frontend
npm run dev
```

Visit the app at http://localhost:8000 or your Herd domain.

---
