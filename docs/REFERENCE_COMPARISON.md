# Reference comparison — starwoodmotor.co.uk vs this site

Reviewed 28 October 2026 against the live reference site. Every page in the
reference's own sitemap was opened and read; its plugin stack was read from the
markup. Nothing here is guessed.

The reference is **not a quality bar to copy wholesale**. A large part of it is
either US-market copy on a UK domain, or claims that cannot be substantiated.
Those parts are listed separately in section 6 and are deliberately not built,
for the reasons given. Everything else is a genuine gap and is listed as such.

---

## 1. Reference site, as built

**Stack** — WordPress 7.1.3, WooCommerce 11.2.1, Woodmart theme + child,
Elementor, Contact Form 7 (+ Ultimate Addons), WooCommerce Extra Product
Options, YayCurrency Pro, WP WhatsApp, Tawk.to live chat, Yoast SEO.

**Pages (10, from its own sitemap)** — `/`, `/packages/`, `/about-us/`,
`/contact/`, `/cart/`, `/checkout/`, `/my-account/`, `/terms-conditions/`,
`/privacy-policy/`, `/refund_returns/`.

**Products (3)** — `packages/basic` $44.16, `packages/silver` $61.29
("2 VIN Reports"), `packages/classic` $71.22. Each has a VIN text field and an
Add to cart button.

**Two parallel order paths, which do not agree with each other**
1. Home page lead form (Contact Form 7): name, email, phone, registration/serial,
   vehicle type (Car / Bike / Boat / Caravan-Truck), package. Emails the office.
   No payment, no order record.
2. Package product page: VIN field → Add to cart → WooCommerce checkout.

**Home page sections, in order** — hero + lead form; press logos (Forbes,
Reuters); stats bar (VIN & Plate, 1,500,000+ searches, 30+ sources, Extensive
History); "Do I need a vehicle report?"; eight check-type blocks with photos and
CTAs (auction records, sales listings, usage, ownership, title brand, accident,
stolen, odometer, loan & lien); "What's included in your report" (six blocks);
six reviews with names and dates.

**Persistent UI** — floating WhatsApp button (+44 7599 077238), Tawk.to live
chat widget.

**Footer** — about blurb, Other Links (T&C, Privacy, Refund), contact block
(email, phone, postal address), "Powered & Managed by VEHICLE REPORT CHECK".

---

## 2. Page-by-page

| Reference | Here | Status |
|---|---|---|
| `/` Home | `/` Home | Present. Ours is much shorter — see 3. |
| `/packages/` | `/pricing/` | Present, but **one price, no tiers**. |
| `/packages/basic\|silver\|classic` | — | **Missing.** No tiered products. |
| `/about-us/` | `/about/` | Present. |
| `/contact/` | `/contact/` | Present, but **email only** — no form, phone or address. |
| `/cart/` | `/cart/` | Present. |
| `/checkout/` | `/checkout/` | Present, and stronger (validated registration, SLA shown, honest coverage). |
| `/my-account/` | `/my-account/` | Present. |
| `/terms-conditions/` | `/terms/` | Present, drafted for UK rather than US. |
| `/privacy-policy/` | `/privacy-policy/` | Present. |
| `/refund_returns/` | `/refunds/` | Present. |
| — | `/how-it-works/` | Extra here. |
| — | `/report-data-sources/` | Extra here. |
| — | `/faq/` | Extra here. |
| — | `/sample-report/` | Extra here. |
| — | `/order-report/` | Extra here. |

---

## 3. Genuine gaps — things worth building

Ordered by how much they affect a visitor's decision to buy.

### 3.1 Commercial structure
- **Tiered packages.** The reference sells three. This site sells one report at
  one price. This is a merchant decision, not a build decision: it changes the
  product model, the pricing page, the checkout and the fulfilment brief.
- **Per-tier "what you get" list** on each package page.
- **Currency.** The reference shows USD on a `.co.uk` domain. This site shows
  GBP, which is the correct default for UK consumers; multi-currency is only
  worth adding if the merchant genuinely sells outside the UK.

### 3.2 Order form
- **Vehicle type** (car / motorbike / van / other). Affects which sources apply.
- **VIN as an alternative to a registration**, for imports and vehicles without
  a current UK plate.
- **Name and phone** captured before checkout. WooCommerce collects a name and
  email at checkout; a phone is optional and currently unused.
- **Pre-order enquiry path** for someone who wants to ask before paying.

### 3.3 Contact and support
- **Contact form** on `/contact/` (the reference uses CF7; here it would be one
  honest form that creates an email, with spam protection).
