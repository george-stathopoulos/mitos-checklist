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
		register_rest_route( 'mitos-checklist/v1', '/journeys/(?P<id>[a-z0-9_-]+)', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'journey' ),
		) );
	}

	public static function journey( WP_REST_Request $req ) {
		$j = Mitoschk_Journeys::get( (string) $req['id'] );
		if ( ! $j ) {
			return new WP_Error( 'mitoschk_not_found', 'Unknown journey', array( 'status' => 404 ) );
		}
		$data = Mitoschk_Sync::data();
		foreach ( $j['steps'] as &$step ) {
			$pid = (string) ( $step['procedure'] ?? '' );
			$step['available'] = ! empty( $data[ $pid ] );
			$step['title']     = $step['available'] ? $data[ $pid ]['title'] : '';
			$step['reviewed']  = self::reviewed( $pid );
		}
		unset( $step );
		return rest_ensure_response( $j );
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
		if ( empty( $data[ $id ] ) ) {
			return new WP_Error( 'mitoschk_not_found', 'Unknown procedure', array( 'status' => 404 ) );
		}
		$p             = $data[ $id ];
		$p['reviewed'] = self::reviewed( $id );
		return rest_ensure_response( $p );
	}
}
