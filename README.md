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
`welfare@`, `leader@` and `editor@bvcc.test`.

For server-rendered pages, run `npm run build:ssr` then `php artisan inertia:start-ssr`.

## Check it

```sh
composer test        # formatting, static analysis and tests
npm run check        # front-end lint and formatting
npm run types:check  # TypeScript
```
