# Changelog — WM BFSG Compliance Dashboard

All notable changes to **WM BFSG Compliance Dashboard** are documented in this file in adherence to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.2.2] - 2026-11-21

### Fixed

- Screenshot 1 showed the WM Suite menu from before WM Suite Hub 1.2.3, with entries the hub no longer has («Marketplace (Lösungspakete)», «Merkle-Audit-Protokoll», «CRA- & SBOM-Zentrale»); it is retaken with the current menu. The three finding counts of the report («%d Bild ohne alt-Attribut», «%d Link ohne Text», «%d übersprungene Überschriftenebene») repeated one form for several numbers in Arabic, Croatian, Czech, Irish, Latvian, Lithuanian, Maltese, Polish, Romanian, Slovak and Slovenian, for example Polish «obrazy/obrazów» for both 2 and 5 and the Arabic plural for one; each number now gets the form its language uses (Polish «2 obrazy», «5 obrazów»). The plugin's code is unchanged.

## [1.2.1] - 2026-11-21

### Changed

- The bundled family stylesheet is identical to WM Suite Hub 1.2.5 again: it starts the admin header below WordPress's help toggle instead of shortening it. This plugin's own screens register no help tab, so they change only where another plugin adds one.
- The translation catalogs are regenerated from the code: the `.pot` header names the current version, strings the code no longer uses are gone, and the block-editor files carry the block's title, description and keywords with the context WordPress looks them up by.

## [1.2.0] - 2026-11-20

### Changed
- The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
- The `.wordpress-org/` banners show the current version.

### Removed
- The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

## [1.1.3] - 2026-11-19

- Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.

- Added root `SUPPORT.md`, `.github/ISSUE_TEMPLATE/{config.yml,bug_report.yml}`, and explicit zero-support / "AS IS" liability notices across `README.md`, `README.de.md`, `README.es.md`, `readme.txt`, `SECURITY.md`, and `llms.txt`.
- Migrated plugin author/catalog/repository metadata to personal open-source distribution (`Arnold Wender`, `https://arnoldwender.com/wordpress-plugins/wm-bfsg-compliance-dashboard/`, `https://github.com/arnoldwender/wm-wp-bfsg-compliance-dashboard`), normalized FSF GPL-2.0 `LICENSE` metadata where applicable, and updated `.wordpress-org/` banner branding to `ARNOLD WENDER · OPEN SOURCE`.

## [Unreleased]

## [1.1.2] - 2026-11-19

### Changed
- Archive superseded legacy banner/screenshot files under `versionen/visuals-pre-2026-11-19/` and embed the live `.wordpress-org/` visual kit (`banner-1544x500.png`, `banner-772x250.png`, `icon.svg`, `icon-128x128.png`, `icon-256x256.png`, `screenshot-1..4.png`).
- Add `== Screenshots ==` captions in `readme.txt`, live screenshot tables and official plugin page links in `README.md`, `README.de.md`, `README.es.md`, and `llms.txt`, and align release metadata at `v1.1.2`.

## [1.1.1] - 2026-11-19

### Changed
- Point `Plugin URI` to `https://arnoldwender.com/wordpress-plugins/wm-bfsg-compliance-dashboard/` and align release metadata at `v1.1.1`.

---

## [1.1.0] - 2026-11-16

### Added
- **Gutenberg Block Editor Integration:** Added `blocks/anlage-3/block.json`, `blocks-src/index.js`, `build/index.js` and `build/index.asset.php`, registering editor script `wm-bfsg-dashboard-blocks-editor` with `wp_set_script_translations()`, `InspectorControls` for optional statement overrides, and live `ServerSideRender` preview in the Block Inserter.
- **Opt-In Uninstallation (`wm_bfsg_dashboard_delete_data_on_uninstall`):** Updated `uninstall.php` to preserve operator statement settings and pattern-check reports by default unless data deletion on uninstall is explicitly enabled via the registered setting and checkbox on the statement settings tab.
- **Developer Hooks & Block Ecosystem Bridges:** Added filters `wm_bfsg_dashboard_declaration_html` and `wm_bfsg_dashboard_audit_report`, actions `wm_bfsg_dashboard_ready` and `wm_bfsg_dashboard_audit_completed`, WordPress 6.5+ Block Bindings source `wm-bfsg-dashboard/metrics`, and GenerateBlocks 2.x Dynamic Tag `{{wm_bfsg_dashboard_metric}}`.
- **Complete 28-Locale Translations & JED Catalogs:** Regenerated `languages/wm-bfsg-compliance-dashboard.pot` covering all PHP and block-editor strings and compiled complete translations across all 28 `.po`/`.mo` catalogs and 28 JED `.json` bundles.
- **SBOM & Playground Blueprint:** Added CycloneDX 1.6 `bom.json` (`GPL-2.0-or-later`) and `.wordpress-org/blueprints/blueprint.json`.

