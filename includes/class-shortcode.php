<?php
defined( 'ABSPATH' ) || exit;

/** [mitos_checklist], [mitos_checklist id="123456"] and [mitos_journey id="freelancer"] */
class Mitoschk_Shortcode {

	public static function init() {
		add_shortcode( 'mitos_checklist', array( __CLASS__, 'render' ) );
		add_shortcode( 'mitos_journey', array( __CLASS__, 'render_journey' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'mitos_checklist' );
		self::assets();
		return '<div class="mitoschk" data-id="' . esc_attr( preg_replace( '/\D/', '', (string) $atts['id'] ) ) . '"><p>' . esc_html( Mitoschk_Strings::all()['loading'] ) . '</p></div>';
	}

	public static function render_journey( $atts ) {
		$atts = shortcode_atts( array( 'id' => 'freelancer' ), $atts, 'mitos_journey' );
		self::assets();
		return '<div class="mitoschk" data-journey="' . esc_attr( sanitize_key( $atts['id'] ) ) . '"><p>' . esc_html( Mitoschk_Strings::all()['loading'] ) . '</p></div>';
	}

	private static function assets() {
		wp_enqueue_style( 'mitos-checklist', MITOSCHK_URL . 'assets/app.css', array(), MITOSCHK_VERSION );
		wp_enqueue_script( 'mitos-checklist', MITOSCHK_URL . 'assets/app.js', array(), MITOSCHK_VERSION, true );
		wp_localize_script( 'mitos-checklist', 'MitosChecklist', array(
			'rest'    => esc_url_raw( rest_url( 'mitos-checklist/v1/' ) ),
			'strings' => Mitoschk_Strings::all(),
			'dateFmt' => Mitoschk_Strings::is_greek() ? 'el-GR' : 'en-GB',
		) );
	}
}
