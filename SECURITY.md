# Security Policy — WM BFSG 2025 Legal Compliance Dashboard & Anlage 3 Generator

## Supported Versions

| Version | Supported |
| --- | --- |
| `1.2.x` (`1.2.2`) | Current release branch |

---

## Reporting a Vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities, and **do not** send private inquiries or support emails to `arnoldwender.com`, email addresses, or social/direct messages (private channels are unmonitored for open-source plugins).

Submit security reports **exclusively** via GitHub Private Vulnerability Reporting:

- **Security Advisories:** [github.com/arnoldwender/wm-wp-bfsg-compliance-dashboard/security/advisories/new](https://github.com/arnoldwender/wm-wp-bfsg-compliance-dashboard/security/advisories/new)

> **Best-Effort Only / Zero Warranty / Zero Support:** This open-source repository carries **zero technical or user support** ([SUPPORT.md](SUPPORT.md)) and **zero response-time SLA**. Advisory reports may be reviewed at the author's sole discretion without obligation, timeline, or liability ("AS IS" under [LICENSE](LICENSE)).

### What to include

1. Reproduction steps or a proof of concept.
2. The affected component: shortcode or block output, admin screen and settings form, REST routes under `wm-bfsg-dashboard/v1`, WP-CLI command, hub adapter.
3. The impact you observed (for example stored XSS through settings, missing capability check, CSRF).

### Attack surface

- **Public:** the statement printed by the shortcodes and the Gutenberg block; every interpolated value is escaped.
- **Administrators only (`manage_options`):** the settings form (nonce `wm_bfsg_decl_action`), the three REST routes (REST nonce), the dashboard widget.
- **No outbound requests, no file writes, no custom tables.** Stored data: WordPress options (`wm_bfsg_declaration_settings`, `wm_bfsg_last_audit_results`, `wm_bfsg_dashboard_delete_data_on_uninstall`), preserved on uninstall unless opt-in deletion is enabled.

Reports are acknowledged as soon as they are read; there is no contractual response time.
