# BVCC spec

The living spec for this app: goals, the site it replaces, business rules,
roadmap, feature backlog, env vars and open questions. The code is the spec;
if they disagree, fix this doc.

## Goals

Replace the Blackwater Valley Canoe Club WordPress site (bvcc.org.uk) with
this Laravel app.

- **Make running the club easier.** One admin where volunteers see who is a
  member and on which tier, plan the year's calendar, and see for each event
  who is booked, who has paid, who to chase and who has cancelled.
- **Make "come and try it" and joining the main journey.** The old homepage
  has no call to action.
- **Work brilliantly on a phone.** Most visitors, and volunteers at the
  waterside, are on one.
- **Be easy to find**, by search engines and by AI assistants. See [seo.md](seo.md).
- **Hold members' medical and child data securely.**

## Current site (bvcc.org.uk)

Crawled 2026-10-13. This is the reference for redirects (F-21) and the import (F-23).

**Platform:** WordPress with a custom 2015 `blackwatervalley` theme.

| Plugin                                      | Job                                        | Replaced by                                    |
| ------------------------------------------- | ------------------------------------------ | ---------------------------------------------- |
| Events Manager, WP FullCalendar             | Events, tickets by tier, bookings          | F-14, F-15, F-33, F-34; online booking in F-52 |
| Paid Memberships Pro (PayPal)               | Membership levels, checkout, member fields | F-16, F-31, F-32; online joining in F-51       |
| Theme My Login, security questions          | Member accounts                            | F-50                                           |
| Contact Form 7                              | Hire request form                          | F-17                                           |
| Yoast SEO                                   | Titles, sitemap                            | F-04, F-22                                     |
| GDPR, Google Analytics (a retired property) | Cookie bar, analytics                      | F-44                                           |
| CoBlocks, Vimeography                       | Galleries, video                           | Photos on pages; F-45 for galleries            |

**Content:** 63 pages (many are plugin plumbing or empty), 16 news posts,
967 event pages going back to 2019 (2 upcoming), 17 venues. The public media
API exposes only 32 photos; the rest sit in page galleries.

**Problems found on the old site** (for the committee; not part of this app):

- The club's bank sort code and account number are printed on a public event
  page, and a member discount code is printed on the public checkout page.
- Many pages link to `development.bvcc.org.uk`, which does not respond.
- Two different prices are given for monthly subscribers (see Open questions).
- The privacy policy mentions another club's website.
- Hire and booking terms still contain Covid-era wording.

## Information architecture

| Nav item         | Address    | Contains                                                                  | Old addresses it absorbs                     |
| ---------------- | ---------- | ------------------------------------------------------------------------- | -------------------------------------------- |
| (logo)           | `/`        | Home                                                                      | `/`                                          |
| Paddle with us   | `/paddle`  | Weekly sessions, beginners, learning, six disciplines, venues (`/venues`) | `/about-us/*`, `/disciplines/`               |
| Events and trips | `/events`  | Calendar, event pages, booking terms                                      | `/events/*`                                  |
| Join             | `/join`    | Membership and fees, hire (`/join/hire`), terms                           | `/membership/*`, `/hire/*`, `/hire-request/` |
| News             | `/news`    | Posts by category, RSS                                                    | `/news/*`                                    |
| Club             | `/club`    | About, committee, documents and policies                                  | `/documents/*`, `/club-clothing/`            |
| Contact          | `/contact` | Form routed to role inboxes                                               | `/contact-us/`                               |

Also: `/faqs` (replaces the old Help section). Editable pages live at
`/{paddle|join|club}/{slug}`. The old Help item is now `/faqs`. **Login**
(`/login`, with Register and Forgotten password) is back as the last nav item
and becomes My account once you're logged in (F-50, see
[members.md](members.md)).

**Home page, in order:** hero with "Come and try it"; next weekly session;
disciplines; upcoming trips and events; why paddle with us; venues;
first-time questions; latest news; closing call to action.

## Business rules

