<?php
/**
 * Falsifier for the generated Barrierefreiheitserklärung (BFSG Anlage 3).
 *
 * The statement this plugin writes into the operator's own public legal page used to end with:
 *
 *     "Zertifiziert und kontinuierlich überwacht durch WM Enterprise Accessibility
 *      Cloud • SHA-256 Validierungs-Hash: <hash>"
 *
 * Four claims in one sentence, none of them backed:
 *   - nothing certifies anything (and a certification claim inside a self-assessment undermines
 *     the assessment and is a UWG § 5 exposure for the operator, not for us);
 *   - nothing monitors anything continuously — there is no scan and no cron;
 *   - "WM Enterprise Accessibility Cloud" is not a service that exists;
 *   - the hash was sha256( website_url . assessment_date ), two values printed a few lines above.
 *     Recomputable by anyone, attesting to nothing, inside a legal document.
 *
 * Run: wp eval-file wp-content/plugins/wm-bfsg-compliance-dashboard/tests/test-anlage3-claims.php --allow-root
 *
 * @package WMCyber\BFSG
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "Must run inside WordPress via WP-CLI.\n";
	exit( 1 );
}

// eval-file runs the file inside a function, so `global` would not bind for the helper below.
$GLOBALS['wm_b_passed'] = 0;
$GLOBALS['wm_b_failed'] = 0;

/**
 * Assert helper.
 *
 * @param bool   $condition Condition.
 * @param string $label     Description.
 * @return void
 */
function wm_b_assert( $condition, $label ) {
	if ( $condition ) {
		++$GLOBALS['wm_b_passed'];
		echo "  [PASS] {$label}\n";
	} else {
		++$GLOBALS['wm_b_failed'];
		echo "  [FAIL] {$label}\n";
	}
}

echo "\n=== BFSG ANLAGE 3 CLAIM TEST ===\n\n";

$generator = new \WMCyber\BFSG\Dashboard\Legal\Anlage3Generator();
$html      = $generator->generate_declaration();

wm_b_assert( is_string( $html ) && '' !== $html, 'the generator produces a statement' );

// --- The four unbacked claims must not reach the operator's legal page -----
wm_b_assert( false === stripos( $html, 'Zertifiziert und kontinuierlich' ), 'the statement no longer certifies itself' );
wm_b_assert( false === stripos( $html, 'Accessibility Cloud' ), 'it no longer names a service that does not exist' );
wm_b_assert( false === stripos( $html, 'Validierungs-Hash' ), 'it no longer prints a checksum that attests to nothing' );

// --- No test procedure the plugin does not run, no invented contact (2026-09-14) ---
wm_b_assert( false === stripos( $html, 'AST-Analyse' ) && false === stripos( $html, 'BITV-Test' ), 'it no longer claims an AST analysis or a BITV test' );
wm_b_assert( false === stripos( $html, 'Compliance Officer' ) && false === strpos( $html, '+49 345 000000' ), 'an unconfigured statement names no invented contact person or phone number' );
wm_b_assert( false === stripos( $html, 'vollständig konform' ) && false !== stripos( $html, 'noch nicht angegeben' ), 'an unconfigured statement claims no conformity level' );
wm_b_assert( false !== strpos( $html, '<a href="' . esc_url( home_url() ) . '"' ), 'the website link is real markup, not escaped text' );

// --- What it does say has to be true --------------------------------------
wm_b_assert( false !== stripos( $html, 'Selbstbewertung' ), 'it says the statement rests on a self-assessment' );
wm_b_assert( false === strpos( $html, wp_date( 'd.m.Y' ) ) && false !== strpos( $html, 'Erstellungsdatum ist noch nicht angegeben' ), 'without a saved date it names no creation date (it printed the day of the page view)' );
wm_b_assert(
	false !== stripos( $html, 'Zertifizierung durch eine externe Stelle liegt ihr nicht zugrunde' ),
	'and says explicitly that no external certification underlies it'
);

// --- The statutory parts must still be there ------------------------------
wm_b_assert( false !== stripos( $html, 'Durchsetzungsverfahren' ), 'the enforcement-procedure section survives' );
wm_b_assert( false !== stripos( $html, 'barrierefreiheit@ms.sachsen-anhalt.de' ), 'the supervisory body contact survives' );

// --- The admin no longer recommends the strongest claim -------------------
$admin_code = implode(
	"\n",
	array_filter(
		file( WM_BFSG_DASHBOARD_PATH . 'includes/Admin/AdminDashboard.php', FILE_IGNORE_NEW_LINES ),
		static function ( $line ) {
			$t = ltrim( $line );
			return '' !== $t && 0 !== strpos( $t, '*' ) && 0 !== strpos( $t, '//' ) && 0 !== strpos( $t, '/*' );
		}
	)
);
wm_b_assert(
	false === strpos( $admin_code, '[Recomendado]' ),
	'the compliance selector no longer recommends declaring full conformance'
);

echo "\n=== RESULT: {$GLOBALS['wm_b_passed']} passed, {$GLOBALS['wm_b_failed']} failed ===\n\n";

// wp-plugin-fleet-runtime.sh reads the exit code: until 2026-09-15 this file exited 0 with failures.
exit( $GLOBALS['wm_b_failed'] > 0 ? 1 : 0 );
