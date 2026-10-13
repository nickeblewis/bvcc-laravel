# Design: "Blackwater"

The public site's design system. The idea: the page is water. Dark, deep
water at the top of every page, wave-shaped edges where sections meet, river
contour lines behind headings, and the club's yellow as the one bright thing
on it, like a boat.

The club's colours are black and gold (its constitution says so) and the logo
is yellow on charcoal. The palette keeps both, with the black read as deep
river water.

Tokens and component classes are in `resources/css/app.css`. Components are in
`resources/js/components/site`. The admin uses Filament's own look, in teal.

## Colour

| Token                     | Value                  | Use                                                               |
| ------------------------- | ---------------------- | ----------------------------------------------------------------- |
| `ink-950` to `ink-600`    | `#04121a` to `#174c63` | Header, heroes, footer, dark-mode surfaces                        |
| `river-50` to `river-900` | `#eefbfa` to `#18454a` | Links, icons, tints, the closing band                             |
| `gold-300` to `gold-700`  | `#ffe680` to `#8a6a00` | Calls to action, focus rings, eyebrow text on dark. Nothing else. |
| `foam`                    | `#f6fbfa`              | Page background                                                   |
| `mist`                    | `#e4f1f0`              | Alternate section background                                      |

Use the semantic names in components, so light and dark mode both work:
`bg-page`, `bg-panel`, `text-fg`, `text-soft`, `border-line`, `text-link`.

Rules:

- Gold is for the thing we want people to do next. One gold button per view.
- Body text and links meet WCAG AA contrast in both modes.
- Event category colours are picked by name in the admin and mapped to
  AA-safe values in `resources/js/lib/site.ts`.
- Never rely on colour alone: the river grade badge has wave ticks and a
  number; cancelled events are struck through and labelled.

## Type

- **Display:** Bricolage Grotesque (700, 800), for headings.
- **Body:** Instrument Sans (400 to 700).
- Both are self-hosted by the Vite fonts plugin; no requests to a font CDN.
- Sizes are fluid, set for a 360px phone and scaled up with `clamp()`:
  `text-hero`, `text-title`, `text-heading`.
- One `h1` per page, in `PageHero` (or the home hero). Headings go in order.

## Water motifs

| Motif           | Where                                                    | How                                                                         |
| --------------- | -------------------------------------------------------- | --------------------------------------------------------------------------- |
| Wave edge       | Bottom of every hero, top of the footer and closing band | `WaveDivider.vue`, filled with the colour of the section it leads into      |
| Drifting waves  | Home hero only                                           | Three layers; two drift slowly. Off under `prefers-reduced-motion`.         |
| Contour lines   | Behind heroes                                            | `.contours`, an inline SVG pattern, like a river on a map                   |
| River grade     | Whitewater events                                        | `GradeBadge.vue`: grade 1 to 5 as rising wave ticks                         |
| Tide-table date | Event cards                                              | Weekday, day, month in a block of the category colour                       |
| Logo tile       | Header, footer, home hero                                | The yellow mark on its charcoal tile with rounded corners. Never on yellow. |

Motion is slow and small. Nothing moves that the visitor did not ask to move,
except the home hero's waves.

## Components

| Component                                                | Purpose                                                                             |
| -------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `SiteLayout`                                             | Header, slide-over menu, footer, sticky phone call to action, light and dark toggle |
| `PageHero`                                               | Breadcrumbs, eyebrow, the `h1`, summary, actions                                    |
| `QuickFacts`                                             | The answers people want first, as label and value pairs                             |
| `EventCard`, `GradeBadge`                                | An event in a list                                                                  |
| `DisciplineCard`, `ArticleCard`, `VenueCard`, `LinkCard` | Cards                                                                               |
| `FaqList`                                                | Questions as native `<details>`: no JavaScript needed                               |
| `FormField`                                              | Label, hint, control, error, wired up for screen readers                            |
| `SitePicture`                                            | A photo with `srcset` from the image route                                          |
| `CtaBand`                                                | The closing "Come and try it"                                                       |

Classes: `shell` (page width and gutters), `section` (vertical rhythm),
`card`, `btn` with `btn-gold`, `btn-ink` or `btn-outline`, `link`, `eyebrow`,
`field-input`, `prose-site` (Markdown content).

## Mobile rules

Checked at 360px, 768px and 1280px.

- Design the phone layout first. Wider layouts add columns; they never add content.
- Nothing scrolls sideways. Grids use `minmax(0, 1fr)` tracks; tables in
  content scroll inside themselves; the fee table becomes cards.
- Every tap target is at least 44px high.
- Filter chips scroll horizontally in one row instead of wrapping.
- The events calendar is a list on phones; the month grid is offered from 768px.
- A sticky "Come and try it" bar sits at the bottom on phones, clear of the
  home indicator, and is left off the pages that are already the next step.
- Forms use the right keyboard (`inputmode`, `type`, `autocomplete`), stack in
  one column, and show errors beside the field.
- Photos are served at the width the screen needs, as WebP, and lazy-load
  below the fold.

## Not done yet

- Photo galleries (F-45).
- A hand-drawn illustration set; icons are Lucide for now.
- A vector logo (see Open questions in spec.md).
