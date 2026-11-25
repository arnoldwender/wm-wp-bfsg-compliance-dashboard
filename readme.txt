=== WM BFSG 2025 Legal Compliance Dashboard & Anlage 3 Generator ===
Contributors: arnoldwender
Tags: bfsg, accessibility, accessibility statement, barrierefreiheit, wcag
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Builds the German accessibility statement from the operator's settings and runs three pattern checks on stored page content.

== Description ==

The plugin builds the German accessibility statement (Erklärung zur Barrierefreiheit) from what the operator enters: the level of conformity as the operator's own self-assessment, the date of that assessment, a feedback contact and the enforcement body. Without a stated level or date the statement says that they are not yet stated; a contact person or phone number that was not entered is left out. The statement says that it rests on a self-assessment and that no external certification underlies it.

It also runs three pattern checks on the stored content of up to 20 published pages, posts and products: images without an alt attribute, links without text and skipped heading levels. These checks do not look at the rendered site and are not a test against WCAG 2.2 or EN 301 549. The plugin does not assess whether or how the BFSG applies to an offer.

* **Gutenberg block & shortcodes:** block `wm/bfsg-anlage-3` with InspectorControls and live ServerSideRender preview, plus `[wm_barrierefreiheitserklaerung]`, `[wm_bfsg_anlage_3]` and `[wm_bfsg_statement]`; attributes `company`, `url`, `level`, `email`, `phone`, `officer` and `authority` override the saved settings on that page.
* **Block Bindings & GenerateBlocks 2.x:** registers WordPress 6.5+ Block Bindings source `wm-bfsg-dashboard/metrics` and GenerateBlocks 2.x Dynamic Tag `{{wm_bfsg_dashboard_metric}}`.
* **Developer hooks:** filters `wm_bfsg_dashboard_declaration_html` and `wm_bfsg_dashboard_audit_report`, actions `wm_bfsg_dashboard_ready` and `wm_bfsg_dashboard_audit_completed`.
* **Admin screen:** menu entry "BFSG Dashboard" with an overview, statement settings (including opt-in data removal on uninstall) and preview, the report of the last check run and a list of terms; a dashboard widget shows the stored run without running anything.
* **WP-CLI:** `wp bfsg-audit scan` runs the checks and lists the findings (error exit when nothing was found to check); `wp bfsg-audit anlage3` prints the statement from the saved settings as text.
* **REST:** `wm-bfsg-dashboard/v1` with `/run-audit`, `/preview-declaration` and `/audit-certificate`, all for administrators.
* **Suite Hub:** registers as a spoke of WM Suite Hub when the hub is active.

No outbound requests; every script, stylesheet and 28-locale translation catalog ships inside the plugin.

**No Support & Zero Liability ("AS IS"):** This plugin is provided free of charge as personal open-source software with **zero support** and **no private support**. Do not contact the author via email, `arnoldwender.com`, or direct messages. Report reproducible defects exclusively at `https://github.com/arnoldwender/wm-wp-bfsg-compliance-dashboard/issues` (no SLA or response guarantee). Under GPLv2+ Sections 11-12 it is distributed **"AS IS"** without warranty, legal advice, or author liability.

== Installation ==

1. Upload the `wm-bfsg-compliance-dashboard` folder to `/wp-content/plugins/` and activate it.
2. Open "BFSG Dashboard", tab "Erklärung", and enter the company, website, the date of your self-assessment, the level your own assessment of the site supports, the feedback e-mail and, if wanted, a contact person and phone number.
3. Create a page for the statement, insert the "Erklärung zur Barrierefreiheit (BFSG)" block or `[wm_barrierefreiheitserklaerung]` on it and link it from the footer next to the imprint and the privacy policy.

== Frequently Asked Questions ==

= Is technical or private support provided? =

No. This plugin comes with zero support (`0` support). Private inquiries sent via email, `arnoldwender.com`, or social/chat channels are not answered. Reproducible bugs belong solely on GitHub Issues (`https://github.com/arnoldwender/wm-wp-bfsg-compliance-dashboard/issues`) with no guarantee of reply or resolution. The software is provided **"AS IS"** at your sole risk with zero warranty or liability (see `SUPPORT.md` in the repository).

