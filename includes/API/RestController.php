<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\BFSG\Dashboard\Scanner\AccessibilityAuditor;
use WMCyber\BFSG\Dashboard\Legal\Anlage3Generator;
use WMCyber\BFSG\Dashboard\Export\AuditPdfReport;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Three routes under `wm-bfsg-dashboard/v1`, all behind `manage_options`.
 *
 * Until 2026-09-14: `/run-audit` answered `success: true` when nothing was checked, `/preview-declaration`
 * ignored the saved settings (the preview showed a statement the page would not print), and the GET
 * `/audit-certificate` ran and stored a new check whenever none was stored.
 */
final class RestController {
	private const NAMESPACE = 'wm-bfsg-dashboard/v1';

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/run-audit', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_run_audit' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/preview-declaration', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_preview_declaration' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );

		register_rest_route( self::NAMESPACE, '/audit-certificate', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_audit_certificate' ],
			'permission_callback' => [ $this, 'check_permissions' ],
		] );
	}

	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/** Runs the three pattern checks; `success` is false when no published content was found. */
	public function handle_run_audit( WP_REST_Request $request ): WP_REST_Response {
		$results = ( new AccessibilityAuditor() )->run_audit();

		return new WP_REST_Response( [
			'success' => (int) $results['total_pages'] > 0,
			'data'    => $results,
		], 200 );
	}

	/** The statement as the shortcode would print it without attributes: saved settings, then site defaults. */
	public function handle_preview_declaration( WP_REST_Request $request ): WP_REST_Response {
		$settings = (array) get_option( 'wm_bfsg_declaration_settings', [] );
		$html     = ( new Anlage3Generator() )->generate_declaration( array_map( 'strval', $settings ) );

		return new WP_REST_Response( [
			'success' => true,
			'html'    => $html,
		], 200 );
	}

	/** Summary of the stored run. Read-only: no stored run gives "Keine Inhalte geprüft". */
	public function handle_audit_certificate( WP_REST_Request $request ): WP_REST_Response {
		$last_audit = AccessibilityAuditor::stored_results();
		$report     = ( new AuditPdfReport() )->generate_certificate_data( $last_audit );

		return new WP_REST_Response( [
			'success' => (int) ( $last_audit['total_pages'] ?? 0 ) > 0,
			'data'    => $report,
		], 200 );
	}
}
