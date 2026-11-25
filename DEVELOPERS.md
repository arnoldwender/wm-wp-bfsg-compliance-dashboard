# WM BFSG Compliance Dashboard — Developer Manual

> Everything below is measured against the code in this repository (v1.2.2).

---

## 1. Layout

| Path | Role |
| --- | --- |
| `wm-bfsg-compliance-dashboard.php` | Header (`1.2.2`), constants (`WM_BFSG_DASHBOARD_VERSION` / `_FILE` / `_PATH` / `_URL`), PSR-4 autoloader for `WMCyber\BFSG\Dashboard\` → `includes/`, hub spoke registration on `wm_register_suite_module`, `plugins_loaded` boot. |
| `includes/Core/Plugin.php` | Text domain on `init`, shortcodes, block and ecosystem integrations, `AdminDashboard` in the admin, REST controller on `rest_api_init`, WP-CLI command `bfsg-audit`, `wm_bfsg_dashboard_ready` action. |
| `includes/Legal/Anlage3Generator.php` | `generate_declaration()`, `display_date()`, `COMPLIANCE_LEVELS`, `wm_bfsg_dashboard_declaration_html` filter. |
| `includes/Frontend/Anlage3Shortcode.php` | Three shortcodes (`wm_barrierefreiheitserklaerung`, `wm_bfsg_anlage_3`, `wm_bfsg_statement`) and Gutenberg block `wm/bfsg-anlage-3` (`wm-bfsg-dashboard-blocks-editor`). |
| `includes/Frontend/BlockIntegrations.php` | WordPress 6.5+ Block Bindings source `wm-bfsg-dashboard/metrics` and GenerateBlocks 2.x Dynamic Tag `{{wm_bfsg_dashboard_metric}}`. |
| `includes/Scanner/AccessibilityAuditor.php` | `run_audit()`, `stored_results()`, `SAMPLE_LIMIT` (20), `wm_bfsg_dashboard_audit_completed` action. |
| `includes/Export/AuditPdfReport.php` | `generate_certificate_data()` — summary array filtered by `wm_bfsg_dashboard_audit_report`; no PDF file is produced. |
| `includes/API/RestController.php` | `/run-audit`, `/preview-declaration`, `/audit-certificate` under `wm-bfsg-dashboard/v1`. |
| `includes/CLI/BfsgAuditCommand.php` | `wp bfsg-audit scan`, `wp bfsg-audit anlage3`. |
| `includes/Admin/AdminDashboard.php` | Menu, family sheet enqueue, `register_setting`, settings save, four-tab screen, dashboard widget. |
| `includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php` | WM Suite Hub contract; returns early when the hub's interface does not exist. |
| `blocks/anlage-3/block.json` | Gutenberg block metadata for `wm/bfsg-anlage-3` (`editorScript`: `wm-bfsg-dashboard-blocks-editor`). |
| `blocks-src/index.js`, `build/` | Block editor source (`blocks-src/`, excluded from ZIP) and compiled editor script + asset manifest (`build/index.js`, `build/index.asset.php`). |
| `assets/css/` | `wm-admin-tokens.css`, `wm-admin-ui.css` (copies of the hub's canonical sheets, pinned by `tests/test-ui-family.php`). |
| `languages/` | `.pot` template, 28 `.po` / `.mo` pairs and 28 JED `.json` block-editor translation catalogs. |
| `bom.json` | CycloneDX 1.6 Software Bill of Materials (`GPL-2.0-or-later`). |
| `uninstall.php` | Preserves options by default; deletes `wm_bfsg_last_audit_results`, `wm_bfsg_declaration_settings` and `wm_bfsg_dashboard_delete_data_on_uninstall` only when `wm_bfsg_dashboard_delete_data_on_uninstall` is enabled. |

---

## 2. Statement generator

`Anlage3Generator::generate_declaration( array $overrides = [] ): string`

| Key | Default when missing |
| --- | --- |
| `company_name` | `get_bloginfo( 'name' )` |
| `website_url` | `home_url()` |
| `assessment_date` | `''` — printed as "Das Erstellungsdatum ist noch nicht angegeben." and left out of the closing line. A `Y-m-d` value is shown as `d.m.Y` (`display_date()`), an impossible date as not stated, any other text as given. |
| `compliance_level` | `''` — any value outside `COMPLIANCE_LEVELS` (`vollständig konform`, `größtenteils konform`, `nicht konform`) is printed as "Der Stand der Vereinbarkeit ist noch nicht angegeben." |
| `feedback_email` | `get_option( 'admin_email' )` |
| `feedback_phone` | `''` — the line is left out |
| `officer_name` | `''` — the line is left out |
| `supervisory_body` | `Landesfachstelle für Barrierefreiheit Sachsen-Anhalt (Halle / Magdeburg)` |

Output: one `div#wm-barrierefreiheitserklaerung` with inline styles, an `h2` heading, an opening paragraph with a real link to the website (built outside the translatable string), four `h3` sections (1. Stand der Vereinbarkeit, 2. Erstellung, 3. Feedback und Kontaktangaben, 4. Durchsetzungsverfahren) and a closing line stating the self-assessment and the absence of an external certification. Every interpolated value is escaped, and the final HTML passes through `apply_filters( 'wm_bfsg_dashboard_declaration_html', $html, $context )`.

