<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Three pattern checks over the stored content of up to 20 published pages, posts and products.
 *
 * What it is not: an audit of the rendered site (theme, menus, widgets, forms and scripts are never
 * seen), nor a test against WCAG 2.2 AA or EN 301 549. Until 2026-09-14 a score of 90 or more was
 * labelled "EN 301 549 Level AA Passed", an empty site scored 100, and the admin buttons promised a
 * scan of the whole site (fleet audit 2026-09-13, bfsg-dashboard D5).
 */
final class AccessibilityAuditor {
	/** Posts looked at per run. */
	public const SAMPLE_LIMIT = 20;

	/** Option holding the last run. */
	public const OPTION = 'wm_bfsg_last_audit_results';

	/**
	 * The stored run, or [] when there is none or it predates 2026-09-14.
	 *
	 * Runs stored by earlier versions carry `compliance_state` "EN 301 549 Level AA Passed" and no
	 * `pages_with_findings`; read as they are, that old verdict would reach the hub telemetry and the
	 * report would say "no findings" for lack of the count. A run without the current shape counts as
	 * not run, so every reader asks for a new one.
	 *
	 * @return array<string, mixed>
	 */
	public static function stored_results(): array {
		$stored = get_option( self::OPTION, [] );
		if ( ! is_array( $stored ) || ! array_key_exists( 'pages_with_findings', $stored ) || ! array_key_exists( 'sample_limit', $stored ) ) {
			return [];
		}
		return $stored;
	}

	/**
	 * Run the three checks and store the result in `wm_bfsg_last_audit_results`.
	 *
	 * @return array{score: ?int, overall_score: ?int, total_pages: int, pages_with_findings: int, sample_limit: int, compliance_state: string, audited_at: string, findings: list<array<string, mixed>>}
	 */
	public function run_audit(): array {
		// ignore_sticky_posts: outside wp-admin (REST, WP-CLI) WP_Query treats this as the home query and puts
		// sticky posts in front, beyond the limit and out of date order (until 2026-09-15).
		$query = new \WP_Query( [
			'post_type'           => [ 'page', 'post', 'product' ],
			'post_status'         => 'publish',
			'posts_per_page'      => self::SAMPLE_LIMIT,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		] );

		$total_pages = 0;
		$total_score = 0;
		$with_issues = 0;
		$findings    = [];

		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof \WP_Post ) ) {
				continue;
			}

			$total_pages++;
			$content = $post->post_content;
			$issues  = [];
			$score   = 100;

			// Check 1: img elements without an alt attribute (an empty alt="" counts as present: decorative). The attribute
			// needs whitespace before it and may have spaces around "=": `\balt=` matched data-alt= and missed alt = "x".
			if ( preg_match_all( '/<' . 'img(?![^>]*\salt\s*=)[^>]*>/i', $content, $matches ) ) {
				$count = count( $matches[0] );
				/* translators: %d: number of images */
				$issues[] = sprintf( _n( '%d Bild ohne alt-Attribut (WCAG 1.1.1)', '%d Bilder ohne alt-Attribut (WCAG 1.1.1)', $count, 'wm-bfsg-compliance-dashboard' ), $count );
				$score -= min( 30, $count * 10 );
			}

			// Check 2: links with no text at all and no aria-label, aria-labelledby or title (those name the link).
			if ( preg_match_all( '/<a\b(?![^>]*\s(?:aria-label|aria-labelledby|title)\s*=\s*["\'][^"\']*\S)[^>]*>(?:\s|&nbsp;)*<\/a>/i', $content, $matches ) ) {
				$count = count( $matches[0] );
				/* translators: %d: number of links */
				$issues[] = sprintf( _n( '%d Link ohne Text (WCAG 2.4.4)', '%d Links ohne Text (WCAG 2.4.4)', $count, 'wm-bfsg-compliance-dashboard' ), $count );
				$score -= min( 20, $count * 10 );
			}

			// Check 3: a heading level skipped downwards (h2 followed by h4).
			if ( preg_match_all( '/<h([1-6])\b/i', $content, $matches ) ) {
				$levels = array_map( 'intval', $matches[1] );
				$prev = 0;
				$skipped = 0;
				foreach ( $levels as $lvl ) {
					if ( $prev > 0 && $lvl > ( $prev + 1 ) ) {
						$skipped++;
					}
					$prev = $lvl;
				}
				if ( $skipped > 0 ) {
					/* translators: %d: number of skipped heading levels */
					$issues[] = sprintf( _n( '%d übersprungene Überschriftenebene (WCAG 1.3.1)', '%d übersprungene Überschriftenebenen (WCAG 1.3.1)', $skipped, 'wm-bfsg-compliance-dashboard' ), $skipped );
					$score -= min( 20, $skipped * 10 );
				}
			}

			$score = max( 0, $score );
			$total_score += $score;
			if ( [] !== $issues ) {
				$with_issues++;
			}

			$findings[] = [
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'url'    => get_permalink( $post->ID ),
				'score'  => $score,
				'issues' => $issues,
				// Findings or none; until 2026-09-15 a page with one image without alt scored 90 and was labelled "compliant".
				'status' => [] === $issues ? 'no_findings' : 'findings',
			];
		}

		// No content checked means no score, not a perfect one.
		$overall_score = $total_pages > 0 ? (int) round( $total_score / $total_pages ) : null;

		if ( 0 === $total_pages ) {
			$state = 'NOTHING_CHECKED';
		} elseif ( 0 === $with_issues ) {
			$state = 'NO_FINDINGS_IN_THREE_CHECKS';
		} else {
			$state = 'FINDINGS';
		}

		$results = [
			'score'               => $overall_score,
			'overall_score'       => $overall_score,
			'total_pages'         => $total_pages,
			'pages_with_findings' => $with_issues,
			'sample_limit'        => self::SAMPLE_LIMIT,
			'compliance_state'    => $state,
			'audited_at'          => current_time( 'mysql' ),
			'findings'            => $findings,
		];

		update_option( self::OPTION, $results, false );

		/**
		 * Fires after a pattern-check run has completed and stored its results.
		 *
		 * @param array<string, mixed> $results Pattern-check run results.
		 */
		do_action( 'wm_bfsg_dashboard_audit_completed', $results );

		return $results;
	}
}
