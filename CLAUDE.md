# BVCC (Laravel)

Website and club admin for Blackwater Valley Canoe Club. Laravel 13, a
Filament admin at `/admin`, and a public site in Inertia + Vue 3 + Tailwind 4
with server-side rendering.

## The spec

`docs/spec.md` is the living spec. It holds the feature backlog (F-01, F-02,
…), the business rules, the roadmap, env vars and open questions. Bigger areas
have their own doc: `docs/admin.md` (members, events, bookings, payments),
`docs/design.md` (the design system) and `docs/seo.md` (SEO and AEO). Read the
relevant sections before starting feature work. `docs/README.md` explains the
workflow.

When asked to work on a feature (e.g. "implement F-52"):

1. Read its backlog entry and notes. If the acceptance criteria are thin,
   propose them before building.
2. Write the acceptance criteria as Pest tests first (`it('…')->todo()` in
   `tests/Feature` is the to-do list), then make them pass.
3. Update the doc in the same change: set the status (☐ planned, 🔨 in progress,
   ✅ done with date), move now-true details into the relevant sections, add
   new env vars to the Environment variables table, and log leftovers as
   backlog items, "To do" steps or open questions.

The code is the spec. If the doc and code disagree, fix the doc.

## Commands

- `composer dev`: run the app, queue worker, log viewer and Vite together.
- `composer test`: format check (Pint), static analysis (Larastan level 7) and
  the Pest suite. All three must pass.
- `composer lint`: fix PHP formatting.
- `npm run check` / `npm run check:fix`: lint and format the front end.
  `npm run types:check`: type-check Vue and TypeScript.
- `npm run build:ssr`: build the browser and server bundles. Run
  `php artisan inertia:start-ssr` to serve rendered HTML (restart it after a build).
- `php artisan migrate:fresh --seed`: rebuild the database. `ClubSeeder` loads
  the club's real reference data; `DemoSeeder` (local only) adds made-up
  members, bookings and staff logins (`admin@bvcc.test` / `password`, and one
  per role).
- `php artisan bvcc:import-wp`: copy news, policy pages and photos from the
  old WordPress site. `php artisan bvcc:staff`: create a staff login.

## Conventions

- **Site identity** (name, emails, social links) comes from `config/site.php`.
  Never hard-code it in PHP, Blade, mail or Vue; Vue reads the shared `site` prop.
- **Money** is integer pence plus a currency code (`GBP`). Never floats. Show
  it with `App\Support\Money::format()`; edit it with
  `App\Filament\Support\MoneyInput`.
- **Business rules live in `app/Services`** (`Bookings`, `Memberships`,
  `Payments`, `FeeCalculator`, `EventSeries`). Filament actions and
  controllers call them; they do not change statuses or prices themselves.
- **Private data never reaches the public site.** Public controllers pass
  models through `App\Support\Present`, which is the only place that decides
  what a page may see. No member, booking, payment or leader data goes in an
  Inertia prop. Emergency contacts and medical notes are encrypted casts,
  hidden from JSON, and shown only where `User::canSeeMedical()` allows.
- **Every public page declares its SEO.** Controllers extend
  `Site\SiteController` and call `$this->page(component, props, new Seo(…))`.
  The root Blade view prints the title, meta tags and JSON-LD, so they are in
  the HTML without JavaScript. Do not set titles from Vue. See `docs/seo.md`.
- **Content is Markdown**, rendered by `App\Support\Markdown::html()`, which
  escapes typed-in HTML. That is the only source allowed in `v-html`.
- **Admin access** goes by role area: a resource uses the
  `RestrictedToArea` trait and names its `Area`. The role-to-area table is
  `UserRole::areas()`.
- **Public site styles**: use the tokens and component classes in
  `resources/css/app.css` (`shell`, `section`, `card`, `btn btn-gold`,
  `link`, `field-input`, `prose-site`) and the components in
  `resources/js/components/site`. Design for a 360px phone first; every tap
  target is at least 44px; nothing scrolls sideways. See `docs/design.md`.
- **Forms**: every field has a visible `<label>` through `FormField.vue`;
  errors show beside the field. Placeholders are never labels.
- **Old addresses**: anything the WordPress site served should redirect.
  Add rows in Website > Redirects (or `ClubSeeder::redirects()`); patterns are
  in `Site\RedirectController`. The fallback route must stay last in
  `routes/web.php`.
- **Dates** are stored and shown in UK time (`Europe/London`) and are
  immutable Carbon objects. Format them on the server, in `Present`.
- **Member accounts** (F-50, `docs/members.md`): members log in and sign up at
  `/login` and `/register` (Fortify, drawn as `site/account/*` pages), and use
  the admin with the `Member` role, which has no areas. Staff sign in at
  `/admin/login`. The admin needs a verified email, so staff logins you create
  must be marked verified (`markEmailAsVerified()`); `email_verified_at` is not
  fillable.