---

## 3. Shortcodes, Gutenberg block & dynamic bindings

- `[wm_barrierefreiheitserklaerung]`, `[wm_bfsg_anlage_3]`, `[wm_bfsg_statement]` → `Anlage3Shortcode::render_shortcode( $atts )`. The parameter is untyped (WordPress passes `''` without attributes) and cast to array before `shortcode_atts()`.
- Attributes: `company`, `url`, `level`, `email`, `phone`, `officer`, `authority`, each through `sanitize_text_field()`. Precedence per value: attribute, then the saved setting, then the generator's default (level, phone and contact person stay empty). The date comes from the saved `assessment_date` only.
- Gutenberg block `wm/bfsg-anlage-3`: registered from `blocks/anlage-3/block.json` on `init` with editor script `wm-bfsg-dashboard-blocks-editor` (`build/index.js` + `build/index.asset.php`), `wp_set_script_translations()`, `InspectorControls` for optional attribute overrides, and `ServerSideRender` preview.
- WordPress 6.5+ Block Bindings: source `wm-bfsg-dashboard/metrics` (`BlockIntegrations::get_block_binding_value()`), accepting `key` values `compliance_level`, `assessment_date`, `company_name`, `feedback_email`, `supervisory_body`, `pages_checked`, `pages_with_findings`, `score` and `last_run`.
- GenerateBlocks 2.x Dynamic Tag: `{{wm_bfsg_dashboard_metric}}` (`GenerateBlocks_Register_Dynamic_Tag` + `generateblocks_dynamic_tag_output` filter) exposing the same honest stored metrics without inventing missing values.

---

## 4. Developer hooks

| Hook | Type | Arguments | Description |
| --- | --- | --- | --- |
| `wm_bfsg_dashboard_ready` | Action | `Plugin $plugin` | Fires after core services, shortcodes, block and integrations have booted. |
| `wm_bfsg_dashboard_audit_completed` | Action | `array $results` | Fires after `AccessibilityAuditor::run_audit()` stores `wm_bfsg_last_audit_results`. |
| `wm_bfsg_dashboard_declaration_html` | Filter | `string $html, array $context` | Filters the generated accessibility statement HTML before output. |
| `wm_bfsg_dashboard_audit_report` | Filter | `array $report, array $audit_results` | Filters the summary array returned by `AuditPdfReport::generate_certificate_data()`. |

---

## 5. Pattern checks

`AccessibilityAuditor::run_audit(): array`

- Query: `WP_Query( post_type [page, post, product], post_status publish, posts_per_page SAMPLE_LIMIT (20), orderby date, order DESC, ignore_sticky_posts true, no_found_rows true )`.
- Per item, on `post_content`:

| Check | Pattern | Penalty |
| --- | --- | --- |
| Image without `alt` (WCAG 1.1.1) | `<img(?![^>]*\salt\s*=)[^>]*>` — `alt=""` counts as present, `data-alt` does not, spaces around `=` are allowed | 10 each, max 30 |
| Link without text (WCAG 2.4.4) | `<a\b(?![^>]*\s(?:aria-label\|aria-labelledby\|title)\s*=\s*["'][^"']*\S)[^>]*>(?:\s\|&nbsp;)*</a>` — a non-empty `aria-label`, `aria-labelledby` or `title` names the link | 10 each, max 20 |
| Skipped heading level (WCAG 1.3.1) | a `<hN>` more than one level below the previous heading | 10 each, max 20 |

- Item: `{id, title, url, score, issues[], status}` with status `findings` or `no_findings`; `issues` are German strings through `_n()`.
- Result: `score` and `overall_score` (mean, `null` when no item), `total_pages`, `pages_with_findings`, `sample_limit`, `compliance_state` (`NOTHING_CHECKED`, `NO_FINDINGS_IN_THREE_CHECKS`, `FINDINGS`), `audited_at` (`current_time( 'mysql' )`), `findings`. Stored with `update_option( AccessibilityAuditor::OPTION, …, false )` (`wm_bfsg_last_audit_results`).
- Reading: every consumer goes through `AccessibilityAuditor::stored_results()`, which returns `[]` when no run is stored or when the stored run lacks `pages_with_findings` or `sample_limit`.