== Screenshots ==

1. BFSG statement builder cockpit (Anlage 3) combining operator configuration and stored check metrics
2. Gutenberg Block Editor with wm/bfsg-anlage-3 inserted and sidebar inspector open
3. Published German accessibility statement rendered by [wm_bfsg_anlage_3] / block SSR
4. Dashboard widget summarizing stored accessibility statement configuration

== Changelog ==


= 1.2.2 =
* Fixed: Screenshot 1 showed the WM Suite menu from before WM Suite Hub 1.2.3, with entries the hub no longer has («Marketplace (Lösungspakete)», «Merkle-Audit-Protokoll», «CRA- & SBOM-Zentrale»); it is retaken with the current menu. The three finding counts of the report («%d Bild ohne alt-Attribut», «%d Link ohne Text», «%d übersprungene Überschriftenebene») repeated one form for several numbers in Arabic, Croatian, Czech, Irish, Latvian, Lithuanian, Maltese, Polish, Romanian, Slovak and Slovenian, for example Polish «obrazy/obrazów» for both 2 and 5 and the Arabic plural for one; each number now gets the form its language uses (Polish «2 obrazy», «5 obrazów»). The plugin's code is unchanged.

= 1.2.1 =
* The bundled family stylesheet is identical to WM Suite Hub 1.2.5 again: it starts the admin header below WordPress's help toggle instead of shortening it. This plugin's own screens register no help tab, so they change only where another plugin adds one.

= 1.2.0 =
* The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
* The `.wordpress-org/` banners show the current version.
* Removed: The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

= 1.1.3 =
* Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.
* Added root `SUPPORT.md`, GitHub Issue templates, explicit zero-support & "AS IS" liability notices in READMEs and `readme.txt`, and normalized canonical GPLv2+ `arnoldwender.com` / `arnoldwender/wm-wp-bfsg-compliance-dashboard` links.
* Updated `.wordpress-org/` visual banners to personal `ARNOLD WENDER · OPEN SOURCE` branding.

= 1.1.2 =
* Added live `.wordpress-org/` visual kit assets (banner, icons, and live WordPress screenshots) and updated documentation links for v1.1.2.

= 1.1.1 =
* Point Plugin URI to the official plugin page.

= 1.1.0 =
* Added Gutenberg block editor integration (`blocks/anlage-3/block.json`, `build/index.js`, `wm-bfsg-dashboard-blocks-editor`) with InspectorControls and ServerSideRender preview so `wm/bfsg-anlage-3` is available in the Block Inserter.
* Added opt-in uninstallation (`wm_bfsg_dashboard_delete_data_on_uninstall`, default off) preserving operator statement settings and audit reports unless deletion on uninstall is explicitly enabled.
* Added developer filters (`wm_bfsg_dashboard_declaration_html`, `wm_bfsg_dashboard_audit_report`), actions (`wm_bfsg_dashboard_ready`, `wm_bfsg_dashboard_audit_completed`), WordPress 6.5+ Block Bindings source (`wm-bfsg-dashboard/metrics`) and GenerateBlocks 2.x Dynamic Tag (`{{wm_bfsg_dashboard_metric}}`).
* Added complete translations across all 28 `.po`/`.mo` catalogs and 28 JED `.json` block-editor catalogs, CycloneDX 1.6 `bom.json` and WordPress Playground blueprint.
* Hardened admin preview rendering with `wp_kses_post()` and aligned statement date, sticky-post handling, German finding labels, family admin stylesheets, REST and WP-CLI reporting.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.2 =
Screenshot 1 retaken and plural forms fixed in 11 languages. No code, data or settings change.

= 1.2.1 =
Stylesheet update only (the family sheet of WM Suite Hub 1.2.5). No data or settings change.

= 1.2.0 =
Hub users: update WM Suite Hub to 1.2.0 together with this plugin. The hub contract moved to a new PHP namespace; next to an older hub the plugin keeps working, but it is not listed in the cockpit and its admin page has no menu entry until the hub is updated.
