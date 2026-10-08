<?php
defined( 'ABSPATH' ) || exit;

/**
 * Local title index of every Mitos procedure (the API has no search), plus
 * cached on-demand lookup of single procedures.
 */
class Mitoschk_Index {

	const LIST_API = 'https://api.digigov.grnet.gr/v1/services/';
	const OPT      = 'mitoschk_index';
	const PARTIAL  = 'mitoschk_index_partial';
	const HOOK     = 'mitoschk_index_build';
	const BUDGET   = 20; // seconds per run.

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'build' ) );
		add_action( 'init', function () {
			$idx = self::get();
			$old = $idx && ( time() - (int) $idx['built'] ) > WEEK_IN_SECONDS;
			if ( ( ! $idx || $old ) && ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_single_event( time() + 30, self::HOOK );
			}
		} );
	}

	/** @return array|null array( 'items' => id => title, 'built' => ts ) */
	public static function get() {
		$i = get_option( self::OPT );
		return is_array( $i ) && ! empty( $i['items'] ) ? $i : null;
	}

	/** Continue (or start) building the index. Returns true when complete. */
	public static function build() {
		$start = time();
		$part  = get_option( self::PARTIAL );
		$part  = is_array( $part ) ? $part : array( 'page' => 1, 'items' => array() );

		while ( time() - $start < self::BUDGET ) {
			$res = wp_remote_get( add_query_arg( array( 'page' => $part['page'], 'limit' => 100 ), self::LIST_API ), array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json', 'User-Agent' => 'MitosChecklist/' . MITOSCHK_VERSION . '; ' . home_url() ),
			) );
			if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
				break;
			}
			$json = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( empty( $json['data'] ) ) {
				break;
			}
			foreach ( $json['data'] as $row ) {
				$title = isset( $row['title']['el'] ) ? $row['title']['el'] : ( $row['title']['en'] ?? '' );
				if ( $title && isset( $row['id'] ) ) {
					$part['items'][ (string) $row['id'] ] = $title;
				}
			}
			if ( empty( $json['next_page'] ) ) {
				update_option( self::OPT, array( 'items' => $part['items'], 'built' => time() ), false );
				delete_option( self::PARTIAL );
				return true;
			}
			$part['page'] = (int) $json['next_page'];
		}

		update_option( self::PARTIAL, $part, false );
		wp_schedule_single_event( time() + 20, self::HOOK );
		return false;
	}

	/** Lower-case and strip Greek accents so searches match either way. */
	public static function norm( $s ) {
		$s = function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
		return strtr( $s, array(
			'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ϊ' => 'ι', 'ΐ' => 'ι',
			'ό' => 'ο', 'ύ' => 'υ', 'ϋ' => 'υ', 'ΰ' => 'υ', 'ώ' => 'ω', 'ς' => 'σ',
		) );
	}

	/** @return array list of array( id, title ) */
	public static function search( $q, $limit = 20 ) {
		$idx = self::get();
		if ( ! $idx ) {
			return array();
		}
		$terms = array_filter( preg_split( '/\s+/u', self::norm( trim( $q ) ) ) );
		if ( ! $terms ) {
			return array();
		}
		$hits = array();
		foreach ( $idx['items'] as $id => $title ) {
			$n     = self::norm( $title );
			$score = 0;
			foreach ( $terms as $t ) {
				$pos = strpos( $n, $t );
				if ( false === $pos ) {
					continue 2;
				}
				$score += ( 0 === $pos || ' ' === $n[ $pos - 1 ] ) ? 2 : 1;
			}
			$hits[] = array( 'id' => (string) $id, 'title' => $title, 'score' => $score - strlen( $n ) / 1000 );
		}
		usort( $hits, function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );
		return array_map( function ( $h ) {
			return array( 'id' => $h['id'], 'title' => $h['title'] );
		}, array_slice( $hits, 0, $limit ) );
	}

	/** Live fetch with a 12h cache, a long-lived stale copy and a per-visitor limit. @return array|WP_Error */
	public static function live( $id ) {
		$idx = self::get();
		if ( $idx && ! isset( $idx['items'][ $id ] ) ) {
			return new WP_Error( 'mitoschk_unknown', 'Unknown procedure', array( 'status' => 404 ) );
		}
		$fresh = get_transient( 'mitoschk_p_' . $id );
		if ( is_array( $fresh ) ) {
			return $fresh;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'mitoschk_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 60 ) {
			$stale = get_transient( 'mitoschk_ps_' . $id );
			return is_array( $stale ) ? $stale : new WP_Error( 'mitoschk_rate', 'Too many requests', array( 'status' => 429 ) );
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );

		$rec = Mitoschk_Sync::fetch( $id );
		if ( is_wp_error( $rec ) ) {
			$stale = get_transient( 'mitoschk_ps_' . $id );
			return is_array( $stale ) ? $stale : new WP_Error( 'mitoschk_upstream', 'The registry is unavailable', array( 'status' => 502 ) );
		}
		set_transient( 'mitoschk_p_' . $id, $rec, 12 * HOUR_IN_SECONDS );
		set_transient( 'mitoschk_ps_' . $id, $rec, 30 * DAY_IN_SECONDS );
		return $rec;
	}
}
