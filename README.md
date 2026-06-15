# Scalyn Task Time Tracker

Scalyn Task Time Tracker is a Laravel-based workspace for tracking billable time, managing client work, and keeping delivery teams aligned. It brings together clients, tasks, time entries, timesheets, reports, comments, attachments, and user administration in one internal tool.

## Features

- Time tracking with logged hours, notes, and task-based entries
- Timesheets and reporting for reviewing work by period
- Client management with active and archived records
- Task management with status, priority, assignment, comments, and attachments
- Task import flow for bringing in work from external sources
- Dashboard analytics for recent activity and client hour totals
- Team and user administration for managing access and workspace roles

## Getting Started

### Requirements

- PHP 8.2+
- Composer
- Node.js and npm
- A database supported by Laravel

### Setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Copy the example environment file and generate an app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Configure your database in `.env`, then run the migrations:
   ```bash
   php artisan migrate --force
   ```
4. Install frontend dependencies and build assets:
   ```bash
   npm install
   npm run build
   ```
5. Start the app:
   ```bash
   php artisan serve
   ```

### One-command setup

This project also includes a Laravel setup script that installs dependencies, creates `.env`, generates the app key, runs migrations, and builds assets:

```bash
composer setup
```

## Demo Accounts

The seeders create three demo users:

- `admin@scalyn.local`
- `manager@scalyn.local`
- `member@scalyn.local`

All demo accounts use `Password123!`

## Helpful Commands

- `composer test` runs the test suite
- `composer setup` prepares a fresh local environment
- `composer dev` starts the app, queue listener, logs, and Vite together

