# Phase 7: SEO, GEO, AEO, SEM and front-end performance

What the public site tells search engines and AI answer engines, how marketing
tags are set up, and the performance changes made in phase 7.

## 1. Meta strategy

All pages print their `<head>` through `resources/views/components/layout/seo-head.blade.php`.

- **Title / description / keywords** come from the Blade sections `title`,
  `meta_description` and `meta_keywords`, with fallbacks in `config/seo.php`
  (`default_title`, `default_description`). Every public and auth page has a
  unique title and description (checked by `tests/Feature/Seo/SeoTest.php`).
- **Canonical, `og:url` and hreflang** (`pt-BR` and `x-default`) use
  `url()->current()`, so query strings never create duplicate canonicals.
- **Open Graph / Twitter Cards**: `og:type=website`, `og-image.png`
  (1200 × 630, with type/width/height/alt), `twitter:card=summary_large_image`.
- **Icons and app metadata**: `apple-touch-icon.png` (180 px), `icon-192.png`,
  `icon-512.png`, `public/manifest.json` (theme colour `#039855`),
  `<meta name="theme-color" content="#039855">`.
- Removed: the fabricated `aggregateRating`, the `generator` meta (it leaked the
  Laravel version), ICBM geo tags and references to missing images.

## 2. Robots per route

| Pages | `<meta name="robots">` |
| --- | --- |
| `config('seo.indexable_routes')`: `home`, `contact`, `faq`, `privacy`, `terms`, `cookies` | `index, follow, max-image-preview:large, max-snippet:-1` |
| Everything else (login, register, password pages, app pages, error pages, any response rendered for an exception) | `noindex, nofollow` |

`SecurityHeaders` also sends `X-Robots-Tag: noindex, nofollow` for `admin`,
`admin/*` and `api/*` (covers `/admin/login` and `/api/documentation`), because
Filament and Swagger pages do not use `seo-head`.

## 3. Structured data (JSON-LD)

Built from PHP arrays by `App\Services\StructuredDataService`
(`StructuredDataServiceInterface`) and printed by `<x-seo.json-ld :data="..." />`,
which encodes with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`
so user-visible text can never close the `<script>` tag.

| Page | Types |
| --- | --- |
| `/` | `Organization` (logo, two `ContactPoint`s: WhatsApp and e-mail, `parentOrganization` Grupo 3W Online), `WebSite` + `SearchAction`, `SoftwareApplication` (`FinanceApplication`, two `Offer`s from `PlanPricing`, publisher/author Grupo 3W Online), `FAQPage` (`LandingContent::faq()`), `HowTo` (`LandingContent::steps()`) |
| `/contato` | `Organization` + `BreadcrumbList` |
| `/ajuda` | `FAQPage` from the questions shown (follows `?q=`) + `BreadcrumbList` |
| `/privacidade`, `/termos`, `/cookies` | `BreadcrumbList` |
| App pages | `BreadcrumbList` from `<x-ui.breadcrumb>` |

**SearchAction rationale**: Google only shows a sitelinks search box for a real
search. `/ajuda?q={search_term_string}` is the public help-centre search that
already exists, so the action points there. The app's transaction search needs
login and is not exposed.

No `Review`/`AggregateRating` is emitted: there are no real reviews.

## 4. Sitemap, robots.txt and llms.txt

- `GET /sitemap.xml` (`PageController@sitemap`): exactly `home`, `contact`, `faq`,
  `privacy`, `terms`, `cookies`. Legal pages use `config('legal.policy_updated_at')`
  as `lastmod`; the others the build date.
- `GET /robots.txt` (`PageController@robots`): allows the public pages, disallows
  `/admin`, `/api`, `/dashboard` and every app prefix (`/wallets`, `/transactions`,
  `/contas`, `/orcamentos`, `/goals`, `/categories`, `/calculadoras`,
  `/simuladores`, `/relatorios`, `/assinatura`, `/configuracoes`, `/ia`,
  `/profile`, `/aceitar-termos`, `/convites`). `/login` and `/register` stay
  crawlable so bots can read their `noindex`. Ends with the `Sitemap:` line.
- `GET /llms.txt` (`PageController@llms`, `text/plain`, [llmstxt.org](https://llmstxt.org)
  format): summary, plans with the price from `PlanPricing::formattedCurrent()`,
  features, public pages, contact and developer. It is a route, not a static
  file, so the price never drifts. Do not add `public/llms.txt` (it would shadow
  the route).

## 5. GEO / AEO content

- Landing `#faq` and `/ajuda` use answer-first Q&A blocks (`<details>/<summary>`,
  `h2`/`h3`), the same text that feeds `FAQPage`.
