<?php
/**
 * Plugin Name:       WM BFSG 2025 Legal Compliance Dashboard & Anlage 3 Generator
 * Plugin URI:        https://arnoldwender.com/wordpress-plugins/wm-bfsg-compliance-dashboard/
 * Description:       Erzeugt die Erklärung zur Barrierefreiheit aus den Angaben des Betreibers (Shortcode & Block) und führt drei Musterprüfungen auf dem gespeicherten Inhalt von bis zu 20 Seiten durch.
 * Version:           1.2.2
 * Requires at least: 6.4
 * Tested up to:      7.1
 * Requires PHP:      8.2
 * Author:            Arnold Wender
 * Author URI:        https://arnoldwender.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wm-bfsg-compliance-dashboard
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WM_BFSG_DASHBOARD_VERSION', '1.2.2' );
define( 'WM_BFSG_DASHBOARD_FILE', __FILE__ );
define( 'WM_BFSG_DASHBOARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'WM_BFSG_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

// Autoloader
spl_autoload_register( function ( string $class ): void {
	$prefix   = 'WMCyber\\BFSG\\Dashboard\\';
	$base_dir = WM_BFSG_DASHBOARD_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Register as Spoke in WM Suite Hub
add_filter( 'wm_register_suite_module', function ( array $modules ): array {
	if ( ! class_exists( 'WMCyber\BFSG\Dashboard\Adapters\Bfsg_Dashboard_Spoke_Adapter' ) && file_exists( WM_BFSG_DASHBOARD_PATH . 'includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php' ) ) {
		require_once WM_BFSG_DASHBOARD_PATH . 'includes/Adapters/Bfsg_Dashboard_Spoke_Adapter.php';
	}
	if ( class_exists( 'WMCyber\BFSG\Dashboard\Adapters\Bfsg_Dashboard_Spoke_Adapter' ) ) {
		$modules[] = new Adapters\Bfsg_Dashboard_Spoke_Adapter();
	}
	return $modules;
} );

// Bootstrap
add_action( 'plugins_loaded', function (): void {
	Core\Plugin::instance()->boot();
} );