As published on bvcc.org.uk on 2026-10-13. Prices and times are data, edited
in the admin (Settings > Membership tiers, Settings > Club fees). Money is
integer pence in GBP. Everything here needs confirming with the committee.

**Participation:** individuals and families of any level, aged 8 and over.
Volunteer-run, founded 1986, home at Aldershot Lido.

**Membership tiers** (all run for one year):

| Tier                                            | Fee band          | Price (pence)               | Who                                              |
| ----------------------------------------------- | ----------------- | --------------------------- | ------------------------------------------------ |
| Non-member                                      | Non-member        | 0                           | Anyone; gives the club emergency details         |
| Youth or student: pay as you paddle             | Pay as you paddle | 1500                        | Under 18 or in full-time education               |
| Youth or student: monthly subscriber            | Subscriber        | 3000 to join + 1500 a month | As above                                         |
| Adult: pay as you paddle                        | Pay as you paddle | 3500                        | 18+ and not in full-time education               |
| Adult: monthly subscriber                       | Subscriber        | 5000 to join + 1500 a month | As above                                         |
| Family: pay as you paddle                       | Pay as you paddle | 5000                        | Up to 2 adults plus youth members at one address |
| Adult: pay as you paddle with polo shed storage | Pay as you paddle | 6000                        | 18+                                              |
| Affiliate                                       | Affiliate         | 0                           | Members of B3C or USCC                           |

**Club fees** (pence, per person):

| Fee band          | Session | Half day | Full day | League or competition |
| ----------------- | ------- | -------- | -------- | --------------------- |
| Non-member        | 1000    | 1000     | 1500     | 500                   |
| Pay as you paddle | 600     | 600      | 1000     | n/a                   |
| Subscriber        | 0       | 0        | 0        | n/a                   |
| Affiliate         | 600     | 600      | 1000     | 500                   |

- An event uses one of these fee types, or its own custom prices, or is free.
- A person's band comes from their active membership on the day of the event.
  A membership that has not been paid for does not count.
- Fees can be waived per person (volunteering, a qualifying whitewater award).
- Extra costs (accommodation, parking, course entry) are stated on the event
  and are not tracked by the system yet.

**Booking and payment:** see [admin.md](admin.md).

**Weekly session** (Wednesdays): summer at Aldershot Lido 7pm to 8:30pm;
autumn on the Basingstoke Canal 6pm to 7:30pm; winter at Yateley Manor School
pool 8pm to 9:45pm; spring on the canal 7pm to 8:30pm.

**Role inboxes** (all `@bvcc.org.uk`): enquiries, bookings, memberships,
chair, vicechair, welfare, treasurer, secretary, healthandsafety, programme,
equipment, polo, volunteering, media, webmaster. Office holders' names are
kept in the admin (Website > Committee), not in code or this doc.

## Roadmap

1. **Phase 0: foundation.** F-00 to F-05. Done.
2. **Phase 1: public site.** F-10 to F-24. Done.
3. **Phase 2: admin core.** F-30 to F-42. Done. Staff enter members,
   bookings and payments; money is tracked, not taken.
4. **Go-live.** The "To do before go-live" list below, then DNS cutover.
5. **Phase 3: member self-service.** F-50 to F-58. Accounts, online joining
   and renewal, online booking, my bookings, migrating members from WordPress.
6. **Phase 4: online payments.** F-70 to F-73.

## Feature backlog