- **Phone number and postal address** in the footer and on `/contact/` —
  required for a UK trading business under the Consumer Contracts Regulations.
- **Live chat** (Tawk.to or similar) and a **WhatsApp button**. Both are
  merchant accounts, not code.

### 3.4 Marketing depth on the home page
The reference devotes most of its home page to explaining what a report answers.
This site states its position in four short sections and stops. Worth adding,
written honestly:
- a "what a report can tell you" section, one block per check that is actually
  licensed, each saying plainly what it can and cannot answer;
- a short "is this for me?" explainer for used-car buyers;
- a worked example tied to the sample report;
- imagery. There is currently none — the site is entirely type and colour.

### 3.5 SEO and sharing — all absent here
- No meta description, no Open Graph or Twitter card tags, no site icon
  (favicon). Pages shared on WhatsApp or social currently render bare.
- `wp-sitemap.xml` and `robots.txt` are served by core and work.
- No structured data (`Service` / `FAQPage` / `Organization`).

### 3.6 Trust signals, built honestly
- **Reviews** — only once real customers have left them, attributed and dated.
- **Published counts** — e.g. reports delivered, average delivery time. These
  can be computed from this site's own order data, so they would be true.

---

## 4. What this site already has that the reference does not

Worth stating to the client, because it is where the money went.

- A real fulfilment workflow: queue, assignment, deadline against business hours,
  staff upload, delivery, retries with back-off.
- Source-by-source **coverage honesty** — a report cannot be saved until every
  published source is answered, and an unavailable check must say why. The
  customer's email repeats it source by source.
- Private report storage above the web root, **signed expiring download links**,
  revoked automatically on a full refund.
- Append-only **audit trail** with key redaction.
- **GDPR** export and erasure wired into WordPress's own privacy tools.
- Capability-based roles for staff, support and store manager.
- 75 unit tests, 101 integration assertions, coding-standards clean, CI matrix.
- Backup and restore scripts, proven by destroying and restoring the site.
- Accessibility checked with axe-core across all 16 pages; mobile layouts checked.
- Translation templates for plugin and theme.

---

## 5. Where the reference is weaker, not just different

- The two order paths disagree: the home form takes a **registration**, the
  product page demands a **VIN**, and the packages promise delivery in 60–70
  minutes while the hero promises "instant".
- The lead form takes a name, email and phone and produces **no order record**.
- Refund policy says no refunds once downloaded. For UK digital content that is
  only enforceable if the customer expressly consents to waive the 14-day
  cancellation right **before** download, and acknowledges it. The reference
  does not collect that consent.
- Terms reference **NMVTIS**, US intellectual-property law, subscriptions and
  accounts that the site does not operate, and set a minimum age of 13.

---

## 6. Deliberately not copied

Each of these is in the reference and is a rule in this project's brief.

| In the reference | Why it is not built here |
|---|---|
| Six named reviews with dates | Invented customer reviews. The brief forbids made-up reviews. Real ones only. |
| Forbes and Reuters logos in the hero | Implied press endorsement that does not exist. |
| "1,500,000+ VIN searches", "30+ sources" | Unverifiable counts. This site can publish counts from its own data instead. |
| "Accurate, **instant** vehicle reports" | Fulfilment here is a person, within a stated window. Claiming instant would be false. |
| NMVTIS, DMV, NHTSA, state/federal/police/insurance databases, title brands, liens, auction photos, odometer "450,000 cases across United Kingdom" | US systems described as if they covered UK vehicles. None of it applies to a UK registration, and no licence exists for it. |
| Named competitor ("Carsfax") used throughout | Trademark-adjacent, and comparative claims would need substantiation. |
| "100% Trusted Reports" | Absolute claim that cannot be supported. |
| Prices in USD on a UK site | UK consumers must see the price they pay, in GBP, inclusive of tax. |
| Package tiers whose extra checks are not licensed | Selling a "finance check" or "stolen check" requires a data source for it. Tiers are fine; tiers listing checks we cannot perform are not. |

---

## 7. Decisions needed before any of section 3 is built

1. One price or three tiers — and if tiers, what each one actually includes.
2. The confirmed price per tier, in GBP, VAT position stated.
3. Which vehicle types are accepted.
4. Whether VIN orders are accepted.
5. Registered trading name, postal address and phone number.
6. Live chat and WhatsApp: accounts, and who answers them, in what hours.
7. Logo, brand colours, photography.
8. Which data sources are licensed — this decides what the marketing sections
   are allowed to say.
