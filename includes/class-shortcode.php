<?php
defined( 'ABSPATH' ) || exit;

/** [mitos_checklist] and [mitos_checklist id="123456"] */
class Mitoschk_Shortcode {

	public static function init() {
		add_shortcode( 'mitos_checklist', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'mitos_checklist' );

		wp_enqueue_style( 'mitos-checklist', MITOSCHK_URL . 'assets/app.css', array(), MITOSCHK_VERSION );
		wp_enqueue_script( 'mitos-checklist', MITOSCHK_URL . 'assets/app.js', array(), MITOSCHK_VERSION, true );
		wp_localize_script( 'mitos-checklist', 'MitosChecklist', array(
			'rest'    => esc_url_raw( rest_url( 'mitos-checklist/v1/' ) ),
			'strings' => Mitoschk_Strings::all(),
			'dateFmt' => 0 === strpos( determine_locale(), 'el' ) ? 'el-GR' : 'en-GB',
		) );

		return '<div class="mitoschk" data-id="' . esc_attr( preg_replace( '/\D/', '', (string) $atts['id'] ) ) . '"><p>' . esc_html( Mitoschk_Strings::all()['loading'] ) . '</p></div>';
	}
}