### Changed & Fixed
- **Admin Preview Escaping:** Wrapped the statement preview output in `AdminDashboard::render_dashboard()` with `wp_kses_post()`.
- **Documentation & CI Alignment:** Synchronized English, German, Spanish and 24 translated READMEs, `DEVELOPERS.md`, `SECURITY.md` and `docs/ARCHITECTURE.md` with version `1.1.0`, and cleaned CI workflow comments.

### Translation catalog (2026-09-20)

- **82 of the 95 translatable strings had no entry in the `.pot`.** What this plugin prints is a legal page — the Erklärung zur Barrierefreiheit — so a heading no translator can see is a legal page that cannot follow the site it sits on into another language. The template is now generated from the code with `wp i18n make-pot`, every `.po` merged with `msgmerge --no-fuzzy-matching` and every `.mo` rebuilt.
- **The garbled-headings issue is closed by measurement.** The Known issue reported that `bg_BG`, `cs_CZ` and `el` rendered the public statement's headings double-encoded. Re-measured 2026-09-20: **0 double-encoded entries across all 29 catalogs**, and **0 of the surviving live translations** carry double-encoded text. The repair pass of 2026-09-17 fixed it and the note was never updated.
- 161 entries whose strings no longer exist became obsolete `#~` entries; 27 real translations survived, verified against the code **before** the merge. Honest coverage: 27 of 2,716.
- `tests/test-pot-freshness.php` and a CI step keep the catalog matching the code.

### The version this file opens on matches the version the plugin ships (D15, 2026-09-17)

- **The plugin is 1.0.0, and the changelog now opens on 1.0.0 too.** This file used to open on `[1.1.0]`, a release that `git tag` (0) and `gh release list` (0) both deny and that no other file in the repo agreed with. The `1.1.0` entry moved **verbatim and complete** to an appendix at the foot of this file: nothing was deleted, because a changelog is a record and the record is that those entries were written. Moved by the changelog maintenance utility, which refuses to write if a single non-blank line would be lost.

