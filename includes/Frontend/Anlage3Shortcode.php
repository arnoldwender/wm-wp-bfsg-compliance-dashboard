<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\BFSG\Dashboard\Legal\Anlage3Generator;

final class Anlage3Shortcode {
	/**
	 * Attributes the shortcode accepts. Empty string means "use the saved setting".
	 */
	private const DEFAULT_ATTS = [
		'company'   => '',
		'url'       => '',
		'level'     => '',
		'email'     => '',
		'phone'     => '',
		'officer'   => '',
		'authority' => '',
	];

	public function boot(): void {
		add_shortcode( 'wm_bfsg_anlage_3', [ $this, 'render_shortcode' ] );
		add_shortcode( 'wm_barrierefreiheitserklaerung', [ $this, 'render_shortcode' ] );
		// Name the README documents; kept as an alias of the two above.
		add_shortcode( 'wm_bfsg_statement', [ $this, 'render_shortcode' ] );

		// Register Gutenberg Block and editor script if available
		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		$asset_file = WM_BFSG_DASHBOARD_PATH . 'build/index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: [
				'dependencies' => [ 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ],
				'version'      => WM_BFSG_DASHBOARD_VERSION,
			];

		if ( function_exists( 'wp_register_script' ) ) {
			wp_register_script(
				'wm-bfsg-dashboard-blocks-editor',
				WM_BFSG_DASHBOARD_URL . 'build/index.js',
				(array) ( $asset['dependencies'] ?? [] ),
				(string) ( $asset['version'] ?? WM_BFSG_DASHBOARD_VERSION ),
				true
			);
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'wm-bfsg-dashboard-blocks-editor',
				'wm-bfsg-compliance-dashboard',
				WM_BFSG_DASHBOARD_PATH . 'languages'
			);
		}

		if ( function_exists( 'register_block_type' ) ) {
			$block_dir = WM_BFSG_DASHBOARD_PATH . 'blocks/anlage-3';
			register_block_type(
				is_dir( $block_dir ) ? $block_dir : 'wm/bfsg-anlage-3',
				[
					'editor_script'   => 'wm-bfsg-dashboard-blocks-editor',
					'render_callback' => [ $this, 'render_shortcode' ],
				]
			);
		}
	}

	/**
	 * Render the statement. WordPress passes an empty STRING when the shortcode has no
	 * attributes and the block API passes its attribute array, so the parameter is untyped
	 * and cast before shortcode_atts() (a typed `array $atts` was a TypeError on the public
	 * legal page, fleet audit 2026-09-13 §6.2 #4).
	 *
	 * @param array<string, string>|string $atts Shortcode or block attributes.
	 */
	public function render_shortcode( $atts = [] ): string {
		$atts = shortcode_atts( self::DEFAULT_ATTS, (array) $atts, 'wm_bfsg_anlage_3' );
		$atts = array_map( static fn( $v ): string => sanitize_text_field( (string) $v ), $atts );

		$generator = new Anlage3Generator();
		$settings  = (array) get_option( 'wm_bfsg_declaration_settings', [] );

		// Attribute wins when given, then the saved setting, then the site default.
		$pick = static fn( string $att, string $setting, $default ) => '' !== $atts[ $att ] ? $atts[ $att ] : ( $settings[ $setting ] ?? $default );

		// No invented fallbacks: an unset level, phone or contact person stays unset and the generator
		// says so or leaves the line out (until 2026-09-14: "vollständig konform", "+49 345 000000",
		// "WM Suite Compliance Officer" on every unconfigured legal page).
		$overrides = [
			'company_name'     => $pick( 'company', 'company_name', get_bloginfo( 'name' ) ),
			'website_url'      => $pick( 'url', 'website_url', home_url() ),
			'compliance_level' => $pick( 'level', 'compliance_level', '' ),
			// The saved date of the self-assessment; no attribute, and never today's date.
			'assessment_date'  => (string) ( $settings['assessment_date'] ?? '' ),
			'feedback_email'   => $pick( 'email', 'feedback_email', get_option( 'admin_email' ) ),
			'feedback_phone'   => $pick( 'phone', 'feedback_phone', '' ),
			'officer_name'     => $pick( 'officer', 'officer_name', '' ),
			'supervisory_body' => $pick( 'authority', 'supervisory_body', 'Landesfachstelle für Barrierefreiheit Sachsen-Anhalt (Halle / Magdeburg)' ),
		];

		return $generator->generate_declaration( $overrides );
	}
}
