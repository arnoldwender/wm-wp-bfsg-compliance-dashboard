<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\BFSG\Dashboard\Frontend\Anlage3Shortcode;
use WMCyber\BFSG\Dashboard\Frontend\BlockIntegrations;
use WMCyber\BFSG\Dashboard\Admin\AdminDashboard;
use WMCyber\BFSG\Dashboard\API\RestController;
use WMCyber\BFSG\Dashboard\CLI\BfsgAuditCommand;

final class Plugin {
	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'init', function (): void {
			load_plugin_textdomain(
				'wm-bfsg-compliance-dashboard',
				false,
				dirname( plugin_basename( WM_BFSG_DASHBOARD_FILE ) ) . '/languages'
			);
		} );

		// Shortcodes, Blocks & Ecosystem Integrations
		( new Anlage3Shortcode() )->boot();
		( new BlockIntegrations() )->boot();

		// Admin Dashboard
		if ( is_admin() ) {
			AdminDashboard::instance()->boot();
		}

		// REST API
		add_action( 'rest_api_init', function (): void {
			( new RestController() )->register_routes();
		} );

		// WP-CLI
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'bfsg-audit', BfsgAuditCommand::class );
		}

		/**
		 * Fires after WM BFSG Compliance Dashboard has booted its core services.
		 *
		 * @param Plugin $plugin Plugin singleton instance.
		 */
		do_action( 'wm_bfsg_dashboard_ready', $this );
	}
}