| ID          | Feature                                   | Status        | Notes                                                                                                                                                                                                                                                                              |
| ----------- | ----------------------------------------- | ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Phase 0** | **Foundation**                            |               |                                                                                                                                                                                                                                                                                    |
| F-00        | Scaffold                                  | ✅ 2026-10-13 | Laravel 13 Vue starter kit (Inertia 3, Vue 3, TypeScript, Tailwind 4, Fortify), Pest, Pint, Larastan level 7, Filament 5. SQLite locally. UK time zone, immutable dates.                                                                                                           |
| F-01        | Site identity                             | ✅ 2026-10-13 | `config/site.php`; shared to Vue as the `site` prop. Logo, Clubmark badge and icons in `public/images/brand/`; sharing image `social.png`.                                                                                                                                         |
| F-02        | Design system                             | ✅ 2026-10-13 | See [design.md](design.md). Tokens and component classes in `resources/css/app.css`, components in `resources/js/components/site`.                                                                                                                                                 |
| F-03        | Layout, navigation and footer             | ✅ 2026-10-13 | `SiteLayout.vue`: skip link, slide-over menu on phones, sticky "Come and try it" bar on phones, footer with link columns, light and dark mode. Branded 404 page.                                                                                                                   |
| F-04        | SEO plumbing                              | ✅ 2026-10-13 | See [seo.md](seo.md). `Seo`, `Schema`, `Present`, `Markdown` in `app/Support`. Covered by `tests/Feature/Site/SeoTest.php`.                                                                                                                                                        |
| F-05        | Roles and the admin panel                 | ✅ 2026-10-13 | See [admin.md](admin.md) F-30.                                                                                                                                                                                                                                                     |
| **Phase 1** | **Public site**                           |               |                                                                                                                                                                                                                                                                                    |
| F-10        | Home page                                 | ✅ 2026-10-13 | Sections as in Information architecture. Empty sections are hidden. `tests/Feature/Site/PublicPagesTest.php`.                                                                                                                                                                      |
| F-11        | Paddle with us and disciplines            | ✅ 2026-10-13 | `/paddle`, `/paddle/{discipline}`: quick facts, body, video link, contact, upcoming events for that discipline. Six disciplines seeded from the old site's text.                                                                                                                   |
| F-12        | Editable content pages                    | ✅ 2026-10-13 | `Page` model with section, quick facts, Markdown body, FAQs by category, "last reviewed" date and a "needs checking" flag. One template, `ContentPage.vue`.                                                                                                                        |
| F-13        | Venues                                    | ✅ 2026-10-13 | `/venues`, `/venues/{slug}` with address, directions link, upcoming events, and an OpenStreetMap embed once a map pin is set. Seven venues seeded, without pins (F-46).                                                                                                            |
| F-14        | Events list and calendar                  | ✅ 2026-10-13 | `/events`: upcoming 12 months, filter by category, list grouped by month; month grid on wider screens. "How booking works" and FAQs.                                                                                                                                               |
| F-15        | Event page                                | ✅ 2026-10-13 | Quick facts, grade badge, price per band from the fee table, places left, cancelled banner, "Email to book" (prefilled), add to calendar. iCalendar feeds at `/events/calendar.ics` and `/events/{slug}.ics`. Drafts are hidden; old events stay reachable but drop out of search. |
| F-16        | Membership and fees                       | ✅ 2026-10-13 | `/join`: how to join, tiers and the fee table rendered from the same data the booking system charges, FAQs.                                                                                                                                                                        |
| F-17        | Hire                                      | ✅ 2026-10-13 | `/join/hire`: page content plus a request form (max 4 weeks, items, terms) saved to Inbox > Hire requests and emailed to equipment@. `tests/Feature/Site/FormsTest.php`.                                                                                                           |
| F-18        | News                                      | ✅ 2026-10-13 | `/news` with categories and paging, `/news/{slug}`, RSS at `/news/feed.xml`. Drafts and scheduled posts are hidden.                                                                                                                                                                |
| F-19        | Contact                                   | ✅ 2026-10-13 | Topic routes the email to that role's inbox; saved to Inbox > Enquiries; hidden-field and rate-limit spam protection. `FormsTest.php`.                                                                                                                                             |
| F-20        | Club, committee, documents, FAQs          | ✅ 2026-10-13 | `/club` (about, committee from the admin), `/club/documents` (uploads by category), `/faqs` (grouped).                                                                                                                                                                             |
| F-21        | Redirects                                 | ✅ 2026-10-13 | 301 from every old address: 47 seeded rows (editable, with use counts) plus patterns for dated news posts, news categories and old event and venue pages. `SeoTest.php`.                                                                                                           |
| F-22        | Sitemap, robots, llms.txt, Markdown pages | ✅ 2026-10-13 | See [seo.md](seo.md). `SeoTest.php`.                                                                                                                                                                                                                                               |
| F-23        | WordPress import                          | ✅ 2026-10-13 | `php artisan bvcc:import-wp`: 16 news posts, 7 policy and terms pages (flagged for review) and 40 photos, via the public REST API and page galleries. Repeatable. Lines with bank details are stripped. Run on 2026-10-13 against the live site.                                   |
| F-24        | Responsive photos                         | ✅ 2026-10-13 | `/img/{width}/{path}` serves uploads as WebP at 480, 768, 1200 or 1600px, cached on disk; `SitePicture.vue` writes the `srcset`.                                                                                                                                                   |
| **Phase 2** | **Admin core**                            |               | Detail and acceptance criteria in [admin.md](admin.md).                                                                                                                                                                                                                            |
| F-30        | Staff logins and roles                    | ✅ 2026-10-13 | `tests/Feature/Admin/AdminPagesTest.php`                                                                                                                                                                                                                                           |
| F-31        | Members                                   | ✅ 2026-10-13 | `AdminActionsTest.php`, `Club/MembershipsTest.php`                                                                                                                                                                                                                                 |
| F-32        | Memberships                               | ✅ 2026-10-13 | `Club/MembershipsTest.php`                                                                                                                                                                                                                                                         |
| F-33        | Events and weekly series                  | ✅ 2026-10-13 | `Club/BookingsTest.php`, `AdminActionsTest.php`                                                                                                                                                                                                                                    |
| F-34        | Bookings, capacity and waiting list       | ✅ 2026-10-13 | `Club/BookingsTest.php`                                                                                                                                                                                                                                                            |
| F-35        | Payments and refunds ledger               | ✅ 2026-10-13 | `Club/BookingsTest.php`, `AdminActionsTest.php`                                                                                                                                                                                                                                    |
| F-36        | Fees by membership band                   | ✅ 2026-10-13 | `Club/FeesTest.php`                                                                                                                                                                                                                                                                |
| F-37        | Event register                            | ✅ 2026-10-13 | `AdminActionsTest.php`                                                                                                                                                                                                                                                             |
| F-38        | Dashboard                                 | ✅ 2026-10-13 | `AdminActionsTest.php`                                                                                                                                                                                                                                                             |
| F-39        | Programme calendar                        | ✅ 2026-10-13 | `AdminPagesTest.php`                                                                                                                                                                                                                                                               |
| F-40        | Website content                           | ✅ 2026-10-13 | News, disciplines, pages, documents, FAQs, committee, photos, redirects, venues, categories. `AdminPagesTest.php`                                                                                                                                                                  |
| F-41        | Inbox                                     | ✅ 2026-10-13 | Enquiries and hire requests with "dealt with" and status.                                                                                                                                                                                                                          |
| F-42        | Settings                                  | ✅ 2026-10-13 | Membership tiers, club fees, staff logins.                                                                                                                                                                                                                                         |
| **Next**    | **Small things**                          |               |                                                                                                                                                                                                                                                                                    |
| F-43        | Monthly subscription tracking             | ☐             | A subscriber's monthly payments can be recorded, but nothing says "March is unpaid". Needs a decision on how subscribers pay today.                                                                                                                                                |
| F-44        | Analytics and cookies                     | ☐             | Privacy-friendly analytics; a cookie notice only if something needs one. The site sets no tracking cookies now.                                                                                                                                                                    |
| F-45        | Photo galleries                           | ☐             | Galleries on discipline and venue pages from the photo library. Only one photo per page is shown now.                                                                                                                                                                              |
| F-46        | Map pins                                  | ☐             | Latitude and longitude for each venue (Programme > Venues), which turns on the map and adds coordinates to the structured data.                                                                                                                                                    |
| F-47        | Access log for medical data               | ☐             | Record who viewed or exported emergency and medical details.                                                                                                                                                                                                                       |
| F-48        | Extra costs on a booking                  | ☐             | Accommodation and similar charges as lines on a booking, so they can be chased too.                                                                                                                                                                                                |
| F-49        | Leader role limits                        | ☐             | Leaders can currently edit any event. Limit them to their own events and registers.                                                                                                                                                                                                |
| **Phase 3** | **Member self-service**                   |               |                                                                                                                                                                                                                                                                                    |
| F-50        | Member accounts                           | 🔨            | Built 2026-10-28, not yet run (PHP missing on the dev machine): Login in the nav, sign-up with a tier, forgotten password, Member role, My membership. See [members.md](members.md).                                                                                               |
| F-51        | Join and renew online                     | ☐             | Sign-up and tier choice are done in F-50 and leave a pending membership. Still to do: eligibility checks, payment instructions or payment, renewal.                                                                                                                                |
| F-52        | Book online                               | ☐             | "Book a place" on the event page creates a pending booking; replaces "Email to book".                                                                                                                                                                                              |
| F-53        | My bookings                               | ☐             | See, pay for and cancel bookings.                                                                                                                                                                                                                                                  |
| F-54        | Family participants                       | ☐             | Manage the people on a family membership.                                                                                                                                                                                                                                          |
| F-55        | Renewal reminders on a schedule           | ☐             | Automatic emails before expiry. Staff send them by hand now.                                                                                                                                                                                                                       |
| F-56        | Waiting list offers                       | ☐             | Email the next person when a place frees up.                                                                                                                                                                                                                                       |
| F-57        | Member migration                          | ☐             | Import accounts and memberships from Paid Memberships Pro. Needs an export from the WordPress admin.                                                                                                                                                                               |
| F-58        | Members-only documents                    | ☐             | Depends on the open question about risk assessments.                                                                                                                                                                                                                               |
| **Phase 4** | **Online payments**                       |               |                                                                                                                                                                                                                                                                                    |
| F-70        | Payment gateway                           | ☐             | Stripe or PayPal behind the existing `Payment` model.                                                                                                                                                                                                                              |
| F-71        | Subscriptions                             | ☐             | Monthly subscriber payments taken automatically.                                                                                                                                                                                                                                   |
| F-72        | Automatic reconciliation                  | ☐             | Gateway payments confirm bookings and memberships without staff.                                                                                                                                                                                                                   |
| F-73        | Refunds through the gateway               | ☐             |                                                                                                                                                                                                                                                                                    |

