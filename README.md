# Torres Vehicle Rental – Symfony Project

A Symfony 8.1, PHP 8.4 (including `mbstring`, `intl`, `dom`, `pdo_mysql`, and `zip`), Twig, Bootstrap, and Doctrine application for customer vehicle rentals and staff rental operations.

## Main features

### Customer portal
- Customer registration, login, and logout.
- Customer dashboard with reservation and return-request summaries.
- A consistent customer sidebar across the dashboard, vehicle booking, reservation history, return requests, profile, and password pages. The sidebar supports active-page highlighting, desktop collapse, and a mobile slide-out menu.
- Browse the public fleet and view vehicle details.
- Submit a vehicle reservation with color, pickup/return dates and times, contact number, pickup location, and payment method.
- View reservation history and current statuses.
- Cancel eligible confirmed reservations (with CSRF protection).
- Submit a vehicle return request, report no damage or damage, upload an optional photo, and follow inspection status.
- View and update profile details and change password.

### Staff workspace
- Staff dashboard with fleet, reservation, and pending-return summaries.
- View the vehicle list, review reservations, and process vehicle returns.
- Inspect customer-reported vehicle condition, add inspection notes/photo, and confirm completed returns.
- Update the staff member's own profile and password.
- Staff may update an inspection they started; Admin can manage all inspections.
- Staff cannot access Admin-only dashboard or staff-account management, and cannot create/edit/delete fleet vehicles.

### Return workflow
1. Customer submits a return request for a confirmed reservation.
2. Admin or Staff inspects the actual vehicle condition and saves the inspection.
3. Admin or the staff member who owns the inspection confirms the return.
4. The reservation changes to `completed` and the selected vehicle color is marked available again.

## Setup

1. Extract the project and open the `webdev` directory (the folder containing `composer.json`).
2. Back up the existing database before applying the included migration.
3. Copy `.env.example` to `.env` for Docker Compose defaults, then replace the local sample credentials. For a direct Windows Symfony server, configure the database connection in `.env.local`.
4. Install dependencies:

   ```powershell
   composer install
   ```

5. Confirm that `.env.local` points to your existing MySQL database. For Docker, copy `.env.example` to `.env`; the Compose service connects to `database:3306`. For a Symfony server running directly on Windows, create `.env.local` with your own connection string, for example (use port 3308 for the included Docker MySQL service, or your own MySQL port):

   ```dotenv
   DATABASE_URL="mysql://YOUR_DB_USER:YOUR_DB_PASSWORD@127.0.0.1:3308/torres_rental?serverVersion=8.0.32&charset=utf8mb4"
   APP_SECRET=replace-with-a-long-random-local-secret
   ```

6. Back up `torres_rental` in phpMyAdmin before changing its schema. Then review the pending migrations:

   ```powershell
   php bin/console doctrine:migrations:status
   php bin/console doctrine:migrations:migrate --dry-run
   ```

   The included migrations add the Staff role, return workflow, pending reservation status, and account status. They do not drop existing rental tables or rows. Read the migration files before applying them to your database. Do not run `doctrine:schema:drop`.

7. Apply the migrations only after the backup and review:

   ```powershell
   php bin/console doctrine:migrations:migrate
   ```

8. Start Symfony:

   ```powershell
   symfony server:start --port=8001
   ```

   Or use Docker Desktop:

   ```powershell
   docker compose config
   docker compose up -d --build
   ```

   Docker URLs: application `http://127.0.0.1:8000`, phpMyAdmin `http://127.0.0.1:8081`.

The booking form uses the existing `locations` table; ensure it contains the pickup locations you want customers to select. Existing customer, vehicle, color, and reservation tables are retained. Do not run `doctrine:schema:drop` or recreate the database.

## Validation

Run these checks after installing dependencies:

```powershell
php bin/console lint:container
php bin/console lint:twig templates
php bin/console debug:router
php bin/console doctrine:migrations:status
```

