<?php
/**
 * WM Suite BFSG 2025 Compliance Dashboard — Opt-In Uninstaller
 *
 * Triggered when plugin is deleted via WordPress Admin.
 * Preserves operator statement settings and audit reports by default unless
 * explicit data deletion on uninstall has been enabled.
 *
 * @package WMCyber\BFSG\Dashboard
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$wm_bfsg_delete_data = filter_var(
	get_option( 'wm_bfsg_dashboard_delete_data_on_uninstall', false ),
	FILTER_VALIDATE_BOOLEAN
) || filter_var(
	function_exists( 'get_site_option' ) ? get_site_option( 'wm_bfsg_dashboard_delete_data_on_uninstall', false ) : false,
	FILTER_VALIDATE_BOOLEAN
);

if ( ! $wm_bfsg_delete_data ) {
	return;
}

$wm_bfsg_dashboard_options = [
	'wm_bfsg_last_audit_results',
	'wm_bfsg_declaration_settings',
	'wm_bfsg_dashboard_delete_data_on_uninstall',
];

foreach ( $wm_bfsg_dashboard_options as $wm_bfsg_dashboard_option ) {
	delete_option( $wm_bfsg_dashboard_option );
	if ( function_exists( 'delete_site_option' ) ) {
		delete_site_option( $wm_bfsg_dashboard_option );
	}
}
