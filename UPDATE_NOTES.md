# Torres Vehicle Rental - update notes

## Database changes

There is no separate `database/001_return_flow_and_account_status.sql` file in this project. The supported schema updates are the Doctrine migrations in `migrations/`:

1. `Version20261109140633` — baseline marker for the imported database; it does not create or drop tables.
2. `Version20261112030000` — adds the `staff` role, reservation completion status, and `vehicle_returns` table.
3. `Version20261112040000` — adds the `pending` reservation status.
4. `Version20261112050000` — adds the `users.status` field (`active` / `disabled`).

Before applying updates, export a backup of your existing `torres_rental` database. Review the pending migration list and SQL dry run:

```powershell
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate --dry-run
```

Apply only after confirming that the live database matches the expected starting schema:

```powershell
php bin/console doctrine:migrations:migrate
```

Do not run `doctrine:schema:drop`, import a database dump over an existing database, or use migration rollback commands on your working database. The distributable ZIP intentionally excludes SQL dumps and private database records.

## Vehicle return workflow

1. Customer opens My Reservations and submits a return request for a confirmed reservation.
2. Customer selects whether damage is present and may upload a photo.
3. Admin or Staff inspects the vehicle and records the inspection result, notes, and optional photo.
4. Admin or the staff member who started the inspection confirms the return.
5. The reservation becomes `completed` and the selected vehicle color is made available again.

Statuses: `pending` / `confirmed` / `cancelled` / `completed` for reservations; `pending` / `inspected` / `confirmed` for vehicle returns.

## Account security

- Admin creates Staff accounts; there is no public Staff registration.
- Admin can enable or disable Customer and Staff accounts.
- Disabled accounts are rejected during login and when the security user provider refreshes an existing session.
- Use `php bin/console app:create-admin` to create an administrator interactively if the existing database has no usable Admin account.
- Passwords are stored using Symfony's password hasher.

## Template directory casing

The login and registration templates are stored in `templates/security/` (lowercase), matching the controller render paths. The customer portal templates are stored in `templates/User/` (uppercase) and are referenced with that exact casing by their controllers. Preserve this casing when extracting or deploying on Linux/Docker.

## Docker

The Docker Compose application is available at `http://127.0.0.1:8000`; phpMyAdmin is available at `http://127.0.0.1:8081`. Never expose MySQL or phpMyAdmin through ngrok. Expose only the application after local database and browser tests pass.


## Customer portal sidebar update

- Added a shared `templates/customer/portal.html.twig` layout and `public/assets/css/customer-portal.css` stylesheet.
- Applied the same responsive customer sidebar to the dashboard, reservation history, booking form, return request, profile, and password pages.
- Added Dashboard, Browse Vehicles, My Reservations, My Profile, Change Password, View Homepage, and Logout navigation.
- Added active-route highlighting, collapsible desktop navigation, mobile slide-out navigation, backdrop dismissal, and Escape-key dismissal.
- Updated the shared account controller to select the customer portal layout for customer accounts while preserving Admin and Staff layouts.
- Kept the public homepage, public fleet pages, and contact page on their normal public navigation.
- Verified Twig syntax, Symfony container wiring, and customer route registration after the change.