---

## 6. Report summary

`AuditPdfReport::generate_certificate_data( array $audit_results ): array` returns `report_id` (`BFSG-` + 16 hex characters of a SHA-256 over site URL, time, pages and flagged pages — an identifier, recomputable by anyone), `generator`, `site_url`, `timestamp`, `score`, `pages_checked`, `pages_with_findings`, `checks` (German description of the three checks) and `result` (`Keine Inhalte geprüft`, `Keine Befunde in den drei Musterprüfungen` or `Befunde auf N von M geprüften Seiten`), filtered through `wm_bfsg_dashboard_audit_report`. There is no legal status and no seal.

---

## 7. REST

| Method | Route | Permission | Response |
| --- | --- | --- | --- |
| `POST` | `/wp-json/wm-bfsg-dashboard/v1/run-audit` | `manage_options` | `200 { success: total_pages > 0, data: <run_audit> }` |
| `GET` | `/wp-json/wm-bfsg-dashboard/v1/preview-declaration` | `manage_options` | `200 { success: true, html }` — generated from `wm_bfsg_declaration_settings` |
| `GET` | `/wp-json/wm-bfsg-dashboard/v1/audit-certificate` | `manage_options` | `200 { success: <stored run checked content>, data: <report summary> }` — reads the stored run, never runs one |

---

## 8. WP-CLI

```bash
wp bfsg-audit scan      # runs the checks; error exit when nothing was found to check; one line per item with findings
wp bfsg-audit anlage3   # statement from the saved settings, tags stripped
```

---

## 9. Admin & Uninstallation

- **Menu:** `add_menu_page( 'wm-bfsg-dashboard', 'BFSG Dashboard', manage_options, family icon, 82 )`.
- **Assets:** family sheets on hooks containing `wm-bfsg-dashboard` and on `index.php` (widget), versioned by file time.
- **Settings:** `wm_bfsg_declaration_settings` and `wm_bfsg_dashboard_delete_data_on_uninstall` (`register_setting` under `wm_bfsg_dashboard_settings`), saved on `admin_init` after `wp_verify_nonce( …, 'wm_bfsg_decl_action' )` and `manage_options`; input unslashed and sanitised; `compliance_level` kept only when it is one of `COMPLIANCE_LEVELS`; `assessment_date` kept only as a valid `Y-m-d`. `AdminDashboard::settings()` merges the saved values over defaults that invent nothing.
- **Screen:** tabs `overview`, `anlage3` (settings form, opt-in uninstall checkbox, `wp_kses_post` preview), `certificate` (report summary and findings table of the stored run), `glossary`.
- **Uninstallation (`uninstall.php`):** preserves `wm_bfsg_declaration_settings` and `wm_bfsg_last_audit_results` unless `wm_bfsg_dashboard_delete_data_on_uninstall` is explicitly enabled (`'1'`).

---

## 10. WM Suite Hub spoke

`Bfsg_Dashboard_Spoke_Adapter`: slug `wm-bfsg-compliance-dashboard`, version from `WM_BFSG_DASHBOARD_VERSION`, health `NEEDS_CONFIG` while `overall_score` is missing or `null`, `HEALTHY` from 80, `DEGRADED` below; SBOM component (`GPL-2.0-or-later`) with SHA-256 of the main file; settings URL `admin.php?page=wm-bfsg-dashboard`; telemetry `pattern_check_score`, `pages_checked`, `pages_with_findings`, `check_state`, `declared_level`, `last_check_run`; actions `self_test` and `run_audit`.

---

## 11. Known limitations

1. The enforcement block prints the Sachsen-Anhalt authority line and e-mail address under `supervisory_body`, and the heading cites "Anlage 3 BFSG & Art. 246 EGBGB".
2. The opening sentence names BFSG, EN 301 549 V3.2.1 and WCAG 2.2 Level AA as the goal on every site.
3. The checks read stored `post_content` only: no rendered output, shortcodes, PHP-rendered blocks or theme; image-only links are not flagged; the theme's `<h1>` is unknown to the heading check.
4. The statement uses inline styles with fixed colours.

---

## 12. Tests

```bash
php tests/test-suite.php         # checks, statement, report, adapter, shortcode, block, hooks, uninstall, admin wording, REST
php tests/test-pot-freshness.php # .pot covers every translatable string in PHP, blocks-src and block.json
bash tests/run-mutations.sh      # each mutation undoes one fix in a copy; the suite must turn red
wp eval-file wp-content/plugins/wm-bfsg-compliance-dashboard/tests/test-anlage3-claims.php --allow-root   # inside WordPress
```

---

## 13. Security disclosure

See [SECURITY.md](SECURITY.md).
