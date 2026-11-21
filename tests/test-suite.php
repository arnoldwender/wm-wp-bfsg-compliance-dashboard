<?php
/**
 * Standalone Automated Test Runner & Unit Suite for wm-bfsg-compliance-dashboard.
 *
 * Runs without external WordPress/MySQL dependency by providing a deterministic
 * mock environment for WP_Query, options, hooks, textdomain, and Spoke interfaces.
 *
 * @package WMCyber\BFSG\Dashboard
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// A PHP warning fails the run: a render that reads a missing key still passes its string checks otherwise.
$GLOBALS['bfsg_php_warnings'] = [];
set_error_handler(static function (int $no, string $msg, string $file, int $line): bool {
    $GLOBALS['bfsg_php_warnings'][] = basename($file) . ':' . $line . ' ' . $msg;
    return false;
});

// Setup mock WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (!defined('WM_BFSG_DASHBOARD_VERSION')) {
    define('WM_BFSG_DASHBOARD_VERSION', '1.2.2');
}
if (!defined('WM_BFSG_DASHBOARD_FILE')) {
    define('WM_BFSG_DASHBOARD_FILE', dirname(__DIR__) . '/wm-bfsg-compliance-dashboard.php');
}
if (!defined('WM_BFSG_DASHBOARD_PATH')) {
    define('WM_BFSG_DASHBOARD_PATH', dirname(__DIR__) . '/');
}
if (!defined('WM_BFSG_DASHBOARD_URL')) {
    define('WM_BFSG_DASHBOARD_URL', 'https://demo.example.com/wp-content/plugins/wm-bfsg-compliance-dashboard/');
}

// Global Mocks & In-Memory Store
global $mock_options, $mock_filters, $mock_actions, $mock_posts;
$mock_options = [];
$mock_filters = [];
$mock_actions = [];
$mock_posts   = [];

// Mock WP core functions
if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        global $mock_actions;
        $mock_actions[$tag][] = $callback;
    }
}
if (!function_exists('do_action')) {
    function do_action(string $tag, ...$args): void {
        global $mock_actions;
        if (isset($mock_actions[$tag])) {
            foreach ($mock_actions[$tag] as $callback) {
                call_user_func($callback, ...$args);
            }
        }
    }
}
if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        global $mock_filters;
        $mock_filters[$tag][] = $callback;
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters(string $tag, $value, ...$args) {
        global $mock_filters;
        if (isset($mock_filters[$tag])) {
            foreach ($mock_filters[$tag] as $callback) {
                $value = call_user_func($callback, $value, ...$args);
            }
        }
        return $value;
    }
}
if (!function_exists('get_option')) {
    function get_option(string $option, $default = false) {
        global $mock_options;
        return $mock_options[$option] ?? $default;
    }
}
// The statement dates its own assessment with wp_date() since 2026-09-14 (Plugin Check refuses date()).
if (!function_exists('wp_date')) {
    function wp_date(string $format, ?int $timestamp = null): string {
        return gmdate($format, $timestamp ?? time());
    }
}
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string {
        $text = strip_tags($text);
        return $remove_breaks ? preg_replace('/[\r\n\t ]+/', ' ', $text) : $text;
    }
}
if (!function_exists('update_option')) {
    function update_option(string $option, $value, $autoload = null): bool {
        global $mock_options;
        $mock_options[$option] = $value;
        return true;
    }
}
if (!function_exists('delete_option')) {
    function delete_option(string $option): bool {
        global $mock_options;
        unset($mock_options[$option]);
        return true;
    }
}
if (!function_exists('get_site_option')) {
    function get_site_option(string $option, $default = false) {
        global $mock_options;
        return $mock_options['site_' . $option] ?? $default;
    }
}
if (!function_exists('delete_site_option')) {
    function delete_site_option(string $option): bool {
        global $mock_options;
        unset($mock_options['site_' . $option]);
        return true;
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key): string {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)) ?? '';
    }
}
if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $data): string {
        return preg_replace('#<script\b[^>]*>.*?</script>#is', '', $data) ?? '';
    }
}
if (!function_exists('checked')) {
    function checked($checked, $current = true, bool $display = true): string {
        $res = (string) $checked === (string) $current ? ' checked="checked"' : '';
        if ($display) {
            echo $res;
        }
        return $res;
    }
}
if (!function_exists('register_setting')) {
    function register_setting(string $option_group, string $option_name, array $args = []): void {
        $GLOBALS['mock_registered_settings'][$option_name] = ['group' => $option_group, 'args' => $args];
    }
}
if (!function_exists('wp_register_script')) {
    function wp_register_script(string $handle, string $src, array $deps = [], $ver = false, $in_footer = false): bool {
        $GLOBALS['mock_registered_scripts'][$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver];
        return true;
    }
}
if (!function_exists('wp_set_script_translations')) {
    function wp_set_script_translations(string $handle, string $domain = 'default', string $path = ''): bool {
        $GLOBALS['mock_script_translations'][$handle] = ['domain' => $domain, 'path' => $path];
        return true;
    }
}
if (!function_exists('register_block_type')) {
    function register_block_type(string $block_type, array $args = []): object {
        $GLOBALS['mock_registered_blocks'][$block_type] = $args;
        return (object) ['name' => $block_type, 'args' => $args];
    }
}
if (!function_exists('register_block_bindings_source')) {
    function register_block_bindings_source(string $name, array $args): object {
        $GLOBALS['mock_block_bindings'][$name] = $args;
        return (object) ['name' => $name, 'args' => $args];
    }
}
if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return false;
    }
}
if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string {
        return 'wm-bfsg-compliance-dashboard/' . basename($file);
    }
}
if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, $deprecated = false, $plugin_rel_path = false): bool {
        return true;
    }
}
if (!class_exists('GenerateBlocks_Register_Dynamic_Tag')) {
    class GenerateBlocks_Register_Dynamic_Tag {
        public static array $registered = [];
        public function __construct(array $args) {
            self::$registered[$args['tag'] ?? ''] = $args;
        }
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return is_string($str) ? trim(strip_tags($str)) : '';
    }
}
if (!function_exists('sanitize_email')) {
    function sanitize_email($email): string {
        return filter_var((string)$email, FILTER_SANITIZE_EMAIL) ?: '';
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}
if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'https://demo.example.com' . ($path ? '/' . ltrim($path, '/') : '');
    }
}
if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string {
        return 'https://demo.example.com/wp-admin/' . ltrim($path, '/');
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = 'name'): string {
        return 'WM Suite BFSG Compliance Testbed';
    }
}
if (!function_exists('get_permalink')) {
    function get_permalink($post_id = 0): string {
        return "https://demo.example.com/?p={$post_id}";
    }
}
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}
if (!function_exists('_n')) {
    function _n(string $single, string $plural, int $number, string $domain = 'default'): string {
        return 1 === $number ? $single : $plural;
    }
}
if (!function_exists('_e')) {
    function _e(string $text, string $domain = 'default'): void {
        echo $text;
    }
}
if (!function_exists('current_time')) {
    function current_time(string $type, $gmt = 0) {
        return $type === 'mysql' ? date('Y-m-d H:i:s') : time();
    }
}
if (!function_exists('add_shortcode')) {
    function add_shortcode(string $tag, callable $callback): void {
        global $mock_actions;
        $mock_actions['shortcode_' . $tag][] = $callback;
    }
}
if (!function_exists('shortcode_atts')) {
    function shortcode_atts(array $pairs, array $atts, string $shortcode = ''): array {
        $out = [];
        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }
        return $out;
    }
}

// Mock WP_Post & WP_Query
class WP_Post {
    public int $ID;
    public string $post_title;
    public string $post_content;
    public string $post_type;
    public string $post_status;

    public function __construct(int $id, string $title, string $content, string $type = 'page', string $status = 'publish') {
        $this->ID           = $id;
        $this->post_title   = $title;
        $this->post_content = $content;
        $this->post_type    = $type;
        $this->post_status  = $status;
    }
}

class WP_Query {
    public array $posts = [];
    public static array $last_args = [];

    public function __construct(array $args = []) {
        global $mock_posts;
        self::$last_args = $args;
        $this->posts = $mock_posts;
    }
}

// Mock Suite Hub Interface
if (!interface_exists('ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface')) {
    eval('namespace ArnoldWender\SuiteHub\Contracts {
        interface WM_Plugin_Module_Interface {
            public function get_module_slug(): string;
            public function get_name(): string;
            public function get_version(): string;
            public function get_description(): string;
            public function get_health_status(): string;
            public function get_sbom_data(): array;
            public function get_dependencies(): array;
            public function get_settings_url(): ?string;
            public function get_telemetry_metrics(): array;
            public function execute_action(string $action, array $params = []): array;
        }
    }');
}

// Require Plugin Core Files
require_once ABSPATH . 'includes/Scanner/AccessibilityAuditor.php';
require_once ABSPATH . 'includes/Legal/Anlage3Generator.php';
require_once ABSPATH . 'includes/Export/AuditPdfReport.php';
require_once ABSPATH . 'includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php';
require_once ABSPATH . 'includes/Frontend/Anlage3Shortcode.php';

use WMCyber\BFSG\Dashboard\Scanner\AccessibilityAuditor;
use WMCyber\BFSG\Dashboard\Legal\Anlage3Generator;
use WMCyber\BFSG\Dashboard\Export\AuditPdfReport;
use WMCyber\BFSG\Dashboard\Adapters\Bfsg_Dashboard_Spoke_Adapter;
use WMCyber\BFSG\Dashboard\Frontend\Anlage3Shortcode;

// Test Execution Harness
$passed_count = 0;
$failed_count = 0;

function assert_test(string $name, bool $condition, string $failure_details = ''): void {
    global $passed_count, $failed_count;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed_count++;
    } else {
        echo "  [FAIL] {$name}: {$failure_details}\n";
        $failed_count++;
    }
}

echo "==============================================================================\n";
echo "  WM BFSG 2025 COMPLIANCE DASHBOARD — ZERO-DEPENDENCY UNIT TEST SUITE\n";
echo "==============================================================================\n\n";

// ------------------------------------------------------------
// Test 1: Accessibility Auditor on Clean / Compliant Content
// ------------------------------------------------------------
echo "[TEST GROUP 1] Scanner Engine on Compliant Content\n";
global $mock_posts;
$mock_posts = [
    new WP_Post(
        1,
        'Startseite Barrierefrei',
        '<h1>Hauptüberschrift</h1><p>Willkommen auf unserer Seite.</p><h2>Unterabschnitt</h2><p><img src="hero.webp" alt="WM Suite Firmengebäude in Halle"></p><p><a href="/kontakt">Hier Kontakt aufnehmen</a></p>'
    ),
    new WP_Post(
        2,
        'Leistungen',
        '<h1>Leistungen</h1><p>Hier finden Sie unsere Services.</p><h2>Webentwicklung</h2><p><img src="code.webp" alt="Entwickler bei der barrierefreien Programmierung"></p>'
    ),
];

$auditor = new AccessibilityAuditor();
$results = $auditor->run_audit();

assert_test(
    'Auditor scores 100% on fully accessible semantic content',
    $results['overall_score'] === 100 && $results['score'] === 100 && $results['total_pages'] === 2,
    "Expected score 100 across 2 pages, got: " . var_export($results, true)
);

assert_test(
    'Auditor tags a page without findings as no_findings, not as compliant',
    empty($results['findings'][0]['issues']) && $results['findings'][0]['status'] === 'no_findings',
    "Findings: " . var_export($results['findings'][0], true)
);

// ------------------------------------------------------------
// Test 2: Scanner Detection of WCAG Violations (Alt, Link, Hierarchy)
// ------------------------------------------------------------
echo "\n[TEST GROUP 2] Scanner Detection of Specific WCAG Violations\n";
$mock_posts = [
    new WP_Post(
        3,
        'Seite mit Mängeln',
        '<h1>Überschrift 1</h1><h3>Überschrift 3 ohne H2 (Skipped hierarchy)</h3><p><img src="unnamed.png"></p><p><img src="test.jpg"></p><p><a href="/leer">   </a></p>'
    ),
];

$results_broken = $auditor->run_audit();
$issues = $results_broken['findings'][0]['issues'];

$has_alt_issue = false;
$has_empty_link = false;
$has_heading_issue = false;

foreach ($issues as $issue) {
    if (str_contains($issue, 'WCAG 1.1.1')) {
        $has_alt_issue = true;
    }
    if (str_contains($issue, 'WCAG 2.4.4')) {
        $has_empty_link = true;
    }
    if (str_contains($issue, 'WCAG 1.3.1')) {
        $has_heading_issue = true;
    }
}

assert_test(
    'Auditor flags missing alt attributes (WCAG 1.1.1)',
    $has_alt_issue,
    "Issues found: " . implode(' | ', $issues)
);

assert_test(
    'Auditor flags empty links lacking accessible name (WCAG 2.4.4)',
    $has_empty_link,
    "Issues found: " . implode(' | ', $issues)
);

assert_test(
    'Auditor flags skipped heading hierarchy levels (WCAG 1.3.1)',
    $has_heading_issue,
    "Issues found: " . implode(' | ', $issues)
);

assert_test(
    'Auditor reduces the score and tags the page as findings, without a compliance label',
    $results_broken['overall_score'] < 100 && 'findings' === $results_broken['findings'][0]['status'],
    "Score: {$results_broken['overall_score']}, Status: " . ($results_broken['findings'][0]['status'] ?? 'unknown')
);

assert_test(
    'A run with findings reports FINDINGS and counts the page, never a standard verdict',
    'FINDINGS' === $results_broken['compliance_state'] && 1 === $results_broken['pages_with_findings'] && !str_contains(json_encode($results_broken), 'EN 301 549'),
    "State: " . ($results_broken['compliance_state'] ?? '?')
);

// No content checked is no score: until 2026-09-14 an empty site scored 100.
$mock_posts = [];
$results_empty = $auditor->run_audit();
assert_test(
    'An empty site gets no score (null) and state NOTHING_CHECKED instead of 100',
    null === $results_empty['overall_score'] && 0 === $results_empty['total_pages'] && 'NOTHING_CHECKED' === $results_empty['compliance_state'],
    "Result: " . var_export($results_empty, true)
);

// ------------------------------------------------------------
// Test 3: Anlage 3 Statutory Declaration Generator
// ------------------------------------------------------------
echo "\n[TEST GROUP 3] Anlage 3 Statutory Declaration & UWG §5 Truthfulness\n";
$generator = new Anlage3Generator();
$declaration = $generator->generate_declaration([
    'company_name'     => 'Musterfirma GmbH',
    'website_url'      => 'https://example.com',
    'assessment_date'  => '29.08.2026',
    'compliance_level' => 'vollständig konform',
    'officer_name'     => 'Erika Mustermann',
    'feedback_email'   => 'barrierefreiheit@example.com',
    'feedback_phone'   => '+49 30 1234567',
]);

assert_test(
    'Declaration contains proper German statutory headers and references BFSG and EN 301 549',
    str_contains($declaration, 'Erklärung zur Barrierefreiheit') && str_contains($declaration, 'EN 301 549') && str_contains($declaration, 'Barrierefreiheitsstärkungsgesetz (BFSG)'),
    "Declaration snippet: " . substr($declaration, 0, 200)
);

assert_test(
    'Declaration includes officer name and contact details for feedback mechanism',
    str_contains($declaration, 'Erika Mustermann') && str_contains($declaration, 'barrierefreiheit@example.com') && str_contains($declaration, '+49 30 1234567'),
    "Missing officer or contact info"
);

assert_test(
    'Declaration includes statutory supervisory authority contact (Sachsen-Anhalt)',
    str_contains($declaration, 'barrierefreiheit@ms.sachsen-anhalt.de') && str_contains($declaration, 'Landesfachstelle für Barrierefreiheit'),
    "Missing supervisory body"
);

assert_test(
    'Declaration conforms to UWG §5: truthfully discloses self-assessment without false certification claims',
    str_contains($declaration, 'Selbstbewertung') && !str_contains($declaration, 'Zertifiziert und kontinuierlich') && !str_contains($declaration, 'Accessibility Cloud'),
    "Declaration contained unbacked marketing or certification claims"
);

assert_test(
    'Declaration no longer describes a test procedure the plugin does not run (AST analysis, BITV test, "eingehende Prüfung")',
    !str_contains($declaration, 'AST-Analyse') && !str_contains($declaration, 'BITV-Test') && !str_contains($declaration, 'eingehender'),
    "Declaration snippet: " . substr(strip_tags($declaration), 0, 600)
);

assert_test(
    'The website link is real markup, not escaped text (fleet audit D3)',
    str_contains($declaration, '<a href="https://example.com"') && !str_contains($declaration, '&lt;a href'),
    "Declaration snippet: " . substr($declaration, 0, 700)
);

$declaration_unset = $generator->generate_declaration([
    'company_name' => 'Beispiel GmbH',
    'website_url'  => 'https://beispiel.test',
]);
assert_test(
    'Without a stated level the statement says so and claims no conformity',
    str_contains($declaration_unset, 'noch nicht angegeben') && !str_contains($declaration_unset, 'vollständig konform'),
    "Snippet: " . substr(strip_tags($declaration_unset), 0, 600)
);
assert_test(
    'Without an officer or phone the statement prints no invented contact person or number',
    !str_contains($declaration_unset, 'Ansprechpartner:') && !str_contains($declaration_unset, 'Telefon:') && !str_contains($declaration_unset, 'Compliance Officer') && !str_contains($declaration_unset, '+49 345'),
    "Snippet: " . substr(strip_tags($declaration_unset), 0, 600)
);
$declaration_bogus = $generator->generate_declaration([ 'compliance_level' => 'zertifiziert barrierefrei' ]);
assert_test(
    'A level outside the three allowed values is treated as not stated',
    str_contains($declaration_bogus, 'noch nicht angegeben') && !str_contains($declaration_bogus, 'zertifiziert barrierefrei'),
    "Snippet: " . substr(strip_tags($declaration_bogus), 0, 400)
);

// ------------------------------------------------------------
// Test 4: Cryptographic PDF Audit Report & Certificate Data
// ------------------------------------------------------------
echo "\n[TEST GROUP 4] Cryptographic PDF Audit Report & Certificate Generation\n";
$pdf_report = new AuditPdfReport();
$cert_data = $pdf_report->generate_certificate_data($results);

assert_test(
    'Report carries a run identifier and names the plugin as generator, not an issuing agency',
    str_starts_with($cert_data['report_id'], 'BFSG-') && 'WM BFSG Compliance Dashboard (Plugin)' === $cert_data['generator'],
    "Report: " . var_export($cert_data, true)
);

// A legal verdict does not come out of three regex checks (fleet audit D4): until 2026-09-14
// a score of 90 produced legal_status "RECHTSKONFORM (Compliant)".
assert_test(
    'Report makes no legal-conformity statement and no seal claim',
    !array_key_exists('legal_status', $cert_data) && !array_key_exists('sha256_hash', $cert_data) && !str_contains(json_encode($cert_data), 'RECHTSKONFORM'),
    "Report: " . var_export($cert_data, true)
);

assert_test(
    'Report result says what the three checks found',
    'Keine Befunde in den drei Musterprüfungen' === $cert_data['result'] && 2 === $cert_data['pages_checked'],
    "Result: {$cert_data['result']}"
);

$report_broken = $pdf_report->generate_certificate_data($results_broken);
assert_test(
    'Report result counts pages with findings when there are some',
    'Befunde auf 1 von 1 geprüften Seiten' === $report_broken['result'],
    "Result: {$report_broken['result']}"
);

// ------------------------------------------------------------
// Test 5: Suite Hub Spoke Adapter & CycloneDX SBOM
// ------------------------------------------------------------
echo "\n[TEST GROUP 5] Suite Hub Spoke Adapter Interface & CycloneDX SBOM\n";
// Stored runs carry the current shape (pages_with_findings, sample_limit); older ones count as not run.
$run = static fn(?int $score, int $pages): array => ['overall_score' => $score, 'total_pages' => $pages, 'pages_with_findings' => 0, 'sample_limit' => 20, 'compliance_state' => 0 === $pages ? 'NOTHING_CHECKED' : 'FINDINGS', 'audited_at' => '2026-09-14 12:00:00'];
update_option('wm_bfsg_last_audit_results', $run(100, 5), false);
$adapter = new Bfsg_Dashboard_Spoke_Adapter();

assert_test(
    'Spoke Adapter returns exact module slug wm-bfsg-compliance-dashboard',
    $adapter->get_module_slug() === 'wm-bfsg-compliance-dashboard',
    "Slug: " . $adapter->get_module_slug()
);

assert_test(
    'Spoke Adapter reports HEALTHY status when compliance score is >= 80%',
    $adapter->get_health_status() === 'HEALTHY',
    "Health Status: " . $adapter->get_health_status()
);

// 'WARNING' is not a status the hub accepts (HEALTHY, DEGRADED, ERROR, NEEDS_CONFIG).
update_option('wm_bfsg_last_audit_results', $run(70, 5), false);
assert_test(
    'Spoke Adapter reports DEGRADED below 80 (no more WARNING, which the hub rejects)',
    $adapter->get_health_status() === 'DEGRADED',
    "Health Status: " . $adapter->get_health_status()
);

update_option('wm_bfsg_last_audit_results', $run(45, 5), false);
assert_test(
    'Spoke Adapter reports DEGRADED status when compliance score is below 60%',
    $adapter->get_health_status() === 'DEGRADED',
    "Health Status: " . $adapter->get_health_status()
);

global $mock_options;
unset($mock_options['wm_bfsg_last_audit_results']);
assert_test(
    'A site never checked is NEEDS_CONFIG, not HEALTHY (it used to read as score 100)',
    $adapter->get_health_status() === 'NEEDS_CONFIG',
    "Health Status: " . $adapter->get_health_status()
);
update_option('wm_bfsg_last_audit_results', $run(null, 0), false);
assert_test(
    'A run that checked nothing is NEEDS_CONFIG',
    $adapter->get_health_status() === 'NEEDS_CONFIG',
    "Health Status: " . $adapter->get_health_status()
);
// A run stored by the previous version (measured on a local WordPress install on 2026-09-14): its verdict must not survive.
update_option('wm_bfsg_last_audit_results', ['score' => 98, 'overall_score' => 98, 'total_pages' => 20, 'compliance_state' => 'EN 301 549 Level AA Passed', 'audited_at' => '2026-08-29 10:00:00', 'audit_hash' => 'abc'], false);
$legacy_telemetry = $adapter->get_telemetry_metrics();
assert_test(
    'A run stored by an earlier version counts as not run: NEEDS_CONFIG, NOT_RUN, no "Passed" verdict in telemetry',
    'NEEDS_CONFIG' === $adapter->get_health_status() && 'NOT_RUN' === $legacy_telemetry['check_state'] && null === $legacy_telemetry['pattern_check_score'] && !str_contains(json_encode($legacy_telemetry), 'Passed'),
    "Health: " . $adapter->get_health_status() . " / telemetry: " . var_export($legacy_telemetry, true)
);
assert_test(
    'The report of an earlier-version run says nothing was checked instead of "no findings"',
    'Keine Inhalte geprüft' === (new AuditPdfReport())->generate_certificate_data(AccessibilityAuditor::stored_results())['result'],
    'legacy run read as a current one'
);
update_option('wm_bfsg_last_audit_results', $run(null, 0), false);

$telemetry = $adapter->get_telemetry_metrics();
assert_test(
    'Telemetry reports an unset declared level as NOT_SET and no DRAFT/PUBLISHED guess',
    'NOT_SET' === $telemetry['declared_level'] && !array_key_exists('declaration_active', $telemetry),
    "Telemetry: " . var_export($telemetry, true)
);

$sbom = $adapter->get_sbom_data();
// The plugin's own constant (defined above, bumped with the header): a literal had to be edited on every release.
assert_test(
    'Spoke Adapter generates valid CycloneDX SBOM with SHA-256 file hash and BFSG compliance properties',
    $sbom['bom-ref'] === 'pkg:wordpress/wm-bfsg-compliance-dashboard@' . WM_BFSG_DASHBOARD_VERSION && !empty($sbom['hashes'][0]['content']),
    "SBOM: " . var_export($sbom, true)
);

// Reset mock posts for clean action run
$mock_posts = [
    new WP_Post(
        1,
        'Startseite Barrierefrei',
        '<h1>Hauptüberschrift</h1><p>Inhalt</p>'
    ),
];
$action_res = $adapter->execute_action('run_audit');
assert_test(
    'Spoke Adapter executes run_audit action and returns structured success payload',
    $action_res['success'] === true && str_contains($action_res['message'], 'Musterprüfung abgeschlossen'),
    "Action Response: " . var_export($action_res, true)
);
$mock_posts = [];
$action_empty = $adapter->execute_action('run_audit');
assert_test(
    'run_audit on a site without content reports failure (nothing checked)',
    $action_empty['success'] === false,
    "Action Response: " . var_export($action_empty, true)
);

// ------------------------------------------------------------
// Test 6: Anlage 3 Frontend Shortcode Renderer
// ------------------------------------------------------------
echo "\n[TEST GROUP 6] Frontend Shortcode Rendering\n";
$shortcode = new Anlage3Shortcode();
$rendered = $shortcode->render_shortcode([
    'company' => 'WM Suite Test',
    'officer' => 'Arnold Wender',
]);

assert_test(
    'Shortcode renders Anlage 3 declaration HTML with custom attributes applied',
    str_contains($rendered, 'WM Suite Test') && str_contains($rendered, 'Arnold Wender') && str_contains($rendered, 'wm-bfsg-declaration-box'),
    "Rendered HTML snippet: " . substr($rendered, 0, 150)
);

// WordPress hands the callback an empty STRING when the shortcode carries no attributes
// ([wm_bfsg_anlage_3] on a page). With `array $atts` in the signature that was a TypeError
// on the public legal page (fleet audit 2026-09-13 §6.2 #4).
$type_error = null;
try {
    $rendered_no_atts = $shortcode->render_shortcode('');
} catch (\TypeError $e) {
    $type_error = $e->getMessage();
    $rendered_no_atts = '';
}
assert_test(
    'Shortcode without attributes (empty string from WordPress) renders instead of throwing',
    null === $type_error && str_contains($rendered_no_atts, 'wm-bfsg-declaration-box'),
    "TypeError: " . ($type_error ?? 'none') . " / snippet: " . substr($rendered_no_atts, 0, 120)
);

$rendered_empty_array = $shortcode->render_shortcode([]);
assert_test(
    'Shortcode with an empty attribute array falls back to site settings and invents no contact person',
    str_contains($rendered_empty_array, 'WM Suite BFSG Compliance Testbed') && !str_contains($rendered_empty_array, 'Compliance Officer') && !str_contains($rendered_empty_array, '+49 345 000000') && str_contains($rendered_empty_array, 'noch nicht angegeben'),
    "snippet: " . substr(strip_tags($rendered_empty_array), 0, 400)
);

$rendered_unknown = $shortcode->render_shortcode(['company' => 'ACME', 'onload' => 'alert(1)', 'email' => 'a11y@acme.example']);
assert_test(
    'Shortcode ignores unknown attributes and passes known ones through',
    str_contains($rendered_unknown, 'ACME') && str_contains($rendered_unknown, 'a11y@acme.example') && !str_contains($rendered_unknown, 'alert(1)'),
    "snippet: " . substr($rendered_unknown, 0, 300)
);

global $mock_actions;
assert_test(
    'The shortcode name documented in the README ([wm_bfsg_statement]) is registered',
    isset($mock_actions['shortcode_wm_bfsg_statement']) || (function () use (&$mock_actions, $shortcode): bool {
        $shortcode->boot();
        return isset($mock_actions['shortcode_wm_bfsg_statement']);
    })(),
    "registered shortcodes: " . implode(', ', array_filter(array_keys($mock_actions), static fn($k) => str_starts_with($k, 'shortcode_')))
);

// ------------------------------------------------------------
// Test 7: Admin screen tells the truth (2026-09-14)
// ------------------------------------------------------------
echo "\n[TEST GROUP 7] Admin screen wording and defaults\n";
$admin_code = '';
foreach (token_get_all((string) file_get_contents(ABSPATH . 'includes/Admin/AdminDashboard.php')) as $tok) {
    if (is_array($tok) && in_array($tok[0], [T_COMMENT, T_DOC_COMMENT], true)) {
        continue;
    }
    $admin_code .= is_array($tok) ? $tok[1] : $tok;
}
$shortcode->boot();
assert_test(
    'The shortcode the admin screen tells operators to paste is registered (it used to be [wm_bfsg_declaration], which is not)',
    str_contains($admin_code, "SHORTCODE = 'wm_barrierefreiheitserklaerung'") && isset($mock_actions['shortcode_wm_barrierefreiheitserklaerung']) && !str_contains($admin_code, 'wm_bfsg_declaration]'),
    "registered: " . implode(', ', array_filter(array_keys($mock_actions), static fn($k) => str_starts_with($k, 'shortcode_')))
);
assert_test(
    'No certification, legal-defence or seal wording and no personal default contact in the admin screen',
    !preg_match('/CERTIFICADO|DEFENSA LEGAL|DECLARACIÓN OFICIAL|Arnold Wender|\+49 345 213890|sha256_fingerprint/', $admin_code),
    'forbidden wording found'
);
assert_test(
    'Admin screen uses dashicons, not emoji',
    0 === preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $admin_code),
    'emoji found'
);
assert_test(
    'The dashboard widget reads the stored result instead of running the check on every load (fleet audit D6)',
    (bool) preg_match('/function render_dashboard_widget\(\): void \{(?:(?!function ).)*?AccessibilityAuditor::stored_results\(\)/s', $admin_code) && !preg_match('/function render_dashboard_widget\(\): void \{(?:(?!function ).)*?run_audit\(/s', $admin_code),
    'widget still runs the audit'
);
assert_test(
    'Saved compliance levels are checked against the three allowed values (fleet audit D7)',
    str_contains($admin_code, "in_array( \$level, Anlage3Generator::COMPLIANCE_LEVELS, true )"),
    'no allowlist on save'
);

// ------------------------------------------------------------
// Test 8: REST controller (2026-09-14)
// ------------------------------------------------------------
echo "\n[TEST GROUP 8] REST controller\n";
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {}
}
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public function __construct(public $data = null, public int $status = 200) {}
        public function get_data() { return $this->data; }
    }
}
require_once ABSPATH . 'includes/API/RestController.php';
$rest = new \WMCyber\BFSG\Dashboard\API\RestController();

$mock_posts = [];
$rest_empty = $rest->handle_run_audit(new WP_REST_Request())->get_data();
assert_test(
    '/run-audit answers success false when nothing was checked (it answered true)',
    false === $rest_empty['success'] && 0 === $rest_empty['data']['total_pages'],
    "Response: " . var_export($rest_empty, true)
);
$mock_posts = [ new WP_Post(9, 'Kontakt', '<h1>Kontakt</h1><p><img src="a.png"></p>') ];
$rest_run = $rest->handle_run_audit(new WP_REST_Request())->get_data();
assert_test(
    '/run-audit answers success true once content was checked',
    true === $rest_run['success'] && 1 === $rest_run['data']['pages_with_findings'],
    "Response: " . var_export($rest_run, true)
);

update_option('wm_bfsg_declaration_settings', ['company_name' => 'Gespeicherte GmbH', 'compliance_level' => 'größtenteils konform', 'officer_name' => 'Frau Beispiel']);
$preview = $rest->handle_preview_declaration(new WP_REST_Request())->get_data();
assert_test(
    '/preview-declaration renders the saved settings, not the unconfigured defaults',
    str_contains($preview['html'], 'Gespeicherte GmbH') && str_contains($preview['html'], 'größtenteils konform') && str_contains($preview['html'], 'Frau Beispiel'),
    "snippet: " . substr(strip_tags($preview['html']), 0, 400)
);

unset($mock_options['wm_bfsg_last_audit_results']);
$mock_posts = [ new WP_Post(10, 'Neu', '<h1>Neu</h1>') ];
$cert = $rest->handle_audit_certificate(new WP_REST_Request())->get_data();
assert_test(
    '/audit-certificate is read-only: without a stored run it reports nothing checked and stores nothing',
    false === $cert['success'] && 'Keine Inhalte geprüft' === $cert['data']['result'] && !isset($mock_options['wm_bfsg_last_audit_results']),
    "Response: " . var_export($cert, true)
);

// ------------------------------------------------------------
// Test 9: statement date, query arguments, check patterns (2026-09-15)
// ------------------------------------------------------------
echo "\n[TEST GROUP 9] The statement names the saved date, never the day of the page view\n";
$today = gmdate('d.m.Y');
$undated = $generator->generate_declaration(['company_name' => 'Beispiel GmbH', 'website_url' => 'https://beispiel.test']);
assert_test(
    'Without a date the statement says the date is not stated and prints no date of today',
    str_contains($undated, 'Das Erstellungsdatum ist noch nicht angegeben.') && !str_contains($undated, $today) && !str_contains($undated, 'wurde am'),
    "snippet: " . substr(strip_tags($undated), 0, 500)
);
$dated = $generator->generate_declaration(['assessment_date' => '2026-08-29']);
assert_test(
    'A stored Y-m-d date is printed as d.m.Y in both sentences',
    2 === substr_count($dated, '29.08.2026') && !str_contains($dated, 'noch nicht angegeben.</strong> Die Angaben'),
    "snippet: " . substr(strip_tags($dated), 0, 500)
);
assert_test(
    'An impossible date (2026-02-30) is treated as not stated',
    str_contains($generator->generate_declaration(['assessment_date' => '2026-02-30']), 'Erstellungsdatum ist noch nicht angegeben'),
    'impossible date printed'
);
update_option('wm_bfsg_declaration_settings', ['company_name' => 'Datum GmbH', 'assessment_date' => '2026-07-01']);
$rendered_dated = $shortcode->render_shortcode('');
assert_test(
    'The shortcode passes the saved date to the statement',
    str_contains($rendered_dated, '01.07.2026') && !str_contains($rendered_dated, $today),
    "snippet: " . substr(strip_tags($rendered_dated), 0, 400)
);

// Saving the form: the date field is validated.
foreach ([
    'wp_verify_nonce' => fn($n, $a) => 'nonce' === $n,
    'current_user_can' => fn($c) => true,
    'wp_unslash' => fn($v) => $v,
    'esc_url_raw' => fn($u) => (string) $u,
    'add_query_arg' => fn($args, $url) => $url . '?' . http_build_query($args),
    'wp_safe_redirect' => function ($url) { throw new \RuntimeException('redirect'); },
] as $fn_name => $impl) {
    if (!function_exists($fn_name)) {
        $GLOBALS['mock_impl'][$fn_name] = $impl;
        eval("function {$fn_name}(...\$a) { return (\$GLOBALS['mock_impl']['{$fn_name}'])(...\$a); }");
    }
}
require_once ABSPATH . 'includes/Admin/AdminDashboard.php';
$save = static function (string $date): string {
    $_POST = ['wm_bfsg_decl_nonce' => 'nonce', 'company_name' => 'X', 'website_url' => 'https://x.test', 'compliance_level' => '', 'feedback_email' => 'a@x.test', 'supervisory_body' => 'Stelle', 'assessment_date' => $date];
    try {
        \WMCyber\BFSG\Dashboard\Admin\AdminDashboard::instance()->save_settings();
    } catch (\RuntimeException $e) {
        // wp_safe_redirect() is followed by exit in the plugin; the stub throws instead.
    }
    $_POST = [];
    return (string) (get_option('wm_bfsg_declaration_settings')['assessment_date'] ?? 'missing');
};
assert_test(
    'The settings form stores a valid Y-m-d date and drops an impossible or free-text one',
    '2026-03-01' === $save('2026-03-01') && '' === $save('2026-02-30') && '' === $save('heute'),
    'stored: ' . var_export(get_option('wm_bfsg_declaration_settings'), true)
);

echo "\n[TEST GROUP 10] The run asks for 20 items by date, without sticky posts in front\n";
$mock_posts = [ new WP_Post(20, 'A', '<p>x</p>') ];
$auditor->run_audit();
assert_test(
    'WP_Query gets ignore_sticky_posts, orderby date DESC and the 20-item limit',
    true === (WP_Query::$last_args['ignore_sticky_posts'] ?? null) && 'date' === (WP_Query::$last_args['orderby'] ?? null) && 'DESC' === (WP_Query::$last_args['order'] ?? null) && 20 === (WP_Query::$last_args['posts_per_page'] ?? null),
    'args: ' . var_export(WP_Query::$last_args, true)
);

echo "\n[TEST GROUP 11] Check patterns: attribute names, named links, German findings, no verdict labels\n";
$mock_posts = [ new WP_Post(21, 'Muster', '<p><img data-alt="x" src="a.png"><img src="b.png" alt = "Beschreibung"></p><p><a href="/start" aria-label="Startseite"></a><a href="/leer"></a><a href="/t" title="Suche"> </a></p>') ];
$pattern_run = $auditor->run_audit();
$pattern_issues = $pattern_run['findings'][0]['issues'];
$mock_posts = [ new WP_Post(23, 'data-alt', '<p><img data-alt="x" src="a.png"></p>'), new WP_Post(24, 'alt mit Leerzeichen', '<p><img src="b.png" alt = "Beschreibung"></p>') ];
$alt_run = $auditor->run_audit();
assert_test(
    'data-alt is not an alt attribute (flagged), alt = "x" with spaces is one (not flagged)',
    ['1 Bild ohne alt-Attribut (WCAG 1.1.1)'] === $alt_run['findings'][0]['issues'] && [] === $alt_run['findings'][1]['issues'],
    'issues: ' . var_export(array_column($alt_run['findings'], 'issues'), true)
);
assert_test(
    'A link named by aria-label or title is not counted as empty; one without any name is',
    in_array('1 Link ohne Text (WCAG 2.4.4)', $pattern_issues, true),
    'issues: ' . implode(' | ', $pattern_issues)
);
$mock_posts = [ new WP_Post(22, 'Eins', '<p><img src="a.png"></p>') ];
$one_run = $auditor->run_audit();
assert_test(
    'One image without alt scores 90 and is tagged findings; no stored run carries compliant or critical_violations',
    90 === $one_run['findings'][0]['score'] && 'findings' === $one_run['findings'][0]['status'] && !preg_match('/compliant|critical_violations|minor_issues/', (string) json_encode(get_option('wm_bfsg_last_audit_results'))),
    'finding: ' . var_export($one_run['findings'][0], true)
);
assert_test(
    'The dashboard widget has no green is-valid banner and the tabs are labelled in German',
    !str_contains($admin_code, "'is-valid'") && !str_contains((string) file_get_contents(ABSPATH . 'includes/Admin/AdminDashboard.php'), "'Module Tabs'"),
    'is-valid or Module Tabs still in the admin code'
);

assert_test(
    'tests/test-anlage3-claims.php exits 1 when an assertion failed (it always exited 0)',
    (bool) preg_match('/exit\(\s*\$GLOBALS\[\'wm_b_failed\'\]\s*>\s*0\s*\?\s*1\s*:\s*0\s*\);\s*$/', (string) file_get_contents(ABSPATH . 'tests/test-anlage3-claims.php')),
    'no exit code from the integration falsifier'
);

// ------------------------------------------------------------
// Test 12: v1.1.1 Ready-to-Ship gates (opt-in uninstall, block editor, hooks, bindings, kses)
// ------------------------------------------------------------
echo "\n[TEST GROUP 12] Opt-in uninstallation, Gutenberg block editor, developer hooks & dynamic bindings\n";

assert_test(
    'Admin preview escapes declaration HTML via wp_kses_post',
    str_contains($admin_code, 'wp_kses_post( $declaration_html )'),
    'wp_kses_post missing on $declaration_html preview'
);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', true);
}
update_option('wm_bfsg_declaration_settings', ['company_name' => 'Preserve GmbH']);
update_option('wm_bfsg_last_audit_results', ['total_pages' => 3, 'pages_with_findings' => 0, 'sample_limit' => 20]);
delete_option('wm_bfsg_dashboard_delete_data_on_uninstall');
require ABSPATH . 'uninstall.php';
assert_test(
    'uninstall.php preserves statement settings and audit results by default when opt-in is disabled',
    'Preserve GmbH' === (get_option('wm_bfsg_declaration_settings')['company_name'] ?? null)
        && 3 === (get_option('wm_bfsg_last_audit_results')['total_pages'] ?? null),
    'options were deleted without opt-in'
);

update_option('wm_bfsg_dashboard_delete_data_on_uninstall', '1');
require ABSPATH . 'uninstall.php';
assert_test(
    'uninstall.php deletes statement settings, audit results and opt-in flag when opted in',
    false === get_option('wm_bfsg_declaration_settings', false)
        && false === get_option('wm_bfsg_last_audit_results', false)
        && false === get_option('wm_bfsg_dashboard_delete_data_on_uninstall', false),
    'options survived opt-in uninstall'
);

\WMCyber\BFSG\Dashboard\Admin\AdminDashboard::instance()->register_settings();
assert_test(
    'AdminDashboard registers wm_bfsg_dashboard_delete_data_on_uninstall via register_setting',
    isset($GLOBALS['mock_registered_settings']['wm_bfsg_dashboard_delete_data_on_uninstall']),
    'setting not registered'
);

$block_json_raw = (string) file_get_contents(ABSPATH . 'blocks/anlage-3/block.json');
$block_meta     = json_decode($block_json_raw, true);
$shortcode->register_block();
assert_test(
    'Gutenberg block wm/bfsg-anlage-3 declares editorScript in block.json and registers compiled editor bundle',
    is_array($block_meta)
        && 'wm-bfsg-dashboard-blocks-editor' === ($block_meta['editorScript'] ?? '')
        && is_file(ABSPATH . 'build/index.js')
        && is_file(ABSPATH . 'build/index.asset.php')
        && isset($GLOBALS['mock_registered_scripts']['wm-bfsg-dashboard-blocks-editor'])
        && isset($GLOBALS['mock_script_translations']['wm-bfsg-dashboard-blocks-editor']),
    'block editor script or block.json editorScript missing'
);

add_filter('wm_bfsg_dashboard_declaration_html', static fn(string $h): string => $h . '<!-- filtered-decl -->');
$filtered_decl = $generator->generate_declaration(['company_name' => 'Filter Test']);
assert_test(
    'Filter wm_bfsg_dashboard_declaration_html filters generated statement HTML',
    str_contains($filtered_decl, '<!-- filtered-decl -->'),
    'declaration filter did not apply'
);

add_filter('wm_bfsg_dashboard_audit_report', static function (array $rep): array {
    $rep['custom_note'] = 'filtered';
    return $rep;
});
$filtered_rep = $pdf_report->generate_certificate_data([]);
assert_test(
    'Filter wm_bfsg_dashboard_audit_report filters report summary data',
    'filtered' === ($filtered_rep['custom_note'] ?? ''),
    'report filter did not apply'
);

$audit_hook_fired = false;
add_action('wm_bfsg_dashboard_audit_completed', static function (array $res) use (&$audit_hook_fired): void {
    $audit_hook_fired = array_key_exists('compliance_state', $res);
});
$mock_posts = [];
$auditor->run_audit();
assert_test(
    'Action wm_bfsg_dashboard_audit_completed fires after run_audit()',
    true === $audit_hook_fired,
    'audit_completed action did not fire'
);

require_once ABSPATH . 'includes/Frontend/BlockIntegrations.php';
require_once ABSPATH . 'includes/Core/Plugin.php';
$ready_fired = false;
add_action('wm_bfsg_dashboard_ready', static function ($plugin) use (&$ready_fired): void {
    $ready_fired = $plugin instanceof \WMCyber\BFSG\Dashboard\Core\Plugin;
});
\WMCyber\BFSG\Dashboard\Core\Plugin::instance()->boot();
$integrations = new \WMCyber\BFSG\Dashboard\Frontend\BlockIntegrations();
$integrations->register_block_bindings();
$integrations->register_generateblocks_tag();
unset($mock_options['wm_bfsg_declaration_settings'], $mock_options['wm_bfsg_last_audit_results']);
assert_test(
    'Plugin fires wm_bfsg_dashboard_ready and registers honest Block Bindings + GenerateBlocks Dynamic Tag',
    true === $ready_fired
        && isset($GLOBALS['mock_block_bindings']['wm-bfsg-dashboard/metrics'])
        && isset(\GenerateBlocks_Register_Dynamic_Tag::$registered['wm_bfsg_dashboard_metric'])
        && 'noch nicht angegeben' === $integrations->get_block_binding_value(['key' => 'compliance_level'])
        && 'nicht geprüft' === $integrations->render_dynamic_tag(['key' => 'score']),
    'ready hook or dynamic bindings missing'
);

assert_test('no PHP warning, notice or deprecation during the run', [] === $GLOBALS['bfsg_php_warnings'], implode(' | ', array_slice($GLOBALS['bfsg_php_warnings'], 0, 5)));

// ------------------------------------------------------------
// Summary & Exit Code
// ------------------------------------------------------------
echo "\n==============================================================================\n";
echo "  TEST SUITE RESULTS: {$passed_count} PASSED | {$failed_count} FAILED\n";
echo "==============================================================================\n\n";

exit($failed_count === 0 ? 0 : 1);
