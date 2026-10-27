# Reference comparison — starwoodmotor.co.uk vs this site

Reviewed 28 October 2026 against the live reference site. Every page in the
reference's own sitemap was opened and read; its plugin stack was read from the
markup. Nothing here is guessed.

**Status, 28 October 2026** — everything in section 3 has been built, except
where the entry says a merchant decision or a merchant account is needed. The
commercial figures stay empty: packages exist, prices do not, and ordering stays
switched off until the merchant sets them. Section 6 is unchanged and remains
deliberately unbuilt. `REQUIREMENTS_TRACEABILITY.md` lists each item with the
code and the test that proves it.

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
| `/` Home | `/` Home | Present, with the explanatory sections, check blocks, package comparison and delivery record. |
| `/packages/` | `/pricing/` | Present, with a three-tier comparison showing what each tier excludes. |
| `/packages/basic\|silver\|classic` | same paths | Present, each with its own check list and order form. |
| `/about-us/` | `/about/` | Present. |
| `/contact/` | `/contact/` | Present, with a form, phone, postal address and company details. |
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

## 3. Genuine gaps — now built

Ordered by how much they affect a visitor's decision to buy.

### 3.1 Commercial structure — built
- **Tiered packages.** Three tiers (Basic, Silver, Classic), each a WooCommerce
  product at `/packages/{slug}/`, each promising a named set of checks. A tier
  with no price cannot be bought and says so; go-live is blocked until every
  published tier has one. Which tiers to sell, and at what price, remains the
  merchant's decision — the structure no longer waits on it.
- **Per-tier "what you get" list** on each package page, and a comparison on
  `/pricing/` that shows what each tier does *not* include as well.
- **Currency.** Still GBP only, which is the correct default for UK consumers.
  Multi-currency is not built: it is only worth adding if the merchant genuinely
  sells outside the UK, and showing USD on a `.co.uk` domain, as the reference
  does, is the opposite of helpful.

### 3.2 Order form — built
- **Vehicle type**, from a list the merchant controls. Nothing they have not
  ticked can be ordered.
- **VIN as an alternative to a registration.** Either identifies the vehicle;
  a checkout naming neither is refused, on both the block and classic checkouts.
  A paid VIN-only order goes to staff rather than back to the customer.
- **Name and email** are collected by WooCommerce at checkout as before.
- **Pre-order enquiry path**: the contact form in 3.3, reachable from the
  navigation on every page.

### 3.3 Contact and support — built
- **Contact form** on `/contact/`: sends to the support address and nowhere
  else, with a hidden field for bots and a short per-visitor cooldown. Nothing
  is stored and no list is built.
- **Phone number, postal address, company and VAT numbers**, published on
  `/contact/` and in the business details search engines read. A missing postal
  address is now a go-live blocker, because a UK trader has to publish one.
- **Live chat and a WhatsApp button**: both built, both hidden until the
  merchant supplies their own account. Switching chat on names the provider on
  the cookie page, because it is a third party setting cookies on visitors.

### 3.4 Marketing depth on the home page — built
- **"What a report can tell you"**, one block per check, each stating what it
  cannot tell you as well as what it can. A check published without its limits
  reads as a guarantee, so the catalogue refuses to hold one without them.
- **"Do I need a vehicle report?"**, written for a used-car buyer, next to a
  plain statement of what a clear result does not mean.
- **A package comparison** and a closing order form.
- **Imagery** is still absent: the site is type and colour. Photography is a
  brand asset the merchant supplies — see 7.7.

### 3.5 SEO and sharing — built
- Meta description, Open Graph and Twitter card tags on every page, taken from
  the page's own words. A dedicated SEO plugin, if one is ever installed, takes
  over and these stand aside rather than printing a second, conflicting set.
- A site icon: a plain number-plate shape in the theme's navy, with no wordmark,
  for the merchant to replace with their own logo.
- `Organization` and `FAQPage` structured data. No rating, review count or award
  is emitted, because this site has none to report.
- `wp-sitemap.xml` and `robots.txt` continue to be served by core.

### 3.6 Trust signals — built
- **Reviews**: entered by the merchant on the settings screen, published as
  written with the name and date. No reviews means no reviews section, which is
  the honest state before anyone has bought a report.
- **Delivery record**: reports delivered and the middle delivery time, counted
  from this site's own orders. Hidden below ten deliveries, because one fast or
  one slow order moves the figure enough to mislead.
- **Logo strip**: the merchant pastes media-library ids on the settings screen
  and those logos appear; nothing is shipped and nothing is suggested. The field
  says plainly that it is for logos they are entitled to display — a licence
  they hold, a body they belong to, a provider they use — not a publication's
  logo standing in for an endorsement. Empty by default, which is where the
  reference's Forbes and Reuters marks would have gone. See section 6.

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
- Every check published with its limits, so a tier cannot be sold as a guarantee.
- A contact form that stores nothing and builds no list.
- A delivery record counted from real orders, hidden until it means something.

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

## 7. Decisions still needed from the merchant

None of these block the build any more; they block go-live.

1. The confirmed price for each tier, in GBP, with the VAT position stated.
   Until these exist, ordering stays switched off.
2. Whether three tiers is the right shape, and whether each one's list of checks
   matches what the merchant can actually deliver.
3. Which vehicle types are accepted, and whether VIN orders are accepted. Both
   are tick-boxes on the settings screen.
4. Registered trading name, postal address, phone number, company and VAT
   numbers.
5. Live chat and WhatsApp: the accounts, who answers them, and in what hours.
   Both stay hidden until supplied.
6. Which data sources are licensed. This decides what the marketing sections are
   allowed to say and what the fulfilment coverage grid asks staff to answer.
7. Logo, brand colours and photography, replacing the placeholder site icon.
8. Legal review of the terms, privacy, cookie and refund pages, and the
   confirmation that switches the draft notice off.