Static checks performed on the supplied project and the updated source:
- Customer portal Twig templates use one shared responsive sidebar layout across customer-only pages.
- PHP syntax: all project PHP files passed.
- Symfony service container lint: passed.
- Twig lint: all 45 templates passed.
- YAML lint: all 24 configuration files passed.
- Doctrine mapping discovery: all 8 entities detected; mapping validation passed.
- Route registration: 62 routes loaded; all template `path()` references and dashboard-card route names were checked.
- Basic User role/status behavior and route matching for the main dashboards, contact page, and vehicle-color management passed.

Not verified in this environment:
- Live MySQL connectivity and migration execution (the sandbox PHP runtime has no `pdo_mysql` driver).
- Browser login, permissions, booking conflicts, image uploads, and reservation/return transactions.
- Docker image build or container startup (Docker is not available in this sandbox).
- `cache:clear` in the sandbox (its PHP runtime lacks DOM; the supplied Dockerfile installs DOM).

Therefore, database and browser workflows must still be tested against your own `torres_rental` database. Do not consider those live integration tests passed until you have performed them locally.

Customer and inspection photo uploads accept JPG, PNG, or WebP files up to 5 MB and are stored under `public/uploads/vehicle-returns`.


## Admin panel

The Admin panel is available at `/dashboard` for accounts with the `admin` role. It includes:

- Dashboard statistics, recent reservations, and recent contact messages.
- Vehicle management (add, edit, view, protected deletion, and per-vehicle color/image/stock management).
- Reservation management and vehicle-return inspection/confirmation.
- Customer account directory, customer details, and reservation history. Customer accounts with rental history are retained to protect records.
- Contact message inbox, message details, and deletion. Visitors and signed-in users can submit inquiries at `/contact`.
- Pickup/return location management. Locations used by reservations cannot be deleted.
- Staff account creation, editing, and deletion.
- Administrator profile and password updates.

Admin management pages require `ROLE_ADMIN`. Destructive actions use POST requests and CSRF tokens. Existing reservation-linked vehicles and locations are protected from deletion.


### Reservation approval workflow

- New customer reservations are saved with `pending` status.
- Admin or Staff can approve a pending request, changing its status to `confirmed`.
- Admin or Staff can reject a pending request, changing its status to `cancelled` and releasing the selected vehicle color.
- Customers can see the pending status in their reservation history and cancel their own pending requests.
- Existing reservations are not rewritten by the migration; existing `confirmed`, `cancelled`, and `completed` statuses are preserved.
- Apply the new migration with `docker exec -it torres_symfony_app php bin/console doctrine:migrations:migrate`.


## Account status and security

Admin can enable or disable customer and Staff accounts. Disabled accounts are rejected by the configured Symfony user checker. The migration `Version20261112050000` adds the `users.status` column with existing accounts defaulting to `active`.

## Packaging and database privacy

The distributable source package intentionally excludes `.env`, SQL backups, `.git`, `var/cache`, and installed `vendor` dependencies. Copy `.env.example` to `.env` and replace its sample values before running Docker. Keep your SQL backup private and outside any public/ngrok document root. Never reuse local sample credentials for a shared or public deployment.


## Final examination deployment (ngrok)

Do not start ngrok until the local database migrations and the Admin, Staff, and Customer workflows have been tested successfully. When the local application is ready, expose only the Symfony application port (`8000` for Docker or `8001` for the Symfony CLI). Never expose MySQL (`3308`) or phpMyAdmin (`8081`) through ngrok. Before sharing a public URL, replace all local sample passwords and the development `APP_SECRET`, use production settings, and verify that no SQL backups or private files are publicly accessible.


## Create an Admin account securely

If your existing database already has an administrator, use that account. If you need to create a new administrator, first configure the database and apply the reviewed migrations, then run the interactive command:

```powershell
php bin/console app:create-admin
```

For Docker:

```powershell
docker exec -it torres_symfony_app php bin/console app:create-admin
```

The command prompts for the administrator's name, username, email, and password. The password is entered as a hidden prompt and stored using Symfony's password hasher. It does not create a public Admin registration page.
