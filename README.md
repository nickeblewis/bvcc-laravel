# Blackwater Valley Canoe Club

The club's website and admin: Laravel 13, a Filament admin at `/admin`, and a
public site in Inertia, Vue 3 and Tailwind 4, rendered on the server.

Start with [docs/README.md](docs/README.md). The living spec is
[docs/spec.md](docs/spec.md); working rules for people and AI agents are in
[CLAUDE.md](CLAUDE.md).

## Run it locally

```sh
composer setup                      # install, .env, key, migrate, build
php artisan migrate:fresh --seed    # club data, plus demo members and bookings
php artisan storage:link
php artisan bvcc:import-wp          # optional: news, policy pages and photos from the old site
composer dev                        # http://localhost:8000
```

Admin: `/admin`, as `admin@bvcc.test` / `password` (local demo data only).
There is a demo login for each role: `membership@`, `events@`, `treasurer@`,
`welfare@`, `leader@` and `editor@bvcc.test`. `member@bvcc.test` is a
member's login: it sees only My membership. Members log in or register from
**Login** in the site nav.

To add a staff login (including the first administrator on a fresh server):

```sh
php artisan bvcc:staff    # asks for name, email, role (default Admin) and password
```

Email: locally, everything the app sends goes to [Mailpit](https://mailpit.axllent.org),
which `composer dev` starts when it is installed. Read it at http://localhost:8025
(verification and password reset links work from there). To install it without sudo:

```sh
curl -fsSL https://github.com/axllent/mailpit/releases/latest/download/mailpit-linux-amd64.tar.gz \
  | tar -xz -C ~/.local/bin mailpit
```

Without Mailpit, set `MAIL_MAILER=log` in `.env` and read emails in `storage/logs/laravel.log`.

For server-rendered pages, run `npm run build:ssr` then `php artisan inertia:start-ssr`.

## Check it

```sh
composer test        # formatting, static analysis and tests
npm run check        # front-end lint and formatting
npm run types:check  # TypeScript
```
