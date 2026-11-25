<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\BFSG\Dashboard\Scanner\AccessibilityAuditor;
use WMCyber\BFSG\Dashboard\Legal\Anlage3Generator;
use WP_CLI;

final class BfsgAuditCommand {
	/**
	 * Run the three pattern checks (img without alt, empty links, skipped heading levels) over the stored content of up to 20 published pages, posts and products.
	 *
	 * Not a WCAG 2.2 AA or EN 301 549 audit: the rendered site is never looked at.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bfsg-audit scan
	 *
	 * @when after_wp_load
	 */
	public function scan( array $args, array $assoc_args ): void {
		$results = ( new AccessibilityAuditor() )->run_audit();

		if ( 0 === (int) $results['total_pages'] ) {
			WP_CLI::error( 'No published pages, posts or products found: nothing was checked.' );
		}

		WP_CLI::success( sprintf( 'Pattern checks done: %d item(s) checked, %d with findings, score %d.', $results['total_pages'], $results['pages_with_findings'], $results['overall_score'] ) );
		foreach ( $results['findings'] as $finding ) {
			if ( [] === $finding['issues'] ) {
				continue;
			}
			// Titles are stored with HTML entities (&amp;); a terminal shows them literally.
			WP_CLI::line( sprintf( '- #%d %s: %s', $finding['id'], html_entity_decode( (string) $finding['title'], ENT_QUOTES, 'UTF-8' ), implode( '; ', $finding['issues'] ) ) );
		}
	}

	/**
	 * Print the accessibility statement generated from the saved settings, as plain text.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bfsg-audit anlage3
	 *
	 * @when after_wp_load
	 */
	public function anlage3( array $args, array $assoc_args ): void {
		$settings = (array) get_option( 'wm_bfsg_declaration_settings', [] );
		$html     = ( new Anlage3Generator() )->generate_declaration( $settings );

		WP_CLI::line( WP_CLI::colorize( '%C=== Erklärung zur Barrierefreiheit ===%n' ) );
		WP_CLI::line( wp_strip_all_tags( $html ) );
	}
}
