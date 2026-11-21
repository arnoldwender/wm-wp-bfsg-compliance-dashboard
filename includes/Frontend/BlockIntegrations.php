<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\BFSG\Dashboard\Admin\AdminDashboard;
use WMCyber\BFSG\Dashboard\Legal\Anlage3Generator;
use WMCyber\BFSG\Dashboard\Scanner\AccessibilityAuditor;

/**
 * WordPress 6.5+ Block Bindings source (`wm-bfsg-dashboard/metrics`) and
 * GenerateBlocks 2.x Dynamic Tag (`{{wm_bfsg_dashboard_metric}}`).
 *
 * Missing data is never replaced with an invented conformity level or score.
 */
final class BlockIntegrations {
	public const BINDING_SOURCE = 'wm-bfsg-dashboard/metrics';
	public const GB_TAG         = 'wm_bfsg_dashboard_metric';

	public function boot(): void {
		add_action( 'init', [ $this, 'register_block_bindings' ] );
		add_action( 'init', [ $this, 'register_generateblocks_tag' ] );
		add_filter( 'generateblocks_dynamic_tag_output', [ $this, 'filter_generateblocks_output' ], 10, 3 );
	}

	public function register_block_bindings(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'wm-bfsg-dashboard/metrics',
			[
				'label'              => __( 'WM BFSG Kennzahlen', 'wm-bfsg-compliance-dashboard' ),
				'get_value_callback' => [ $this, 'get_block_binding_value' ],
				'uses_context'       => [],
			]
		);
	}

	public function register_generateblocks_tag(): void {
		if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}

		new \GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'       => __( 'BFSG Kennzahl', 'wm-bfsg-compliance-dashboard' ),
				'tag'         => 'wm_bfsg_dashboard_metric',
				'type'        => 'wm-bfsg-dashboard',
				'supports'    => [],
				'description' => __( 'Gibt eine gespeicherte Angabe der Erklärung oder Kennzahl der letzten Musterprüfung aus.', 'wm-bfsg-compliance-dashboard' ),
				'options'     => [
					'key' => [
						'type'    => 'select',
						'label'   => __( 'Kennzahl', 'wm-bfsg-compliance-dashboard' ),
						'default' => 'compliance_level',
						'options' => [
							[
								'value' => 'compliance_level',
								'label' => __( 'Stand der Vereinbarkeit', 'wm-bfsg-compliance-dashboard' ),
							],
							[
								'value' => 'assessment_date',
								'label' => __( 'Datum der Selbstbewertung', 'wm-bfsg-compliance-dashboard' ),
							],
							[
								'value' => 'pages_checked',
								'label' => __( 'Geprüfte Seiten', 'wm-bfsg-compliance-dashboard' ),
							],
							[
								'value' => 'pages_with_findings',
								'label' => __( 'Seiten mit Befunden', 'wm-bfsg-compliance-dashboard' ),
							],
							[
								'value' => 'last_run',
								'label' => __( 'Letzter Lauf', 'wm-bfsg-compliance-dashboard' ),
							],
						],
					],
				],
				'return'      => [ $this, 'render_dynamic_tag' ],
			]
		);
	}

	/**
	 * Callback for WP 6.5+ Block Bindings (`wm-bfsg-dashboard/metrics`).
	 *
	 * @param array<string, mixed> $source_args Binding arguments (`key`).
	 * @param mixed                $block       WP_Block instance or null.
	 * @param string               $attr_name   Target block attribute name.
	 */
	public function get_block_binding_value( array $source_args, $block = null, string $attr_name = 'content' ): string {
		$key = isset( $source_args['key'] ) ? sanitize_key( (string) $source_args['key'] ) : 'compliance_level';
		return esc_html( $this->resolve_metric_value( $key ) );
	}

	/**
	 * Callback for GenerateBlocks 2.x `{{wm_bfsg_dashboard_metric}}`.
	 *
	 * @param array<string, mixed> $options Tag options.
	 * @param array<string, mixed> $block   Block array.
	 * @param mixed                $instance Dynamic tag instance.
	 */
	public function render_dynamic_tag( array $options = [], array $block = [], $instance = null ): string {
		$key = isset( $options['key'] ) ? sanitize_key( (string) $options['key'] ) : 'compliance_level';
		return esc_html( $this->resolve_metric_value( $key ) );
	}

	/**
	 * Filter hook for GenerateBlocks dynamic tag output when `wm_bfsg_dashboard_metric` is evaluated.
	 *
	 * @param string               $output  Current output.
	 * @param string               $tag     Dynamic tag name.
	 * @param array<string, mixed> $options Tag options.
	 */
	public function filter_generateblocks_output( string $output, string $tag = '', array $options = [] ): string {
		if ( self::GB_TAG !== $tag || '' !== $output ) {
			return $output;
		}
		return $this->render_dynamic_tag( $options );
	}

	public function resolve_metric_value( string $key ): string {
		$settings = AdminDashboard::settings();
		$audit    = AccessibilityAuditor::stored_results();
		$has_run  = isset( $audit['total_pages'] ) && (int) $audit['total_pages'] > 0;

		switch ( $key ) {
			case 'compliance_level':
				return in_array( $settings['compliance_level'], Anlage3Generator::COMPLIANCE_LEVELS, true )
					? $settings['compliance_level']
					: __( 'noch nicht angegeben', 'wm-bfsg-compliance-dashboard' );

			case 'assessment_date':
				$date = Anlage3Generator::display_date( $settings['assessment_date'] );
				return '' !== $date ? $date : __( 'noch nicht angegeben', 'wm-bfsg-compliance-dashboard' );

			case 'company_name':
				return $settings['company_name'];

			case 'feedback_email':
				return $settings['feedback_email'];

			case 'supervisory_body':
				return $settings['supervisory_body'];

			case 'pages_checked':
				return $has_run ? (string) (int) $audit['total_pages'] : '0';

			case 'pages_with_findings':
				return $has_run ? (string) (int) ( $audit['pages_with_findings'] ?? 0 ) : '0';

			case 'score':
				return ( $has_run && isset( $audit['overall_score'] ) && null !== $audit['overall_score'] )
					? (string) (int) $audit['overall_score']
					: __( 'nicht geprüft', 'wm-bfsg-compliance-dashboard' );

			case 'last_run':
				return $has_run ? (string) ( $audit['audited_at'] ?? '' ) : __( 'nie', 'wm-bfsg-compliance-dashboard' );

			default:
				return '';
		}
	}
}
