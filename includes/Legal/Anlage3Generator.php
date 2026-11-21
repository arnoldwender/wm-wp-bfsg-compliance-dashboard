<?php
declare(strict_types=1);

namespace WMCyber\BFSG\Dashboard\Legal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Anlage3Generator {
	/** The three levels the admin form offers; anything else is treated as "not stated". */
	public const COMPLIANCE_LEVELS = [ 'vollständig konform', 'größtenteils konform', 'nicht konform' ];

	/**
	 * The date the statement names: a stored Y-m-d from the settings form, shown as d.m.Y; another non-empty
	 * value as given; '' when none was entered.
	 *
	 * Until 2026-09-15 a missing date became wp_date( 'd.m.Y' ) at render time, so the public page said
	 * "Diese Erklärung wurde am <today> erstellt" on every view.
	 */
	public static function display_date( string $value ): string {
		$value = trim( $value );
		if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? "{$m[3]}.{$m[2]}.{$m[1]}" : '';
		}
		return $value;
	}

	/**
	 * Generate the Erklärung zur Barrierefreiheit from the operator's own settings.
	 *
	 * Missing data is never filled with invented values. Until 2026-09-14 an unconfigured site
	 * published "vollständig konform", the contact person "WM Suite Compliance Officer" (the admin
	 * default was "Arnold Wender (Lead Compliance Auditor)") and the phone numbers +49 345 000000 or
	 * +49 345 213890, and the statement said it rested on "eingehender technischer und semantischer
	 * Server-Side-Prüfung", "kontinuierlicher automatisierter Server-Side AST-Analyse und manuellen
	 * Stichproben gemäß dem 98-Schritte-Prüfverfahren des BIK BITV-Tests" — none of which the plugin
	 * performs (it runs three pattern checks on stored content, see AccessibilityAuditor).
	 *
	 * Note: The heading's legal references and the enforcement block (Sachsen-Anhalt authority
	 * and e-mail address printed for every site, whatever `supervisory_body` says) are legal content
	 * reserved for editorial legal review; this pass did not change them.
	 *
	 * @param array<string, string> $overrides company_name, website_url, assessment_date, compliance_level,
	 *                                         feedback_email, feedback_phone, officer_name, supervisory_body.
	 */
	public function generate_declaration( array $overrides = [] ): string {
		$company_name     = (string) ( $overrides['company_name'] ?? get_bloginfo( 'name' ) );
		$website_url      = (string) ( $overrides['website_url'] ?? home_url() );
		// The date of the operator's self-assessment as entered; never the date of this page view.
		$assessment_date  = self::display_date( (string) ( $overrides['assessment_date'] ?? '' ) );
		$compliance_level = (string) ( $overrides['compliance_level'] ?? '' );
		$feedback_email   = (string) ( $overrides['feedback_email'] ?? get_option( 'admin_email' ) );
		$feedback_phone   = trim( (string) ( $overrides['feedback_phone'] ?? '' ) );
		$officer_name     = trim( (string) ( $overrides['officer_name'] ?? '' ) );
		$supervisory_body = (string) ( $overrides['supervisory_body'] ?? 'Landesfachstelle für Barrierefreiheit Sachsen-Anhalt (Halle / Magdeburg)' );

		$html = '<div class="wm-bfsg-declaration-box" id="wm-barrierefreiheitserklaerung" style="background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 28px; color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; line-height: 1.6;">';
		$html .= '<h2 style="color: #38bdf8; font-size: 22px; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 12px;">' . esc_html__( 'Erklärung zur Barrierefreiheit (gemäß Anlage 3 BFSG & Art. 246 EGBGB)', 'wm-bfsg-compliance-dashboard' ) . '</h2>';

		// The link is built outside the translatable string: esc_html__() on a template holding
		// <a href> printed the tag as visible text inside the legal page (fleet audit, D3).
		$link = '<a href="' . esc_url( $website_url ) . '" style="color: #38bdf8; text-decoration: underline;">' . esc_html( $website_url ) . '</a>';
		$html .= '<p>' . sprintf(
			/* translators: 1: company name, 2: link to the website */
			esc_html__( '%1$s ist bemüht, ihren Webauftritt (%2$s) im Einklang mit dem Barrierefreiheitsstärkungsgesetz (BFSG), der europäischen Norm EN 301 549 V3.2.1 sowie den Web Content Accessibility Guidelines (WCAG 2.2 Level AA) barrierefrei zugänglich zu machen.', 'wm-bfsg-compliance-dashboard' ),
			'<strong>' . esc_html( $company_name ) . '</strong>',
			$link
		) . '</p>';

		$html .= '<h3 style="color: #ffffff; font-size: 17px; margin-top: 20px;">' . esc_html__( '1. Stand der Vereinbarkeit mit den Anforderungen', 'wm-bfsg-compliance-dashboard' ) . '</h3>';
		if ( in_array( $compliance_level, self::COMPLIANCE_LEVELS, true ) ) {
			/* translators: %s: compliance level */
			$html .= '<p>' . sprintf( esc_html__( 'Nach der Selbstbewertung des Betreibers ist dieser Webauftritt mit den Vorgaben der harmonisierten europäischen Norm EN 301 549 %s.', 'wm-bfsg-compliance-dashboard' ), '<strong>' . esc_html( $compliance_level ) . '</strong>' ) . '</p>';
		} else {
			$html .= '<p><strong>' . esc_html__( 'Der Stand der Vereinbarkeit ist noch nicht angegeben.', 'wm-bfsg-compliance-dashboard' ) . '</strong></p>';
		}

		$html .= '<h3 style="color: #ffffff; font-size: 17px; margin-top: 20px;">' . esc_html__( '2. Erstellung dieser Erklärung zur Barrierefreiheit', 'wm-bfsg-compliance-dashboard' ) . '</h3>';
		if ( '' !== $assessment_date ) {
			/* translators: %s: assessment date */
			$html .= '<p>' . sprintf( esc_html__( 'Diese Erklärung wurde am %s erstellt. Die Angaben zum Stand der Vereinbarkeit beruhen auf einer Selbstbewertung des Betreibers.', 'wm-bfsg-compliance-dashboard' ), '<strong>' . esc_html( $assessment_date ) . '</strong>' ) . '</p>';
		} else {
			$html .= '<p><strong>' . esc_html__( 'Das Erstellungsdatum ist noch nicht angegeben.', 'wm-bfsg-compliance-dashboard' ) . '</strong> ' . esc_html__( 'Die Angaben zum Stand der Vereinbarkeit beruhen auf einer Selbstbewertung des Betreibers.', 'wm-bfsg-compliance-dashboard' ) . '</p>';
		}

		$html .= '<h3 style="color: #ffffff; font-size: 17px; margin-top: 20px;">' . esc_html__( '3. Feedback und Kontaktangaben (Feedback-Mechanismus)', 'wm-bfsg-compliance-dashboard' ) . '</h3>';
		/* translators: %s: website URL */
		$html .= '<p>' . sprintf( esc_html__( 'Sind Ihnen Mängel beim barrierefreien Zugang zu Inhalten von %s aufgefallen? Oder haben Sie Fragen zur Umsetzung der Barrierefreiheit? Sie können sich jederzeit an unsere zuständige Ansprechperson wenden:', 'wm-bfsg-compliance-dashboard' ), esc_html( $website_url ) ) . '</p>';
		$html .= '<ul style="list-style-type: disc; margin-left: 20px;">';
		if ( '' !== $officer_name ) {
			$html .= '<li><strong>' . esc_html__( 'Ansprechpartner:', 'wm-bfsg-compliance-dashboard' ) . '</strong> ' . esc_html( $officer_name ) . '</li>';
		}
		$html .= '<li><strong>' . esc_html__( 'E-Mail:', 'wm-bfsg-compliance-dashboard' ) . '</strong> <a href="mailto:' . esc_attr( $feedback_email ) . '" style="color: #38bdf8;">' . esc_html( $feedback_email ) . '</a></li>';
		if ( '' !== $feedback_phone ) {
			$html .= '<li><strong>' . esc_html__( 'Telefon:', 'wm-bfsg-compliance-dashboard' ) . '</strong> ' . esc_html( $feedback_phone ) . '</li>';
		}
		$html .= '</ul>';

		$html .= '<h3 style="color: #ffffff; font-size: 17px; margin-top: 20px;">' . esc_html__( '4. Durchsetzungsverfahren & Marktüberwachungsbehörde', 'wm-bfsg-compliance-dashboard' ) . '</h3>';
		$html .= '<p>' . esc_html__( 'Sollten Sie auf Mitteilungen oder Anfragen zur Barrierefreiheit innerhalb von sechs Wochen keine zufriedenstellende Antwort erhalten, können Sie sich an die zuständige Marktüberwachungs- und Durchsetzungsstelle wenden:', 'wm-bfsg-compliance-dashboard' ) . '</p>';
		$html .= '<div style="background: rgba(255,255,255,0.05); padding: 14px 18px; border-radius: 6px; border-left: 4px solid #38bdf8; font-size: 14px;">';
		$html .= '<strong>' . esc_html( $supervisory_body ) . '</strong><br />';
		$html .= esc_html__( 'Aufsichtsbehörde für digitale Barrierefreiheit • Bundesland Sachsen-Anhalt', 'wm-bfsg-compliance-dashboard' ) . '<br />';
		$html .= esc_html__( 'E-Mail:', 'wm-bfsg-compliance-dashboard' ) . ' <a href="mailto:barrierefreiheit@ms.sachsen-anhalt.de" style="color: #38bdf8;">barrierefreiheit@ms.sachsen-anhalt.de</a>';
		$html .= '</div>';

		// This line used to read "Zertifiziert und kontinuierlich überwacht durch WM Suite
		// Enterprise Accessibility Cloud • SHA-256 Validierungs-Hash: <hash>" (removed in an earlier
		// pass: nothing certifies or monitors, no such service exists, the hash attested to nothing).
		$html .= '<p style="font-size: 12px; color: #94a3b8; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px;">';
		if ( '' !== $assessment_date ) {
			$html .= esc_html(
				sprintf(
					/* translators: %s: assessment date */
					__( 'Diese Erklärung wurde am %s auf Grundlage einer Selbstbewertung erstellt. Eine Zertifizierung durch eine externe Stelle liegt ihr nicht zugrunde.', 'wm-bfsg-compliance-dashboard' ),
					$assessment_date
				)
			);
		} else {
			$html .= esc_html__( 'Diese Erklärung beruht auf einer Selbstbewertung. Eine Zertifizierung durch eine externe Stelle liegt ihr nicht zugrunde.', 'wm-bfsg-compliance-dashboard' );
		}
		$html .= '</p>';
		$html .= '</div>';

		/**
		 * Filters the generated accessibility statement HTML before output.
		 *
		 * @param string                $html      Generated HTML statement.
		 * @param array<string, string> $overrides Resolved statement inputs.
		 */
		return (string) apply_filters(
			'wm_bfsg_dashboard_declaration_html',
			$html,
			[
				'company_name'     => $company_name,
				'website_url'      => $website_url,
				'assessment_date'  => $assessment_date,
				'compliance_level' => $compliance_level,
				'feedback_email'   => $feedback_email,
				'feedback_phone'   => $feedback_phone,
				'officer_name'     => $officer_name,
				'supervisory_body' => $supervisory_body,
			]
		);
	}
}
