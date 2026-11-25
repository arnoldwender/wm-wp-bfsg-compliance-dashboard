<?php
/**
 * WM Suite BFSG 2025 Compliance Dashboard — Suite Hub Spoke Adapter
 *
 * Implements the WM Suite Hub contract (WM_Plugin_Module_Interface)
 * for the hub: health and telemetry from the last stored pattern-check run, the declared level, and the run_audit action.
 * Nothing runs continuously; the checks run on request.
 *
 * @package WMCyber\BFSG\Dashboard
 */

declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Adapters;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
	return;
}

use ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface;
use WMCyber\BFSG\Dashboard\Scanner\AccessibilityAuditor;

final class Bfsg_Dashboard_Spoke_Adapter implements WM_Plugin_Module_Interface {

	public function get_module_slug(): string {
		return 'wm-bfsg-compliance-dashboard';
	}

	public function get_name(): string {
		return 'WM BFSG 2025 Compliance Dashboard';
	}

	public function get_version(): string {
		return defined( 'WM_BFSG_DASHBOARD_VERSION' ) ? WM_BFSG_DASHBOARD_VERSION : '1.2.2';
	}

	public function get_description(): string {
		return 'Drei Musterprüfungen auf bis zu 20 Seiten & Generator für die Erklärung zur Barrierefreiheit aus den Angaben des Betreibers.';
	}

	/**
	 * NEEDS_CONFIG until a run has checked content; HEALTHY from a score of 80, DEGRADED below.
	 * Until 2026-09-14 a site that was never audited read as score 100 and HEALTHY, and scores between
	 * 60 and 79 returned 'WARNING', a status the hub does not accept.
	 */
	public function get_health_status(): string {
		$audit = AccessibilityAuditor::stored_results();
		if ( ! isset( $audit['overall_score'] ) || null === $audit['overall_score'] ) {
			return 'NEEDS_CONFIG';
		}
		return (int) $audit['overall_score'] >= 80 ? 'HEALTHY' : 'DEGRADED';
	}

	public function get_sbom_data(): array {
		return [
			'bom-ref'     => 'pkg:wordpress/wm-bfsg-compliance-dashboard@' . $this->get_version(),
			'type'        => 'application',
			'name'        => $this->get_name(),
			'version'     => $this->get_version(),
			'description' => $this->get_description(),
			'scope'       => 'required',
			'hashes'      => [
				[
					'alg'     => 'SHA-256',
					'content' => hash_file( 'sha256', WM_BFSG_DASHBOARD_FILE ),
				],
			],
			'licenses'    => [
				[
					'license' => [
						'id' => 'GPL-2.0-or-later',
					],
				],
			],
			'properties'  => [
				[
					'name'  => 'wm:features:checks',
					'value' => 'img without alt, empty links, skipped heading levels in the stored content of up to 20 published posts',
				],
				[
					'name'  => 'wm:features:declaration',
					'value' => 'Accessibility statement generated from operator settings (self-assessment)',
				],
			],
		];
	}

	public function get_dependencies(): array {
		return [];
	}

	public function get_settings_url(): ?string {
		return admin_url( 'admin.php?page=wm-bfsg-dashboard' );
	}

	public function get_telemetry_metrics(): array {
		$audit = AccessibilityAuditor::stored_results();
		$settings = (array) get_option( 'wm_bfsg_declaration_settings', [] );

		// `declaration_active` used to read a `published_url` setting nothing ever writes (always DRAFT),
		// and an unset level read as "nicht konform".
		return [
			'pattern_check_score' => isset( $audit['overall_score'] ) ? $audit['overall_score'] : null,
			'pages_checked'       => (int) ( $audit['total_pages'] ?? 0 ),
			'pages_with_findings' => (int) ( $audit['pages_with_findings'] ?? 0 ),
			'check_state'         => $audit['compliance_state'] ?? 'NOT_RUN',
			'declared_level'      => in_array( $settings['compliance_level'] ?? '', \WMCyber\BFSG\Dashboard\Legal\Anlage3Generator::COMPLIANCE_LEVELS, true ) ? $settings['compliance_level'] : 'NOT_SET',
			'last_check_run'      => $audit['audited_at'] ?? null,
		];
	}

	public function execute_action( string $action, array $params = [] ): array {
		$auditor = new AccessibilityAuditor();

		switch ( $action ) {
			case 'self_test':
			case 'run_audit':
				$results = $auditor->run_audit();
				if ( 0 === (int) $results['total_pages'] ) {
					return [
						'success' => false,
						'message' => 'Keine veröffentlichten Seiten, Beiträge oder Produkte gefunden: nichts geprüft.',
					];
				}
				return [
					'success' => true,
					'message' => sprintf( 'Musterprüfung abgeschlossen: %d Seiten geprüft, %d mit Befunden, Punktzahl %d.', (int) $results['total_pages'], (int) $results['pages_with_findings'], (int) $results['overall_score'] ),
				];

			default:
				return [
					'success' => false,
					'message' => sprintf( 'Unbekannte Spoke-Aktion "%s".', esc_html( $action ) ),
				];
		}
	}
}
