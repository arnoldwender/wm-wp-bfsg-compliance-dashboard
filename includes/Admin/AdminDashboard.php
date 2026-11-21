<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WMCyber\BFSG\Dashboard\Legal\Anlage3Generator;
use WMCyber\BFSG\Dashboard\Export\AuditPdfReport;
use WMCyber\BFSG\Dashboard\Scanner\AccessibilityAuditor;

/**
 * Admin screen and dashboard widget.
 *
 * Until 2026-09-14 (Spanish interface): the widget ran a full check on every dashboard load and wrote
 * the result (fleet audit D6); the header carried "DECLARACIÓN OFICIAL ACTIVA" and "DEFENSA LEGAL UWG";
 * the overview showed "Sello de Conformidad: CERTIFICADO" and told the operator to paste
 * [wm_bfsg_declaration], a shortcode nothing registers, onto the legal page (D2); the report tab
 * printed sha256('wm-bfsg-audit') as a "forensic fingerprint" because it read a key the report did not
 * have; the form defaulted to "Arnold Wender (Lead Compliance Auditor)", "+49 345 213890" and
 * "vollständig konform" and saved any compliance level string (D7).
 */
final class AdminDashboard {
	private static ?AdminDashboard $instance = null;

	/** The shortcode the overview tells the operator to use; registered in Frontend/Anlage3Shortcode. */
	public const SHORTCODE = 'wm_barrierefreiheitserklaerung';

	/** Option controlling whether uninstall.php deletes stored settings and audit results. */
	public const DELETE_ON_UNINSTALL_OPTION = 'wm_bfsg_dashboard_delete_data_on_uninstall';

	public static function instance(): AdminDashboard {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'save_settings' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
	}