### Family sheets (2026-09-16)
- Re-synced from the hub: the badge variants carry the state tokens on body surfaces, where the command-banner inks measured 1.49:1 to 2.21:1 against the composed background (hub PR #19), and the canonical sheet dropped five classes that belonged to a single plugin (hub PR #21). `tests/test-ui-family.php` re-pins both md5s.

### Fixed (2026-09-15)
- **The public statement named the day of each page view as its creation date.** Without a stored date the generator used `wp_date( 'd.m.Y' )` at render time, so "Diese Erklärung wurde am … erstellt" changed daily. The settings form has a date field (prefilled with today while nothing is stored, saved only as a valid date), the shortcode passes the saved date, and without one the statement says "Das Erstellungsdatum ist noch nicht angegeben." Measured in einer lokalen WordPress-Installation on the published statement page. Sites that saved settings before this change see "not stated" until they save again.
- **Sticky posts could push a run past 20 items and out of date order.** Outside wp-admin (REST, WP-CLI) WordPress treats the query as the home query and puts sticky posts in front. `ignore_sticky_posts`, `orderby date DESC` and `no_found_rows` are set.
- **A page with one image without alt was labelled `compliant`.** Item status is `findings` or `no_findings`; the widget no longer turns green when nothing was found.
- **The image check read `data-alt=` as an alt attribute and missed `alt = "x"`;** the link check flagged links named by `aria-label` or `title`. Both patterns corrected. Finding texts are German through `_n()` (they were English in the German report).
- **`tests/test-anlage3-claims.php` exited 0 with failures,** so the fleet runtime script could not go red on it.
- "Module Tabs" became "Bereiche"; the adapter docblock no longer speaks of continuous audits.
- Tests: groups 9-11 in the standalone suite (59 assertions, a PHP warning fails the run); `tests/run-mutations.sh` with 10 mutations, all red; CI runs it on PHP 8.3.

### Documented (2026-09-15)
- README (EN/DE/ES), DEVELOPERS.md, readme.txt, CLAUDE.md, AGENTS.md (replaced with the current CLAUDE.md: it still described `/src/`, PHP 8.1 and Chart.js), llms.txt and `public/humans.txt` (no "WCAG 2.2 AA", "DSGVO … Compliant", "HMAC-SHA256 Cryptographic Audit Logs", MySQL layer, React or Gutenberg editor; "Founder" instead of "Founder & CEO") follow the code. The README no longer shows the banner ("WCAG 2.2 AAA AUDIT SCORE 98%", "BITV 2.0 READY", "PDF AUDIT REPORT EXPORT"); `assets/images/` is left out of the package, the file stays pending a decision. The screenshots are marked as the pre-2026-09-14 Spanish screen, the "24 EU Languages" badge is gone, and the catalog note says what was measured: some current statement strings are translated with double-encoded text in `bg_BG`, `cs_CZ` and `el`. Emoji removed from the headings below.

### Fixed (2026-09-14, honest statement and checks)
- **The statement published invented facts on unconfigured sites.** Without settings it declared the site `vollständig konform`, named "WM Suite Compliance Officer" as contact person and printed `+49 345 000000` (the admin form defaulted to "Arnold Wender (Lead Compliance Auditor)" and `+49 345 213890`), and it said the assessment rested on "eingehender technischer und semantischer Server-Side-Prüfung" and "kontinuierlicher automatisierter Server-Side AST-Analyse und manuellen Stichproben gemäß dem 98-Schritte-Prüfverfahren des BIK BITV-Tests" — none of which the plugin performs. An unset level now reads "Der Stand der Vereinbarkeit ist noch nicht angegeben.", an unset phone or contact person is left out, and the statement says the level is the operator's self-assessment. Test group 3 and `tests/test-anlage3-claims.php`.
- **The website link was printed as visible HTML** inside the legal page: the `<a href>` sat inside an `esc_html__()` template. The link is built outside the translatable string (fleet audit 2026-09-13, bfsg-dashboard D3).
- **The admin told operators to paste `[wm_bfsg_declaration]`**, a shortcode nothing registers (D2). It names `[wm_barrierefreiheitserklaerung]`, which is registered; test group 7 pins both.
- **A legal verdict came out of three regex checks.** The report set `legal_status` to "RECHTSKONFORM (Compliant)" from a score of 90, named "Werbeagentur WM Suite BFSG Compliance Engine (Halle/Saale)" as issuer and carried a SHA-256 over values printed beside it; the admin showed "Sello de Conformidad: CERTIFICADO" and a "Huella Digital SHA-256" computed from the fixed string `wm-bfsg-audit`, and the scanner labelled a score of 90 "EN 301 549 Level AA Passed" (D4, D5). The report now says what the three checks found, with a run identifier; the scanner state is `NOTHING_CHECKED`, `NO_FINDINGS_IN_THREE_CHECKS` or `FINDINGS`. Whether a report document should exist stays open for Arnold.
- **Runs stored by earlier versions kept their verdict.** A site checked before this change still held `compliance_state` "EN 301 549 Level AA Passed" without a count of pages with findings (measured on a local WordPress install: a run from 2026-08-29), which the hub telemetry would have passed on and the report would have read as "no findings". `AccessibilityAuditor::stored_results()` treats a run without the current shape as not run; every reader uses it (test group 5).
- **An empty site scored 100** and a site never checked read as `HEALTHY` in the hub. No content checked now means no score (`null`) and hub `NEEDS_CONFIG`; scores from 60 to 79 returned `WARNING`, a status the hub rejects, and are `DEGRADED`.
- **The dashboard widget ran and stored the whole check on every dashboard load** (D6). It reads the stored run.
- **The settings form saved any level string** from `$_POST` without unslashing (D7). Only the three levels the form offers are kept; the rest is stored empty.
- **REST:** `/run-audit` answered `success: true` when nothing was checked; `/preview-declaration` ignored the saved settings; the GET `/audit-certificate` ran and stored a check when none was stored. All three corrected (test group 8).
- **WP-CLI:** `wp bfsg-audit scan` reported success with nothing checked and printed an "Audit SHA-256 Hash" over the score and the time; it now exits with an error when nothing was found and lists the findings. `wp bfsg-audit anlage3` ignored the saved settings.
- **Hub adapter:** `declaration_active` read a `published_url` setting nothing writes (always `DRAFT`) and an unset level was reported as `nicht konform`; telemetry now carries `declared_level` `NOT_SET`. The SBOM properties and description name the three checks instead of a compliance engine.
- The Spanish admin interface ("DECLARACIÓN OFICIAL ACTIVA", "DEFENSA LEGAL UWG") became German with dashicons, and the statement no longer claims a block in the admin text or the plugin header: the block is registered on the server without an editor script.

### Documentation (2026-09-14)
- `README.md`, `README.de.md`, `README.es.md`, the 24 translated READMEs, `DEVELOPERS.md`, `CLAUDE.md`, `llms.txt`, `CONTRIBUTING.md`, `SECURITY.md` and `readme.txt` describe what the plugin ships: a statement generator fed by the operator's settings, three shortcodes and a server-side block, three pattern checks over up to 20 stored items, an admin screen, REST, WP-CLI and a hub adapter. Until now they promised a WCAG 2.2 AA/AAA audit of the whole site, tamper-evident cryptographic PDF reports, GoBD PDF/CSV exports, `wp bfsg-dash status|verify|export`, REST routes `/status` `/submit` `/export`, a `theme` attribute, two tables, an HMAC chain, formula-injection sanitising, two hooks and guaranteed compliance with BFSG, BGG, BITV 2.0 and EN 301 549; none of those exist. The translated READMEs are generated from `README.md` by the fleet script `wp-plugin-fleet-translations-sync.py`.
- The entries **[1.1.0]** and **[1.0.0]** below list features that never shipped as described; they are kept as the record of what was written at the time, each such line marked. The plugin header stays at 1.0.0.
- CI tested PHP 8.1 against a header requiring 8.2; it now runs the standalone suites on 8.2 and 8.3. `CLAUDE.md` and `CONTRIBUTING.md` said PHP 8.1+.

### Fixed
- **Fatal on the public accessibility statement (HIGH, fleet audit 2026-09-13 §6.2 #4):** `[wm_bfsg_anlage_3]` without attributes made WordPress pass an empty string to a callback typed `array $atts` → `TypeError` on the legal page. The callback is untyped, casts to array and runs `shortcode_atts()` with the seven documented attributes (`company`, `url`, `level`, `email`, `phone`, `officer`, `authority`), each sanitised; unknown attributes are dropped. Tests: group 6 of `tests/test-suite.php` (empty string, empty array, unknown attribute).
- **`[wm_bfsg_statement]` now exists:** the README documented that name while the code registered `wm_bfsg_anlage_3` and `wm_barrierefreiheitserklaerung`. Registered as an alias.
- Plugin Check (dist view, 2026-09-14): 8 → 2 errors, the two licence codes (header and readme) that wait for a decision. Translators comments and ordered placeholders on the dashboard-widget score and the header badge; `wp_strip_all_tags()` in the CLI statement output; the assessment date of the statement comes from `wp_date()` in the site's timezone (was `date()` in UTC; *both were the day of the page view, replaced by the saved date on 2026-09-15*); the `readme.txt` description is written in English and lists the shortcodes and commands that exist (`wp bfsg-audit scan` / `anlage3`; the README's `wp bfsg-dash status/verify/export` do not exist and go to the documentation pass).

### Design family (hackaton phase 3, 2026-09-14)
- The admin screen renders from the two WM Suite family sheets (`assets/css/wm-admin-tokens.css`, `assets/css/wm-admin-ui.css`, byte-identical copies of the hub's canonical files, pinned by `tests/test-ui-family.php`) instead of the plugin's own `admin-obsidian.css`; same handles as every WM plugin, versioned by file time. Native `.wrap`, family badge with the hub/standalone state, gettext menu titles, family menu icon, visible focus rings on every control. Applied with the admin UI migration script.

---

## [1.0.0] — 2026-08-25

### Initial Enterprise Release
- Core architectural implementation for Accessibility & Legal. — *no legal assessment is made by the code*
- WP-CLI management commands under `wp bfsg-dash`. — *never shipped under that name: the command is `wp bfsg-audit`*
- Revisionssicheres GoBD cryptographic ledger and telemetry hooks. — *never shipped*
- Strict compliance with BFSG 2025 Anlage 3, BGG § 12d, BITV 2.0 & EN 301 549 V3.2.1. — *no legal assessment is made by the code; the statement is the operator's self-assessment*

---

## Appendix — the entry written for 1.1.0 on 2026-08-29, never released

> **Not a released version — the plugin does not carry this number.** Measured 2026-09-17: `git tag` → 0, `gh release list` → 0, and `wm-bfsg-compliance-dashboard.php`, `readme.txt`, `SECURITY.md` carry **1.0.0**, which is the version this plugin ships as. The entry is kept verbatim as the record of what was written on 2026-08-29; only its heading changed, because Keep a Changelog reserves `## [x.y.z]` for releases and this was never one. Raising the plugin to 1.1.0 means raising `wm-bfsg-compliance-dashboard.php`, `readme.txt`, `SECURITY.md` in the same commit — the number lives in more than one file.

### Added
- **24 Official European Union Languages:** Full binary `.mo` and source `.po` translation packages in `languages/` (`bg_BG`, `cs_CZ`, `da_DK`, `de_DE`, `el_GR`, `en_GB`, `es_ES`, `et_EE`, `fi_FI`, `fr_FR`, `ga_IE`, `hr_HR`, `hu_HU`, `it_IT`, `lt_LT`, `lv_LV`, `mt_MT`, `nl_NL`, `pl_PL`, `pt_PT`, `ro_RO`, `sk_SK`, `sl_SI`, `sv_SE`). — *the catalogs carry Spanish and German msgids with broken encoding*
- **Developer Documentation Suite:** Created `DEVELOPERS.md` with complete architecture diagrams, hook references, database schema, and REST API guides. — *the manual described routes, tables and hooks that never shipped; rewritten from the code on 2026-09-14*
- **Decoupled REST API Controller:** Registered namespace `/wp-json/wm-bfsg-dash/v1` with strict nonce and permission validation. — *never shipped under that name: the namespace is `wm-bfsg-dashboard/v1`, three routes behind `manage_options`*
- **Zero-CDN Frontend Shortcode:** Implemented `[wm_bfsg_statement]` with inline SVG icons and CSS design tokens. — *never shipped as described: the name was registered as an alias on 2026-09-14; the statement uses inline styles, no icons or tokens*
- **CycloneDX v1.6 SBOM Engine:** Dynamic SHA-256 entry file integrity hashing and spoke adapter integration for **WM Suite Hub**. — *the adapter returns one component with a hash; no CycloneDX document or version*
- **Enterprise Showcase Assets:** High-fidelity 16:9 marketing banner and cockpit showcase mockups conditioned on real screenshot topology. — *the banner shows an audit score, a PDF export and "BITV 2.0 READY" the plugin does not have; the image is 1376×768, not 16:9; not shipped since 2026-09-15*
- **Clean Uninstallation Lifecycle:** Comprehensive `uninstall.php` handler for clean database and transient cache purging. — *two options; there is no table and no transient*

### Security
- **CWE-1236 Mitigation:** Active sanitization against formula injection attacks on all CSV exports and dynamic text inputs. — *never shipped: there is no CSV export*
- **Constant-Time Cryptography:** Enforced `hash_equals()` verification on all HMAC-SHA256 signature chains. — *never shipped: there is no hash chain*
- **SQL Parameterization:** Refactored all queries to use strict `$wpdb->prepare()` with parameterized placeholders. — *never applied: the plugin runs no direct database query*

### Quality & Tests
- Expanded standalone deterministic test suite (`tests/test-suite.php`) with 100% green pass rate and zero external dependencies. — *no run was recorded*
