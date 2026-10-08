<?php
defined( 'ABSPATH' ) || exit;

/** Pulls the chosen procedures from the Mitos API into a local option. */
class Mitoschk_Sync {

	const API       = 'https://api.digigov.grnet.gr/v1/services-extended/';
	const HOOK      = 'mitoschk_daily_sync';
	const MAX_IDS   = 50;
	const OPT_IDS   = 'mitoschk_ids';
	const OPT_DATA  = 'mitoschk_procedures';
	const OPT_REVIEW = 'mitoschk_reviewed';

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			self::schedule();
		}
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/** @return string[] Registration codes (MAK) chosen by the site owner. */
	public static function ids() {
		$raw = (string) get_option( self::OPT_IDS, '' );
		preg_match_all( '/\d{3,}/', $raw, $m );
		$ids = array_merge( $m[0], Mitoschk_Journeys::procedure_ids() );
		return array_slice( array_values( array_unique( $ids ) ), 0, self::MAX_IDS );
	}

	public static function data() {
		$d = get_option( self::OPT_DATA, array() );
		return is_array( $d ) ? $d : array();
	}

	/** Re-fetch every chosen procedure. Returns array( ok, failed ) counts + errors. */
	public static function run() {
		$ids    = self::ids();
		$data   = self::data();
		$ok     = 0;
		$errors = array();
		foreach ( $ids as $id ) {
			$res = wp_remote_get( self::API . rawurlencode( $id ), array(
				'timeout' => 20,
				'headers' => array( 'Accept' => 'application/json', 'User-Agent' => 'MitosChecklist/' . MITOSCHK_VERSION . '; ' . home_url() ),
			) );
			if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
				$errors[ $id ] = is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $res );
				continue;
			}
			$json = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
				$errors[ $id ] = 'Unexpected response';
				continue;
			}
			$data[ $id ] = self::normalize( $json['data'], $id );
			++$ok;
		}
		// Drop procedures the owner removed from the list.
		$data = array_intersect_key( $data, array_flip( $ids ) );
		update_option( self::OPT_DATA, $data, false );
		return array( 'ok' => $ok, 'errors' => $errors );
	}

	private static function t( $v ) {
		if ( is_array( $v ) ) {
			return isset( $v['el'] ) ? (string) $v['el'] : ( isset( $v['en'] ) ? (string) $v['en'] : '' );
		}
		return (string) $v;
	}

	private static function dur( $iso ) {
		if ( ! is_string( $iso ) || ! preg_match( '/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', $iso, $m ) ) {
			return '';
		}
		$out = array();
		if ( ! empty( $m[1] ) ) { $out[] = $m[1] . ' d'; }
		if ( ! empty( $m[2] ) ) { $out[] = $m[2] . ' h'; }
		if ( ! empty( $m[3] ) ) { $out[] = $m[3] . ' min'; }
		if ( ! empty( $m[4] ) ) { $out[] = $m[4] . ' s'; }
		return implode( ' ', $out );
	}

	private static function url( $u ) {
		$u = is_string( $u ) ? esc_url_raw( trim( $u ) ) : '';
		return $u;
	}

	/** Reduce the registry record to what the checklist needs. */
	public static function normalize( array $d, $id ) {
		$meta = isset( $d['metadata'] ) && is_array( $d['metadata'] ) ? $d['metadata'] : array();
		$p    = isset( $meta['process'] ) && is_array( $meta['process'] ) ? $meta['process'] : array();

		$conditions = array();
		foreach ( (array) ( $meta['process_conditions'] ?? array() ) as $c ) {
			$conditions[] = array(
				'type' => self::t( $c['conditions_type'] ?? '' ),
				'text' => self::t( $c['conditions_name'] ?? '' ),
				'alt'  => ! empty( $c['conditions_alternative'] ),
				'url'  => self::url( $c['conditions_url'] ?? '' ),
			);
		}

		$documents = array();
		foreach ( (array) ( $meta['process_evidences'] ?? array() ) as $e ) {
			$documents[] = array(
				'key'    => (string) ( $e['evidence_num_id'] ?? count( $documents ) + 1 ),
				'title'  => self::t( $e['evidence_type']['title'] ?? '' ),
				'text'   => self::t( $e['evidence_description'] ?? '' ),
				'note'   => self::t( $e['evidence_note'] ?? '' ),
				'how'    => implode( ', ', array_map( 'strval', (array) ( $e['evidence_submission_type'] ?? array() ) ) ),
				'alt'    => ! empty( $e['evidence_alternative'] ),
				'url'    => self::url( $e['evidence_url'] ?? '' ),
			);
		}

		$fees = array();
		foreach ( (array) ( $meta['process_evidences_cost'] ?? array() ) as $f ) {
			$fees[] = array(
				'type' => self::t( $f['evidence_cost_type'] ?? '' ),
				'text' => self::t( $f['evidence_cost_description'] ?? '' ),
				'min'  => isset( $f['evidence_cost_min'] ) ? (string) $f['evidence_cost_min'] : '',
				'max'  => isset( $f['evidence_cost_max'] ) ? (string) $f['evidence_cost_max'] : '',
			);
		}

		$steps = array();
		foreach ( (array) ( $meta['process_steps'] ?? array() ) as $s ) {
			$steps[] = array(
				'title' => self::t( $s['step_title'] ?? '' ),
				'text'  => self::t( $s['step_description'] ?? '' ),
				'time'  => self::dur( $s['step_duration_max'] ?? '' ),
			);
		}
		foreach ( (array) ( $meta['process_steps_digital'] ?? array() ) as $s ) {
			$steps[] = array(
				'title' => self::t( $s['step_digital_title'] ?? '' ),
				'text'  => self::t( $s['step_digital_description'] ?? '' ),
				'time'  => self::dur( $s['step_digital_duration_max'] ?? '' ),
				'url'   => self::url( $s['step_digital_url'] ?? '' ),
			);
		}

		$links = array();
		foreach ( (array) ( $meta['process_useful_links'] ?? array() ) as $l ) {
			$u = self::url( $l['useful_link_url'] ?? '' );
			if ( $u ) {
				$links[] = array( 'title' => self::t( $l['useful_link_title'] ?? $u ), 'url' => $u );
			}
		}

		$apply = array();
		foreach ( (array) ( $meta['process_provision_digital_locations'] ?? array() ) as $l ) {
			$u = self::url( $l['provision_digital_location_url'] ?? '' );
			if ( $u ) {
				$apply[] = array( 'title' => self::t( $l['provision_digital_location_title'] ?? $u ), 'url' => $u );
			}
		}

		return array(
			'id'          => (string) $id,
			'title'       => self::t( $d['title'] ?? ( $p['official_title'] ?? '' ) ),
			'description' => self::t( $p['description'] ?? '' ),
			'owner'       => self::t( $p['org_owner']['title'] ?? '' ),
			'source_url'  => self::url( $d['url'] ?? 'https://id.mitos.gov.gr/' . $id ),
			'source_date' => (string) ( $d['last_updated'] ?? '' ),
			'synced'      => time(),
			'conditions'  => $conditions,
			'documents'   => $documents,
			'fees'        => $fees,
			'steps'       => $steps,
			'links'       => $links,
			'apply'       => $apply,
		);
	}
}
