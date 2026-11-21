# Architecture — WM BFSG 2025 Legal Compliance Dashboard & Anlage 3 Generator
`wm-bfsg-compliance-dashboard` · version `1.2.0` from the plugin header · WordPress 6.4+ · PHP 8.2+ · text domain `wm-bfsg-compliance-dashboard`

Every figure below comes from the code, not from any external document.

---

## What runs where

```
Anlage3Generator          builds the German accessibility statement from the operator's
                          settings (wm_bfsg_declaration_settings, eight keys)
                            └─ three shortcodes + Gutenberg block wm/bfsg-anlage-3 print it

BlockIntegrations         WordPress 6.5+ Block Bindings (wm-bfsg-dashboard/metrics) and
                          GenerateBlocks 2.x Dynamic Tag ({{wm_bfsg_dashboard_metric}})

AccessibilityAuditor      three pattern checks over the STORED CONTENT of up to 20
                          published items — not a rendered page, not a crawl

AuditPdfReport            summarises the last run.  It produces NO PDF.

admin screen (manage_options) · REST (manage_options) · wp bfsg-audit · hub adapter
```

The whole content of a legal page comes from one option; see the README's *What is stored*.

---

## Wiring — what to touch for a given change

| Change | Files, in order | Trap |
|---|---|---|
| Add a settings key | `AdminDashboard::settings()` defaults → the form → the generator → `uninstall.php` → README table → assertion | a default must be **honest**: never an invented conformity level, contact person, phone number or date |
| Add a check | `AccessibilityAuditor` → assertion + mutation | a check result is a finding, never a verdict; and "nothing checked" is `null` / `NOTHING_CHECKED`, never a score |
| Change the statement's legal text | **stop** | the heading's legal references, the Sachsen-Anhalt authority line and the enforcement e-mail are reserved for legal review by the repository owner |
| Change what the hub sees | `includes/Adapters/` | no content checked ⇒ hub `NEEDS_CONFIG`, REST and CLI failure — not a pass |

---

## Fragile spots

1. **The statement is a legal page of the operator.** Never fill a missing value with an invented one. An unset conformity level prints "noch nicht angegeben", and the day the page is viewed is **not** the date of an assessment. `tests/test-anlage3-claims.php` is the integration falsifier for this and runs only inside WordPress.
2. **No verdicts from the checks.** Three regex checks over stored content do not establish WCAG, BITV 2.0 or EN 301 549 conformance. No "RECHTSKONFORM", "Passed", "CERTIFICADO", seal or fingerprint wording, and no claim that the plugin runs tests it does not run.
3. **Missing data is not "all good".** No content checked ⇒ score `null`, state `NOTHING_CHECKED`, hub `NEEDS_CONFIG`, REST and CLI failure.
4. **`AuditPdfReport` produces no PDF**, despite the name. Anything that promises a downloadable report is describing a feature that does not exist.
5. **The auditor reads stored post content**, not rendered HTML — so it cannot see anything a theme, a block render or JavaScript adds. Its silence says nothing about the page a visitor gets.
6. **The default `supervisory_body` names the Sachsen-Anhalt authority.** On a site whose operator is established elsewhere it points complainants at a body that is not competent.
7. **Opt-in uninstallation.** `uninstall.php` preserves `wm_bfsg_declaration_settings` and `wm_bfsg_last_audit_results` unless `wm_bfsg_dashboard_delete_data_on_uninstall` is explicitly enabled (`'1'`).

---

<!-- wm-measured-inventory -->

## Measured inventory

Regenerated from the code, never hand-edited. Everything above this line is written by hand and stays.

```bash
python3 the fleet's architecture inventory tool wm-bfsg-compliance-dashboard --append-to docs/ARCHITECTURE.md
```

## Inventory by layer

**13 PHP files, 1634 lines** (excluding `tests/`, `vendor/`, `node_modules/`, `languages/`).

| Layer | Files | Lines |
|---|---:|---:|
| `(raíz)` | 2 | 105 |
| `build` | 1 | 18 |
| `includes/API` | 1 | 82 |
| `includes/Adapters` | 1 | 141 |
| `includes/Admin` | 1 | 515 |
| `includes/CLI` | 1 | 59 |
| `includes/Core` | 1 | 61 |
| `includes/Export` | 1 | 64 |
| `includes/Frontend` | 2 | 281 |
| `includes/Legal` | 1 | 147 |
| `includes/Scanner` | 1 | 161 |

---

## Classes

| Class | File |
|---|---|
| `AccessibilityAuditor` | [`includes/Scanner/AccessibilityAuditor.php`](../includes/Scanner/AccessibilityAuditor.php) |
| `AdminDashboard` | [`includes/Admin/AdminDashboard.php`](../includes/Admin/AdminDashboard.php) |
| `Anlage3Generator` | [`includes/Legal/Anlage3Generator.php`](../includes/Legal/Anlage3Generator.php) |
| `Anlage3Shortcode` | [`includes/Frontend/Anlage3Shortcode.php`](../includes/Frontend/Anlage3Shortcode.php) |
| `AuditPdfReport` | [`includes/Export/AuditPdfReport.php`](../includes/Export/AuditPdfReport.php) |
| `BfsgAuditCommand` | [`includes/CLI/BfsgAuditCommand.php`](../includes/CLI/BfsgAuditCommand.php) |
| `Bfsg_Dashboard_Spoke_Adapter` | [`includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php`](../includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php) |
| `BlockIntegrations` | [`includes/Frontend/BlockIntegrations.php`](../includes/Frontend/BlockIntegrations.php) |
| `Plugin` | [`includes/Core/Plugin.php`](../includes/Core/Plugin.php) |
| `RestController` | [`includes/API/RestController.php`](../includes/API/RestController.php) |

---

## Entry points

**REST routes:** `/audit-certificate`, `/preview-declaration`, `/run-audit`

**WP-CLI:** `bfsg-audit`

**Shortcodes:** `wm_barrierefreiheitserklaerung`, `wm_bfsg_anlage_3`, `wm_bfsg_statement`

**Gutenberg blocks:** `wm/bfsg-anlage-3`

---

## Persistence — who writes each key and who reads it

| Key | Written by | Read by |
|---|---|---|
| `wm_bfsg_dashboard_delete_data_on_uninstall` | `includes/Admin/AdminDashboard.php` | `includes/Admin/AdminDashboard.php`, `uninstall.php` |
| `wm_bfsg_declaration_settings` | `includes/Admin/AdminDashboard.php` | `includes/API/RestController.php`, `includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php`, `includes/Admin/AdminDashboard.php`, `includes/CLI/BfsgAuditCommand.php`, `includes/Frontend/Anlage3Shortcode.php` |

Every key has exactly one writing layer and at least one reader.


---

## Gates

- `tests/mutations.php`
- `tests/run-mutations.sh`
- `tests/test-anlage3-claims.php`
- `tests/test-pot-freshness.php`
- `tests/test-suite.php`
- `tests/test-ui-family.php`
- `.github/workflows/ci.yml`

What CI actually runs is in the workflow files above — a test file that no workflow invokes is not a gate.

---

## Drift detected against the documentation

Nothing measurable: versions agree and every class named in the documents exists.

