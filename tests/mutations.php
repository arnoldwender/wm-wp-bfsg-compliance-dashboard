<?php
/**
 * Mutations for tests/run-mutations.sh: each entry undoes one correction with literal replacements
 * [ file, search, replace ]; tests/test-suite.php must turn red for every one of them.
 *
 * Single-quoted strings only: a double-quoted search string would interpolate "$" and never match.
 *
 * @package WMCyber\BFSG\Dashboard
 */

declare(strict_types=1);

return [
	[
		'the statement prints the day of the page view as its creation date again',
		[
			[ 'includes/Legal/Anlage3Generator.php', '$assessment_date  = self::display_date( (string) ( $overrides[\'assessment_date\'] ?? \'\' ) );', '$assessment_date  = (string) ( $overrides[\'assessment_date\'] ?? wp_date( \'d.m.Y\' ) );' ],
		],
	],
	[
		'the shortcode drops the saved date',
		[
			[ 'includes/Frontend/Anlage3Shortcode.php', '\'assessment_date\'  => (string) ( $settings[\'assessment_date\'] ?? \'\' ),', '' ],
		],
	],
	[
		'the settings form stores any date text',
		[
			[ 'includes/Admin/AdminDashboard.php', '\'assessment_date\'  => \'\' !== Anlage3Generator::display_date( (string) ( $post[\'assessment_date\'] ?? \'\' ) ) && 1 === preg_match( \'/^\\d{4}-\\d{2}-\\d{2}$/\', (string) ( $post[\'assessment_date\'] ?? \'\' ) ) ? (string) $post[\'assessment_date\'] : \'\',', '\'assessment_date\'  => (string) ( $post[\'assessment_date\'] ?? \'\' ),' ],
		],
	],
	[
		'sticky posts go in front of the sample again',
		[
			[ 'includes/Scanner/AccessibilityAuditor.php', '\'ignore_sticky_posts\' => true,', '\'ignore_sticky_posts\' => false,' ],
		],
	],
	[
		'data-alt counts as an alt attribute again',
		[
			[ 'includes/Scanner/AccessibilityAuditor.php', '\'/<\' . \'img(?![^>]*\\salt\\s*=)[^>]*>/i\'', '\'/<\' . \'img(?![^>]*\\balt=)[^>]*>/i\'' ],
		],
	],
	[
		'a link named by aria-label counts as empty again',
		[
			[ 'includes/Scanner/AccessibilityAuditor.php', '\'/<a\\b(?![^>]*\\s(?:aria-label|aria-labelledby|title)\\s*=\\s*["\\\'][^"\\\']*\\S)[^>]*>(?:\\s|&nbsp;)*<\\/a>/i\'', '\'/<a\\b[^>]*>(?:\\s*|&nbsp;)*<\\/a>/i\'' ],
		],
	],
	[
		'a page with findings is labelled compliant again',
		[
			[ 'includes/Scanner/AccessibilityAuditor.php', '\'status\' => [] === $issues ? \'no_findings\' : \'findings\',', '\'status\' => $score >= 90 ? \'compliant\' : \'critical_violations\',' ],
		],
	],
	[
		'the widget shows a green is-valid banner again',
		[
			[ 'includes/Admin/AdminDashboard.php', '<div class="wm-dash-banner">', '<div class="wm-dash-banner <?php echo ( $has_run && 0 === $flagged ) ? \'is-valid\' : \'\'; ?>">' ],
		],
	],
	[
		'the integration falsifier exits 0 with failures again',
		[
			[ 'tests/test-anlage3-claims.php', 'exit( $GLOBALS[\'wm_b_failed\'] > 0 ? 1 : 0 );', '' ],
		],
	],
	[
		'an unconfigured statement claims full conformity again',
		[
			[ 'includes/Legal/Anlage3Generator.php', '$compliance_level = (string) ( $overrides[\'compliance_level\'] ?? \'\' );', '$compliance_level = (string) ( $overrides[\'compliance_level\'] ?? \'vollständig konform\' );' ],
		],
	],
	[
		'uninstall deletes stored settings and audit reports without explicit opt-in',
		[
			[ 'uninstall.php', 'if ( ! $wm_bfsg_delete_data ) {', 'if ( false ) {' ],
		],
	],
	[
		'the Gutenberg block loses its editorScript in block.json',
		[
			[ 'blocks/anlage-3/block.json', '"editorScript": "wm-bfsg-dashboard-blocks-editor"', '"editorScript": ""' ],
		],
	],
	[
		'the declaration generator bypasses the wm_bfsg_dashboard_declaration_html filter',
		[
			[ 'includes/Legal/Anlage3Generator.php', '\'wm_bfsg_dashboard_declaration_html\',', '\'wm_bfsg_dashboard_declaration_html_disabled\',' ],
		],
	],
	[
		'the admin preview outputs unescaped declaration HTML without wp_kses_post',
		[
			[ 'includes/Admin/AdminDashboard.php', '<?php echo wp_kses_post( $declaration_html ); ?>', '<?php echo $declaration_html; ?>' ],
		],
	],
];