Status: ☐ planned, 🔨 in progress, ✅ done (with date), ⏸ parked.

## To do before go-live

1. ☐ Committee to confirm prices, session times and the wording on pages
   flagged "to check" (Website > Pages and FAQs, filter "Needs checking").
2. ☐ Review the 7 imported policy and terms pages; remove Covid-era wording.
3. ☐ Add office holders' names in Website > Committee, if they are to be shown.
4. ☐ Upload policy PDFs and minutes in Website > Documents (not imported).
5. ☐ Add descriptions (alt text) to the 40 imported photos; replace them over time.
6. ☐ Create the first administrator on the server: `php artisan bvcc:staff`.
7. ☐ Enter current members and memberships, or do F-57 first.
8. ☐ Set up outgoing email (provider, verified `bvcc.org.uk` sender) and
   `SITE_PAYMENT_INSTRUCTIONS`.
9. ☐ Hosting on Laravel Cloud or Forge: Postgres, a persistent disk or bucket
   for uploads, the scheduler (expires memberships nightly), a queue worker and
   the SSR process (`php artisan inertia:start-ssr`).
10. ☐ Set `APP_ENV=production` and `APP_URL=https://bvcc.org.uk`. Until then
    every page tells search engines not to index it, on purpose.
11. ☐ DNS cutover without breaking email to the role inboxes. Keep WordPress
    reachable on a subdomain until members are migrated.
