<?php
defined( 'ABSPATH' ) || exit;

/** Read-only public endpoints serving the synced procedures to the front end. */
class Mitoschk_Rest {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route( 'mitos-checklist/v1', '/procedures', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'list_items' ),
		) );
		register_rest_route( 'mitos-checklist/v1', '/procedures/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'one' ),
		) );
		register_rest_route( 'mitos-checklist/v1', '/search', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'search' ),
		) );
		register_rest_route( 'mitos-checklist/v1', '/journeys', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'journeys' ),
		) );
		register_rest_route( 'mitos-checklist/v1', '/journeys/(?P<id>[a-z0-9_-]+)', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'journey' ),
		) );
	}

	public static function search( WP_REST_Request $req ) {
		$q = substr( sanitize_text_field( (string) $req->get_param( 'q' ) ), 0, 100 );
		return rest_ensure_response( array(
			'indexed' => (bool) Mitoschk_Index::get(),
			'results' => strlen( $q ) >= 2 ? Mitoschk_Index::search( $q ) : array(),
		) );
	}

	public static function journeys() {
		$out = array();
		foreach ( Mitoschk_Journeys::all() as $id => $j ) {
			$out[] = array( 'id' => $id, 'title' => $j['title'], 'intro' => $j['intro'], 'steps' => count( $j['steps'] ) );
		}
		return rest_ensure_response( $out );
	}

	public static function journey( WP_REST_Request $req ) {
		$j = Mitoschk_Journeys::get( (string) $req['id'] );
		if ( ! $j ) {
			return new WP_Error( 'mitoschk_not_found', 'Unknown journey', array( 'status' => 404 ) );
		}
		$data = Mitoschk_Sync::data();
		foreach ( $j['steps'] as &$step ) {
			$pid  = (string) ( $step['procedure'] ?? '' );
			$rec  = ! empty( $data[ $pid ] ) ? $data[ $pid ] : Mitoschk_Index::live( $pid );
			$step['available']  = ! is_wp_error( $rec );
			$step['title']      = $step['available'] ? $rec['title'] : '';
			$step['apply']      = $step['available'] ? $rec['apply'] : array();
			$step['source_url'] = 'https://id.mitos.gov.gr/' . rawurlencode( $pid );
			$step['reviewed']   = self::reviewed( $pid );
		}
		unset( $step );
		return rest_ensure_response( $j );
	}

	/**
	 * Documents that are themselves issued through a Mitos procedure.
	 * Pattern => Mitos code. Filterable via `mitoschk_document_sources`.
	 */
	private static function document_source( $text, $data ) {
		$map = apply_filters( 'mitoschk_document_sources', array(
			'/φορολογικής ενημερότητας/iu' => '439993',
			'/ασφαλιστικής ενημερότητας/iu' => '119372',
			'/αποδεικτικ[όο] ΑΦΜ|βεβαίωση απόδοσης ΑΦΜ/iu' => '160473',
			'/\bΑΜΚΑ\b/iu'                  => '791797',
		) );
		foreach ( $map as $pattern => $code ) {
			if ( preg_match( $pattern, $text ) ) {
				return array(
					'title' => isset( $data[ $code ] ) ? $data[ $code ]['title'] : '',
					'url'   => 'https://id.mitos.gov.gr/' . rawurlencode( $code ),
				);
			}
		}
		return null;
	}

	private static function reviewed( $id ) {
		$r = get_option( Mitoschk_Sync::OPT_REVIEW, array() );
		return is_array( $r ) && ! empty( $r[ $id ] ) ? (int) $r[ $id ] : 0;
	}

	public static function list_items() {
		$out = array();
		foreach ( Mitoschk_Sync::data() as $id => $p ) {
			$out[] = array(
				'id'          => (string) $id,
				'title'       => $p['title'],
				'description' => $p['description'],
				'owner'       => $p['owner'],
			);
		}
		return rest_ensure_response( $out );
	}

	public static function one( WP_REST_Request $req ) {
		$data = Mitoschk_Sync::data();
		$id   = (string) $req['id'];
		if ( ! empty( $data[ $id ] ) ) {
			$p = $data[ $id ];
		} else {
			$p = Mitoschk_Index::live( $id );
			if ( is_wp_error( $p ) ) {
				return $p;
			}
		}
		$p['reviewed'] = self::reviewed( $id );
		foreach ( $p['documents'] as &$doc ) {
			$doc['source'] = self::document_source( $doc['title'] . ' ' . $doc['text'], $data );
		}
		unset( $doc );
		return rest_ensure_response( $p );
	}
}