- `#como-usar` (3 steps) feeds `HowTo`; `#dicas` lists practical tips.
- The content lives in `App\Support\Landing\LandingContent` (`faq()`, `steps()`,
  `tips()`), so markup and JSON-LD cannot disagree, and the landing makes no
  database query.
- Copy is truthful: no invented user counts, ratings, testimonials or countdowns.

## 6. SEM and analytics setup

Set in `.env` (empty means the tag is never loaded):

```dotenv
GOOGLE_TAG_MANAGER_ID=GTM-XXXXXXX
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
FACEBOOK_PIXEL_ID=000000000000000
```

- Tags are consent-gated (see [phase-7-privacy.md](../compliance/phase-7-privacy.md)):
  Consent Mode v2 defaults are `denied`; GTM/GA4 load after analytics consent,
  the Meta Pixel after marketing consent. The GTM `<noscript>` iframe renders only
  when the consent cookie grants analytics.
- If GTM is used, configure GA4 and Meta inside GTM and leave the other two IDs
  empty to avoid double counting.
- **UTM preservation**: `App\Support\Utm::fromRequest()` keeps
  `utm_source|utm_medium|utm_campaign|utm_term|utm_content` (whitelisted, each
  `[A-Za-z0-9._-]{1,100}`) on the sign-up CTAs of the landing, contact page and
  the Pro CTA, so campaign attribution survives the click to `/register`.
- The Grupo 3W Online CTA links to
  `https://www.3wonline.com.br/?utm_source=finestro&utm_medium=cta&utm_campaign={variant}`.

## 7. Performance

- **Fonts/icons**: Font Awesome CSS loads with `media="print" onload="this.media='all'"`
  plus a `<noscript>` fallback; `dns-prefetch` for cdnjs and jsDelivr.
- **SweetAlert2** script is `defer` in the three layouts; every inline `Swal.*`
  call runs inside `DOMContentLoaded` or an event handler, and `forms.js` checks
  `window.Swal` at event time.
- **Images**: the only `<img>` in `resources/views` (Filament brand) has
  `loading="lazy" decoding="async"` and explicit width/height.
- **Caching** (`public/.htaccess`, `mod_headers` / `mod_expires`):
  `Cache-Control: public, max-age=31536000, immutable` for `/build/*` (Vite
  file names are content-hashed), 1 month for png/svg/ico/webp, 1 year for woff2,
  1 day for the manifest.
- **nginx equivalent**:

  ```nginx
  location /build/ {
      add_header Cache-Control "public, max-age=31536000, immutable";
      try_files $uri =404;
  }
  location ~* \.(png|svg|ico|webp)$ { expires 30d; }
  location ~* \.woff2$ { expires 1y; }
  ```

- **Query budgets** (measured on SQLite in-memory after a warm-up request,
  `tests/Feature/Performance/QueryBudgetTest.php`):

  | Page | Queries | Budget |
  | --- | --- | --- |
  | Landing (guest) | 0 | 2 |
  | Dashboard | 18 (same with 5 or 25 transactions) | 22 |
  | Transactions index | 14 (same with 5 or 25 transactions) | 17 |

  Equal counts for 5 and 25 rows show there is no N+1. Run with
  `QUERY_BUDGET_DUMP=1` to print the numbers.

## 8. Images

`public/og-image.png`, `apple-touch-icon.png`, `icon-192.png` and `icon-512.png`
were generated once by `.agents/tasks/phase-7-final/tools/make-images.php` (GD,
Segoe UI TTF, brand gradient `#039855` → `#054f31`). The script is a task artefact,
not app code. In the final integration step `public/favicon.svg` was recoloured to
the same `#039855` → `#054f31` gradient (it is also the Filament admin favicon and
logo, `resources/views/filament/admin/brand.blade.php`), and the script's `favicon`
mode (`php .agents/tasks/phase-7-final/tools/make-images.php favicon`) built a real
`public/favicon.ico` with 16, 32 and 48 px PNG entries and transparent rounded
corners. `seo-head` links `favicon.svg` as the main icon and `favicon.ico` as the
fallback; `SeoTest` checks both files.

## 9. Tests

`tests/Feature/Seo/SeoTest.php` (unique title/description/canonical, robots per
route, `X-Robots-Tag`, every JSON-LD block decodes, sitemap URL set, robots
disallows, llms.txt price, og image size), `tests/Feature/Performance/QueryBudgetTest.php`,
`tests/Feature/PublicPagesTest.php`.
