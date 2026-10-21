# LifeSync

LifeSync is a PHP and MySQL planner for managing tasks across personal life areas.

## Requirements

- PHP with PDO MySQL enabled
- MySQL or MariaDB

## Local setup

1. Create a MySQL database named `lifesync_db`.
2. Import [`database/lifesync_schema.sql`](database/lifesync_schema.sql).
3. Configure the local connection in `config/connection.php` if your XAMPP MySQL credentials differ from the defaults.
4. Place the project under your PHP web server document root and open `login.php`.
5. Create an account using `register.php`.

## InfinityFree deployment

1. Create a MySQL database in the InfinityFree control panel and import `database/lifesync_schema.sql`.
2. Upload the project files and folders into the hosting account's `htdocs` directory.
3. In `config/connection.php`, set the InfinityFree MySQL hostname, database name, username, and password provided by the hosting control panel.
4. Do not commit database passwords, FTP credentials, or exported user data to GitHub. If a credential was shared publicly, rotate it before deployment.

The generated `LifeSync-infinityfree.zip` is included as a convenience for uploading the PHP files. Import the database schema separately; SQL files are not inside the ZIP.

## Sessions and transactions

- PHP sessions are used for authentication, CSRF tokens, and one-time feedback messages.
- Account registration is atomic: creating the user and that user's default life areas are committed together or rolled back.
- Adding a task runs in a database transaction.
- Updating a task runs in a database transaction.

## Database schema

The base tables (`users`, `pillars`, `tasks`, and `schedule_blocks`) are defined in [`database/lifesync_schema.sql`](database/lifesync_schema.sql). The profile-fields migration is also available at [`database/add_user_profile_fields.sql`](database/add_user_profile_fields.sql) for existing databases that need it.