	public function register_settings(): void {
		if ( function_exists( 'register_setting' ) ) {
			register_setting(
				'wm_bfsg_dashboard_settings',
				self::DELETE_ON_UNINSTALL_OPTION,
				[
					'type'              => 'string',
					'sanitize_callback' => static fn( $val ): string => filter_var( $val, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
					'default'           => '0',
				]
			);
		}
	}

	public static function delete_data_on_uninstall(): bool {
		return filter_var( get_option( 'wm_bfsg_dashboard_delete_data_on_uninstall', '0' ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Settings with honest defaults: nothing about the operator is invented.
	 *
	 * @return array<string, string>
	 */
	public static function settings(): array {
		$defaults = [
			'company_name'     => (string) get_bloginfo( 'name' ),
			'website_url'      => (string) home_url(),
			'compliance_level' => '',
			'assessment_date'  => '',
			'feedback_email'   => (string) get_option( 'admin_email' ),
			'feedback_phone'   => '',
			'officer_name'     => '',
			'supervisory_body' => 'Landesfachstelle für Barrierefreiheit Sachsen-Anhalt (Halle / Magdeburg)',
		];
		return array_merge( $defaults, array_map( 'strval', (array) get_option( 'wm_bfsg_declaration_settings', [] ) ) );
	}

	public function register_dashboard_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'wm_bfsg_compliance_widget',
			__( 'WM BFSG — Musterprüfung & Erklärung', 'wm-bfsg-compliance-dashboard' ),
			[ $this, 'render_dashboard_widget' ]
		);
	}

	/** Reads the last stored run; the check itself only runs on request. */
	public function render_dashboard_widget(): void {
		$audit    = AccessibilityAuditor::stored_results();
		$has_run  = isset( $audit['total_pages'] ) && (int) $audit['total_pages'] > 0;
		$flagged  = (int) ( $audit['pages_with_findings'] ?? 0 );
		$settings = self::settings();
		$level    = in_array( $settings['compliance_level'], Anlage3Generator::COMPLIANCE_LEVELS, true ) ? $settings['compliance_level'] : '';
		?>
		<div class="wm-dash-widget-box" data-wm-theme="<?php echo esc_attr( function_exists( 'wm_get_suite_theme' ) ? wm_get_suite_theme() : 'cyber-events' ); ?>">
			<?php // No green "is-valid" banner: three pattern checks without findings are no verdict. ?>
			<div class="wm-dash-banner">
				<span class="wm-dash-banner-icon"><span class="dashicons dashicons-universal-access-alt" aria-hidden="true"></span></span>
				<div>
					<div class="wm-dash-banner-title">
						<?php
						if ( ! $has_run ) {
							esc_html_e( 'Musterprüfung noch nicht ausgeführt', 'wm-bfsg-compliance-dashboard' );
						} else {
							/* translators: 1: items with findings, 2: items checked */
							echo esc_html( sprintf( __( 'Befunde auf %1$d von %2$d geprüften Seiten', 'wm-bfsg-compliance-dashboard' ), $flagged, (int) $audit['total_pages'] ) );
						}
						?>
					</div>
					<div class="wm-dash-banner-sub"><?php esc_html_e( 'Drei Musterprüfungen im gespeicherten Inhalt – kein WCAG-Audit', 'wm-bfsg-compliance-dashboard' ); ?></div>
				</div>
			</div>

			<div class="wm-dash-kpi-grid">
				<div class="wm-dash-kpi-card">
					<span class="wm-dash-kpi-val"><?php echo $has_run ? esc_html( (string) (int) $audit['total_pages'] ) : '—'; ?></span>
					<span class="wm-dash-kpi-lbl"><?php esc_html_e( 'Geprüfte Seiten', 'wm-bfsg-compliance-dashboard' ); ?></span>
				</div>
				<div class="wm-dash-kpi-card">
					<span class="wm-dash-kpi-val"><?php echo $has_run ? esc_html( (string) $flagged ) : '—'; ?></span>
					<span class="wm-dash-kpi-lbl"><?php esc_html_e( 'Mit Befunden', 'wm-bfsg-compliance-dashboard' ); ?></span>
				</div>
				<div class="wm-dash-kpi-card">
					<span class="wm-dash-kpi-val"><?php echo '' !== $level ? esc_html( $level ) : esc_html__( 'nicht angegeben', 'wm-bfsg-compliance-dashboard' ); ?></span>
					<span class="wm-dash-kpi-lbl"><?php esc_html_e( 'Erklärter Stand', 'wm-bfsg-compliance-dashboard' ); ?></span>
				</div>
			</div>

			<div class="wm-dash-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wm-bfsg-dashboard' ) ); ?>" class="wm-dash-btn">
					<span><?php esc_html_e( 'BFSG Dashboard öffnen', 'wm-bfsg-compliance-dashboard' ); ?></span> <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Family menu icon: a monochrome hub-and-spoke mark, filled shapes only so the WordPress
	 * svg-painter can recolour it with the admin colour scheme. Placeholder until the family
	 * icon is decided (hackaton plan D12).
	 */
	public static function family_menu_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><circle cx="10" cy="10" r="2.6" fill="black"/><circle cx="10" cy="3" r="1.8" fill="black"/><circle cx="16.1" cy="13.5" r="1.8" fill="black"/><circle cx="3.9" cy="13.5" r="1.8" fill="black"/><rect x="9.3" y="4.5" width="1.4" height="3.4" fill="black"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(-60 10 10)"/><rect x="9.3" y="12.1" width="1.4" height="3.4" fill="black" transform="rotate(60 10 10)"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'WM BFSG Dashboard', 'wm-bfsg-compliance-dashboard' ),
			__( 'BFSG Dashboard', 'wm-bfsg-compliance-dashboard' ),
			'manage_options',
			'wm-bfsg-dashboard',
			[ $this, 'render_dashboard' ],
			self::family_menu_icon(),
			82
		);
	}

	/** Family sheets on the plugin screen (standalone and under the hub) and on the dashboard for the widget. */
	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'wm-bfsg-dashboard' ) && 'index.php' !== $hook ) {
			return;
		}

		$root = dirname( __DIR__, 2 ) . '/assets/css/';
		wp_enqueue_style(
			'wm-admin-tokens',
			WM_BFSG_DASHBOARD_URL . 'assets/css/wm-admin-tokens.css',
			[],
			(string) ( @filemtime( $root . 'wm-admin-tokens.css' ) ?: WM_BFSG_DASHBOARD_VERSION )
		);
		wp_enqueue_style(
			'wm-admin-ui',
			WM_BFSG_DASHBOARD_URL . 'assets/css/wm-admin-ui.css',
			[ 'wm-admin-tokens' ],
			(string) ( @filemtime( $root . 'wm-admin-ui.css' ) ?: WM_BFSG_DASHBOARD_VERSION )
		);
	}

	public function save_settings(): void {
		$nonce = isset( $_POST['wm_bfsg_decl_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wm_bfsg_decl_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wm_bfsg_decl_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post  = wp_unslash( $_POST );
		$level = sanitize_text_field( (string) ( $post['compliance_level'] ?? '' ) );

		$settings = [
			'company_name'     => sanitize_text_field( (string) ( $post['company_name'] ?? '' ) ),
			'website_url'      => esc_url_raw( (string) ( $post['website_url'] ?? '' ) ),
			// Allowlist: only the three levels the form offers reach the published statement (D7).
			'compliance_level' => in_array( $level, Anlage3Generator::COMPLIANCE_LEVELS, true ) ? $level : '',
			// Y-m-d from the date field; anything else (or an impossible date) is stored as not stated.
			'assessment_date'  => '' !== Anlage3Generator::display_date( (string) ( $post['assessment_date'] ?? '' ) ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $post['assessment_date'] ?? '' ) ) ? (string) $post['assessment_date'] : '',
			'feedback_email'   => sanitize_email( (string) ( $post['feedback_email'] ?? '' ) ),
			'feedback_phone'   => sanitize_text_field( (string) ( $post['feedback_phone'] ?? '' ) ),
			'officer_name'     => sanitize_text_field( (string) ( $post['officer_name'] ?? '' ) ),
			'supervisory_body' => sanitize_text_field( (string) ( $post['supervisory_body'] ?? '' ) ),
		];

		$delete_on_uninstall = ! empty( $post['delete_data_on_uninstall'] ) && '1' === (string) $post['delete_data_on_uninstall'] ? '1' : '0';

		update_option( 'wm_bfsg_declaration_settings', $settings );
		update_option( 'wm_bfsg_dashboard_delete_data_on_uninstall', $delete_on_uninstall, false );
		wp_safe_redirect( add_query_arg( [ 'page' => 'wm-bfsg-dashboard', 'tab' => 'anlage3', 'updated' => 'true' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * @return array<string, array{label: string, icon: string}>
	 */
	private function tabs(): array {
		return [
			'overview'    => [ 'label' => __( 'Übersicht', 'wm-bfsg-compliance-dashboard' ), 'icon' => 'dashicons-chart-bar' ],
			'anlage3'     => [ 'label' => __( 'Erklärung', 'wm-bfsg-compliance-dashboard' ), 'icon' => 'dashicons-media-document' ],
			'certificate' => [ 'label' => __( 'Prüfbericht', 'wm-bfsg-compliance-dashboard' ), 'icon' => 'dashicons-list-view' ],
			'glossary'    => [ 'label' => __( 'Begriffe', 'wm-bfsg-compliance-dashboard' ), 'icon' => 'dashicons-book-alt' ],
		];
	}

	public function render_dashboard(): void {
		$tabs = $this->tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing, checked against the tab list.
		$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'overview' ) );
		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'overview';
		}

		$last_audit = AccessibilityAuditor::stored_results();
		$has_run    = isset( $last_audit['total_pages'] ) && (int) $last_audit['total_pages'] > 0;
		$settings   = self::settings();
		$level_set  = in_array( $settings['compliance_level'], Anlage3Generator::COMPLIANCE_LEVELS, true );

		$declaration_html = ( new Anlage3Generator() )->generate_declaration( $settings );
		$report           = ( new AuditPdfReport() )->generate_certificate_data( $last_audit );
		?>
		<div class="wrap wm-admin">
			<!-- Header -->
			<header class="wm-admin-header">
				<div class="wm-header-brand">
					<div class="wm-header-icon">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
					</div>
					<div>
						<h1><?php esc_html_e( 'WM BFSG — Erklärung zur Barrierefreiheit', 'wm-bfsg-compliance-dashboard' ); ?></h1>
						<p class="wm-subtitle"><?php esc_html_e( 'Erklärung aus Ihren Angaben erzeugen &bull; drei Musterprüfungen im gespeicherten Inhalt', 'wm-bfsg-compliance-dashboard' ); ?></p>
					</div>
				</div>
				<div class="wm-header-badges">
					<span class="wm-badge-family" title="<?php esc_attr_e( 'Part of the WM Suite plugin family', 'wm-bfsg-compliance-dashboard' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="4" r="2"/><circle cx="19" cy="16" r="2"/><circle cx="5" cy="16" r="2"/><path d="M12 7v2M13.9 13.2l3.4 2M10.1 13.2l-3.4 2"/></svg>
						<?php esc_html_e( 'WM Suite', 'wm-bfsg-compliance-dashboard' ); ?>
						<span class="wm-badge-family-state"><?php echo defined( 'WM_SUITE_HUB_VERSION' ) ? esc_html__( 'Hub', 'wm-bfsg-compliance-dashboard' ) : esc_html__( 'Standalone', 'wm-bfsg-compliance-dashboard' ); ?></span>
					</span>
					<?php if ( ! $level_set ) : ?>
					<span class="wm-badge wm-badge-amber"><?php esc_html_e( 'STAND DER VEREINBARKEIT NICHT ANGEGEBEN', 'wm-bfsg-compliance-dashboard' ); ?></span>
					<?php endif; ?>
				</div>
			</header>

			<div class="wm-quick-guide">
				<div class="wm-quick-guide-title">
					<span><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span> <?php esc_html_e( 'Was dieses Plugin tut', 'wm-bfsg-compliance-dashboard' ); ?></span>
				</div>
				<p class="wm-quick-guide-desc">
					<?php esc_html_e( 'Es erzeugt eine Erklärung zur Barrierefreiheit aus Ihren Angaben (Stand der Vereinbarkeit als Selbstbewertung, Datum, Kontakt, Durchsetzungsstelle) und gibt sie per Shortcode auf einer Seite aus. Zusätzlich prüft es den gespeicherten Inhalt von bis zu 20 veröffentlichten Seiten, Beiträgen und Produkten auf drei Muster. Die Musterprüfung ersetzt keine Prüfung der Website nach WCAG oder EN 301 549, und das Plugin beurteilt nicht, ob und wie das BFSG für Ihr Angebot gilt.', 'wm-bfsg-compliance-dashboard' ); ?>
				</p>
			</div>

			<!-- Tabs -->
			<nav class="wm-nav-tabs" aria-label="<?php esc_attr_e( 'Bereiche', 'wm-bfsg-compliance-dashboard' ); ?>">
				<?php foreach ( $tabs as $slug => $tab ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'wm-bfsg-dashboard', 'tab' => $slug ], admin_url( 'admin.php' ) ) ); ?>" class="wm-tab-link <?php echo $active_tab === $slug ? 'active' : ''; ?>"<?php echo $active_tab === $slug ? ' aria-current="page"' : ''; ?>>
					<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span> <?php echo esc_html( $tab['label'] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'overview' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-grid-4">
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Erklärter Stand', 'wm-bfsg-compliance-dashboard' ); ?></span>
						<span class="wm-stat-value text-cyan"><?php echo $level_set ? esc_html( $settings['compliance_level'] ) : '—'; ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'Selbstbewertung, im Tab „Erklärung“', 'wm-bfsg-compliance-dashboard' ); ?></span>
					</div>
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Geprüfte Seiten', 'wm-bfsg-compliance-dashboard' ); ?></span>
						<span class="wm-stat-value text-emerald"><?php echo $has_run ? esc_html( (string) (int) $last_audit['total_pages'] ) : '—'; ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'höchstens 20 je Lauf', 'wm-bfsg-compliance-dashboard' ); ?></span>
					</div>
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Seiten mit Befunden', 'wm-bfsg-compliance-dashboard' ); ?></span>
						<span class="wm-stat-value text-amber"><?php echo $has_run ? esc_html( (string) (int) ( $last_audit['pages_with_findings'] ?? 0 ) ) : '—'; ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'img ohne alt, leere Links, Überschriftensprünge', 'wm-bfsg-compliance-dashboard' ); ?></span>
					</div>
					<div class="wm-stat-card">
						<span class="wm-stat-label"><?php esc_html_e( 'Letzter Lauf', 'wm-bfsg-compliance-dashboard' ); ?></span>
						<span class="wm-stat-value text-purple" style="font-size: var(--wm-text-base, 1rem);"><?php echo $has_run ? esc_html( (string) $last_audit['audited_at'] ) : esc_html__( 'nie', 'wm-bfsg-compliance-dashboard' ); ?></span>
						<span class="wm-stat-delta"><?php esc_html_e( 'nur auf Anforderung', 'wm-bfsg-compliance-dashboard' ); ?></span>
					</div>
				</div>

				<div class="wm-card" style="margin-top: 24px;">
					<h2><?php esc_html_e( 'Erklärung veröffentlichen', 'wm-bfsg-compliance-dashboard' ); ?></h2>
					<ol>
						<li><?php esc_html_e( 'Im Tab „Erklärung“ Stand der Vereinbarkeit, Kontakt und Durchsetzungsstelle eintragen.', 'wm-bfsg-compliance-dashboard' ); ?></li>
						<li><?php esc_html_e( 'Eine Seite anlegen (z. B. „Erklärung zur Barrierefreiheit“) und diesen Shortcode einfügen:', 'wm-bfsg-compliance-dashboard' ); ?> <code>[<?php echo esc_html( self::SHORTCODE ); ?>]</code></li>
						<li><?php esc_html_e( 'Die Seite im Footer verlinken.', 'wm-bfsg-compliance-dashboard' ); ?></li>
					</ol>
					<p>
						<button type="button" class="button button-primary" id="wm-bfsg-run-audit"><?php esc_html_e( 'Musterprüfung jetzt ausführen', 'wm-bfsg-compliance-dashboard' ); ?></button>
					</p>
					<div id="wm-audit-live-status" aria-live="polite"></div>
				</div>

				<script>
				( function () {
					const box = document.getElementById( 'wm-audit-live-status' );
					function note( cls, text ) {
						box.className = 'notice inline ' + cls;
						const p = document.createElement( 'p' );
						p.textContent = text;
						box.replaceChildren( p );
					}
					document.getElementById( 'wm-bfsg-run-audit' ).addEventListener( 'click', function () {
						note( 'notice-info', <?php echo wp_json_encode( __( 'Prüfe gespeicherte Inhalte …', 'wm-bfsg-compliance-dashboard' ) ); ?> );
						fetch( <?php echo wp_json_encode( esc_url_raw( rest_url( 'wm-bfsg-dashboard/v1/run-audit' ) ) ); ?>, {
							method: 'POST',
							credentials: 'same-origin',
							headers: { 'X-WP-Nonce': <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>, 'Content-Type': 'application/json' }
						} )
						.then( function ( res ) { if ( ! res.ok ) { throw new Error( 'HTTP ' + res.status ); } return res.json(); } )
						.then( function ( data ) {
							const d = data.data || {};
							if ( ! data.success ) {
								note( 'notice-warning', <?php echo wp_json_encode( __( 'Keine veröffentlichten Seiten, Beiträge oder Produkte gefunden: nichts geprüft.', 'wm-bfsg-compliance-dashboard' ) ); ?> );
								return;
							}
							note( 'notice-success', <?php echo wp_json_encode( __( 'Fertig. Geprüfte Seiten:', 'wm-bfsg-compliance-dashboard' ) ); ?> + ' ' + d.total_pages + ' · ' + <?php echo wp_json_encode( __( 'mit Befunden:', 'wm-bfsg-compliance-dashboard' ) ); ?> + ' ' + d.pages_with_findings );
							setTimeout( function () { location.reload(); }, 1500 );
						} )
						.catch( function ( err ) { note( 'notice-error', <?php echo wp_json_encode( __( 'Fehler:', 'wm-bfsg-compliance-dashboard' ) ); ?> + ' ' + err.message ); } );
					} );
				} )();
				</script>
			</div>

			<?php elseif ( 'anlage3' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-grid-2">
					<div class="wm-card">
						<h2><?php esc_html_e( 'Angaben für die Erklärung', 'wm-bfsg-compliance-dashboard' ); ?></h2>
						<form method="post" action="">
							<?php wp_nonce_field( 'wm_bfsg_decl_action', 'wm_bfsg_decl_nonce' ); ?>
							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="wm-bfsg-company"><?php esc_html_e( 'Name des Unternehmens', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td><input type="text" id="wm-bfsg-company" name="company_name" class="regular-text" value="<?php echo esc_attr( $settings['company_name'] ); ?>" required /></td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-url"><?php esc_html_e( 'Adresse der Website', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td><input type="url" id="wm-bfsg-url" name="website_url" class="regular-text" value="<?php echo esc_attr( $settings['website_url'] ); ?>" required /></td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-level"><?php esc_html_e( 'Stand der Vereinbarkeit', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td>
										<?php
										// No option is recommended and none is preselected: an inaccurate "vollständig
										// konform" is the operator's exposure, and the three pattern checks do not
										// establish any level.
										?>
										<select id="wm-bfsg-level" name="compliance_level" aria-describedby="wm-bfsg-level-desc">
											<option value="" <?php selected( $settings['compliance_level'], '' ); ?>><?php esc_html_e( '— bitte wählen —', 'wm-bfsg-compliance-dashboard' ); ?></option>
											<?php foreach ( Anlage3Generator::COMPLIANCE_LEVELS as $level ) : ?>
											<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $settings['compliance_level'], $level ); ?>><?php echo esc_html( $level ); ?></option>
											<?php endforeach; ?>
										</select>
										<p class="description" id="wm-bfsg-level-desc"><?php esc_html_e( 'Ihre Selbstbewertung. Erklären Sie nur, was eine Prüfung Ihrer Website trägt.', 'wm-bfsg-compliance-dashboard' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-date"><?php esc_html_e( 'Datum der Selbstbewertung', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td>
										<?php
										// Prefilled with today only while nothing is stored, visible and editable before saving.
										$date_value = '' !== $settings['assessment_date'] ? $settings['assessment_date'] : wp_date( 'Y-m-d' );
										?>
										<input type="date" id="wm-bfsg-date" name="assessment_date" value="<?php echo esc_attr( $date_value ); ?>" aria-describedby="wm-bfsg-date-desc" />
										<p class="description" id="wm-bfsg-date-desc"><?php esc_html_e( 'Die Erklärung nennt dieses Datum als Erstellungsdatum. Ohne gespeichertes Datum steht dort „noch nicht angegeben“.', 'wm-bfsg-compliance-dashboard' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-officer"><?php esc_html_e( 'Ansprechperson (optional)', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td><input type="text" id="wm-bfsg-officer" name="officer_name" class="regular-text" value="<?php echo esc_attr( $settings['officer_name'] ); ?>" /></td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-email"><?php esc_html_e( 'E-Mail für Rückmeldungen', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td><input type="email" id="wm-bfsg-email" name="feedback_email" class="regular-text" value="<?php echo esc_attr( $settings['feedback_email'] ); ?>" required /></td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-phone"><?php esc_html_e( 'Telefon (optional)', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td><input type="text" id="wm-bfsg-phone" name="feedback_phone" class="regular-text" value="<?php echo esc_attr( $settings['feedback_phone'] ); ?>" /></td>
								</tr>
								<tr>
									<th scope="row"><label for="wm-bfsg-authority"><?php esc_html_e( 'Durchsetzungsstelle', 'wm-bfsg-compliance-dashboard' ); ?></label></th>
									<td>
										<input type="text" id="wm-bfsg-authority" name="supervisory_body" class="regular-text" value="<?php echo esc_attr( $settings['supervisory_body'] ); ?>" required aria-describedby="wm-bfsg-authority-desc" />
										<p class="description" id="wm-bfsg-authority-desc"><?php esc_html_e( 'Hinweis: Die Erklärung druckt unter diesem Namen derzeit immer eine Zeile und eine E-Mail-Adresse der Sachsen-Anhalt-Stelle.', 'wm-bfsg-compliance-dashboard' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Deinstallation', 'wm-bfsg-compliance-dashboard' ); ?></th>
									<td>
										<label for="wm-bfsg-delete-on-uninstall">
											<input type="checkbox" id="wm-bfsg-delete-on-uninstall" name="delete_data_on_uninstall" value="1" <?php checked( self::delete_data_on_uninstall() ); ?> />
											<?php esc_html_e( 'Gespeicherte Angaben und Prüfbericht beim Löschen des Plugins entfernen', 'wm-bfsg-compliance-dashboard' ); ?>
										</label>
										<p class="description"><?php esc_html_e( 'Standardmäßig bleiben die Angaben zur Erklärung und der letzte Prüfbericht bei der Deinstallation erhalten.', 'wm-bfsg-compliance-dashboard' ); ?></p>
									</td>
								</tr>
							</table>
							<?php submit_button( __( 'Speichern', 'wm-bfsg-compliance-dashboard' ) ); ?>
						</form>
					</div>

					<div class="wm-card">
						<h2><?php esc_html_e( 'Vorschau', 'wm-bfsg-compliance-dashboard' ); ?></h2>
						<div style="max-height:560px; overflow-y:auto;">
							<?php echo wp_kses_post( $declaration_html ); ?>
						</div>
					</div>
				</div>
			</div>

			<?php elseif ( 'certificate' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><?php esc_html_e( 'Prüfbericht der letzten Musterprüfung', 'wm-bfsg-compliance-dashboard' ); ?></h2>
					<?php if ( ! $has_run ) : ?>
					<p><?php esc_html_e( 'Noch keine Musterprüfung mit geprüften Inhalten. Im Tab „Übersicht“ ausführen.', 'wm-bfsg-compliance-dashboard' ); ?></p>
					<?php else : ?>
					<table class="widefat striped" style="margin-bottom: 16px;">
						<tbody>
							<tr><th scope="row"><?php esc_html_e( 'Kennung des Laufs', 'wm-bfsg-compliance-dashboard' ); ?></th><td><code><?php echo esc_html( $report['report_id'] ); ?></code></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Zeitpunkt', 'wm-bfsg-compliance-dashboard' ); ?></th><td><?php echo esc_html( $report['timestamp'] ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Prüfungen', 'wm-bfsg-compliance-dashboard' ); ?></th><td><?php echo esc_html( $report['checks'] ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Ergebnis', 'wm-bfsg-compliance-dashboard' ); ?></th><td><?php echo esc_html( $report['result'] ); ?></td></tr>
						</tbody>
					</table>
					<p class="description"><?php esc_html_e( 'Kein Zertifikat und keine Aussage über Rechtskonformität: der Bericht nennt nur, was die drei Muster gefunden haben.', 'wm-bfsg-compliance-dashboard' ); ?></p>

					<table class="widefat striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Seite', 'wm-bfsg-compliance-dashboard' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Punkte', 'wm-bfsg-compliance-dashboard' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Befunde', 'wm-bfsg-compliance-dashboard' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( (array) ( $last_audit['findings'] ?? [] ) as $finding ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( (string) $finding['url'] ); ?>"><?php echo esc_html( (string) $finding['title'] ); ?></a></td>
								<td><?php echo esc_html( (string) (int) $finding['score'] ); ?></td>
								<td><?php echo [] === $finding['issues'] ? '—' : esc_html( implode( '; ', (array) $finding['issues'] ) ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php endif; ?>
				</div>
			</div>

			<?php elseif ( 'glossary' === $active_tab ) : ?>
			<div class="wm-tab-content">
				<div class="wm-card">
					<h2><?php esc_html_e( 'Begriffe', 'wm-bfsg-compliance-dashboard' ); ?></h2>
					<div class="wm-glossary-grid">
						<div class="wm-glossary-card">
							<div class="wm-glossary-term"><?php esc_html_e( 'Erklärung zur Barrierefreiheit', 'wm-bfsg-compliance-dashboard' ); ?></div>
							<p class="wm-glossary-def"><?php esc_html_e( 'Der Text, den dieses Plugin erzeugt: Stand der Vereinbarkeit (Ihre Selbstbewertung), Erstellungsdatum, Kontakt für Rückmeldungen und Durchsetzungsstelle. Ob und in welcher Form Ihr Angebot eine solche Erklärung braucht, beurteilt das Plugin nicht.', 'wm-bfsg-compliance-dashboard' ); ?></p>
						</div>
						<div class="wm-glossary-card">
							<div class="wm-glossary-term"><?php esc_html_e( 'WCAG 2.2 / EN 301 549', 'wm-bfsg-compliance-dashboard' ); ?></div>
							<p class="wm-glossary-def"><?php esc_html_e( 'Technische Anforderungskataloge für barrierefreie Webinhalte. Eine Prüfung danach umfasst weit mehr als die drei Muster dieses Plugins.', 'wm-bfsg-compliance-dashboard' ); ?></p>
						</div>
						<div class="wm-glossary-card">
							<div class="wm-glossary-term"><?php esc_html_e( 'Musterprüfung', 'wm-bfsg-compliance-dashboard' ); ?></div>
							<p class="wm-glossary-def"><?php esc_html_e( 'Sucht im gespeicherten Inhalt nach img-Elementen ohne alt, Links ohne Text und übersprungenen Überschriftenebenen. Theme, Menüs, Formulare, Kontraste und Tastaturbedienung sieht sie nicht.', 'wm-bfsg-compliance-dashboard' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