12. ☐ Submit `/sitemap.xml` in Google Search Console and Bing Webmaster Tools.
13. ☐ Take the bank details and discount code off the old site now.

## Open questions

- **Subscriber prices.** The old levels table says £30 or £50 to join then
  £15 a month; the text below it says £35 or £55 then £20 a month. The table's
  figures are seeded and flagged.
- **Winter session end time.** 9:45pm on the sessions page, 8:45pm in the old FAQ.
- **Is booking required** for weekly sessions, or only preferred?
- **Paddle UK or British Canoeing?** The old site says British Canoeing
  throughout. This site says Paddle UK, the body's current name.
- **Is Clubmark accreditation current?** The site says so, as the old one did.
- **"What to bring" and swimming ability** for beginners: the wording here is
  a sensible placeholder, not the club's own.
- **Member accounts** (F-50): linking a sign-up to an existing member record,
  committee approval of new members, under-18 sign-ups and what members may
  edit. See [members.md](members.md#open-questions).
- **How do monthly subscribers pay today** (standing order, PayPal)? Shapes F-43.
- **Should risk assessments and operating procedures be public** or members-only?
- **Retention period and consent wording** for medical and child data.
- **The "Authenticity Statement" page** was not carried over (its old address
  redirects to the privacy policy). Is it still wanted?
- **Are X (Twitter) and Vimeo still used?**
- **Can we get the logo as an SVG?** The only source is a 550×450 PNG.
- **Which online payment provider** for Phase 4: Stripe, or the club's PayPal?

## Environment variables

Set these on the server. Don't commit secrets. Local defaults are in `.env.example`.

| Variable                                                                              | Required       | Example                | Purpose                                                                                                                                              |
| ------------------------------------------------------------------------------------- | -------------- | ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| `APP_ENV`                                                                             | Yes            | `production`           | Anything other than `production` makes every page `noindex` and `robots.txt` disallow all.                                                           |
| `APP_URL`                                                                             | Yes            | `https://bvcc.org.uk`  | Canonical links, sitemap, structured data and links in email.                                                                                        |
| `APP_KEY`                                                                             | Yes            | _(generated)_          | Encrypts sessions and members' emergency and medical details. Losing it loses that data: back it up.                                                 |
| `DB_CONNECTION`, `DB_URL`                                                             | Yes            | `pgsql`                | Database. SQLite locally.                                                                                                                            |
| `MAIL_MAILER` and its settings                                                        | Yes, for email | `resend`               | Reminders, cancellations, enquiries. Locally `smtp` to Mailpit on port 1025.                                                                         |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`                                                 | Yes            | `noreply@bvcc.org.uk`  | Sender. Must be on a verified domain.                                                                                                                |
| `SITE_PAYMENT_INSTRUCTIONS`                                                           | Recommended    | _(how to pay)_         | The line in payment reminder emails. The place for bank details; never shown on public pages.                                                        |
| `SITE_EMAIL`, `SITE_BOOKINGS_EMAIL`, `SITE_MEMBERSHIPS_EMAIL`, `SITE_EQUIPMENT_EMAIL` | No             | `bookings@bvcc.org.uk` | Role inboxes; defaults in `config/site.php`.                                                                                                         |
| `SITE_LEGACY_URL`                                                                     | No             | `https://bvcc.org.uk`  | Where `bvcc:import-wp` reads from. Point it at the old site's subdomain after cutover.                                                               |
| `INERTIA_SSR_ENABLED`                                                                 | No             | `true`                 | Turn server-side rendering off. Pages still work, and meta tags and JSON-LD are still in the HTML, but body content is then rendered in the browser. |
| `FILESYSTEM_DISK` and bucket settings                                                 | For uploads    | `s3`                   | Photos and documents use the `public` disk; it must survive deploys.                                                                                 |
| `QUEUE_CONNECTION`                                                                    | No             | `database`             | Email is sent in the request at present, so a worker is only needed once mail is queued.                                                             |
