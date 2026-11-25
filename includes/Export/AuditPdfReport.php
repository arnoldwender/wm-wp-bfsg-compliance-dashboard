<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Export;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Summary of the last pattern-check run, as shown on the report tab and returned by
 * `wm-bfsg-dashboard/v1/audit-certificate`.
 *
 * No PDF is generated (the class name predates this). Until 2026-09-14 the summary called itself a
 * certificate, named a "Werbeagentur WM Suite BFSG Compliance Engine" as issuer, set
 * `legal_status` to "RECHTSKONFORM (Compliant)" from a score of 90 over three regex checks and carried
 * a SHA-256 over four values printed next to it. A legal verdict does not come out of a pattern match,
 * and a hash over public values attests to nothing (fleet audit 2026-09-13, bfsg-dashboard D4). Whether
 * the report should become a real document or disappear is Arnold's decision; until then it only says
 * what was checked.
 */
final class AuditPdfReport {
	/**
	 * @param array<string, mixed> $audit_results Result of AccessibilityAuditor::run_audit().
	 * @return array{report_id: string, generator: string, site_url: string, timestamp: string, score: ?int, pages_checked: int, pages_with_findings: int, checks: string, result: string}
	 */
	public function generate_certificate_data( array $audit_results ): array {
		$site_url  = home_url();
		$timestamp = (string) ( $audit_results['audited_at'] ?? current_time( 'mysql' ) );
		$score     = isset( $audit_results['overall_score'] ) ? (int) $audit_results['overall_score'] : null;
		$pages     = (int) ( $audit_results['total_pages'] ?? 0 );
		$flagged   = (int) ( $audit_results['pages_with_findings'] ?? 0 );

		if ( 0 === $pages ) {
			$result = 'Keine Inhalte geprüft';
		} elseif ( 0 === $flagged ) {
			$result = 'Keine Befunde in den drei Musterprüfungen';
		} else {
			$result = sprintf( 'Befunde auf %d von %d geprüften Seiten', $flagged, $pages );
		}

		$report = [
			// An identifier for this run, not a seal: anyone can recompute it from the values beside it.
			'report_id'           => 'BFSG-' . substr( hash( 'sha256', "{$site_url}|{$timestamp}|{$pages}|{$flagged}" ), 0, 16 ),
			'generator'           => 'WM BFSG Compliance Dashboard (Plugin)',
			'site_url'            => $site_url,
			'timestamp'           => $timestamp,
			'score'               => $score,
			'pages_checked'       => $pages,
			'pages_with_findings' => $flagged,
			'checks'              => 'img ohne alt (WCAG 1.1.1), leere Links (WCAG 2.4.4), übersprungene Überschriftenebenen (WCAG 1.3.1) im gespeicherten Inhalt von bis zu 20 veröffentlichten Seiten, Beiträgen und Produkten',
			'result'              => $result,
		];

		/**
		 * Filters the summary array of the stored pattern-check run.
		 *
		 * @param array<string, mixed> $report        Summary data.
		 * @param array<string, mixed> $audit_results Raw stored audit results.
		 */
		$filtered = apply_filters( 'wm_bfsg_dashboard_audit_report', $report, $audit_results );
		return is_array( $filtered ) ? $filtered : $report;
	}
}
