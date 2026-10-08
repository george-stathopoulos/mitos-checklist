<?php
/**
 * Plugin Name: Mitos Checklist
 * Description: Turns official Greek administrative procedures from the Mitos registry (mitos.gov.gr) into saveable preparation checklists, embedded with a shortcode.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: mitos-checklist
 *
 * Procedure data comes from the Mitos API and is licensed CC BY-SA 4.0.
 */

defined( 'ABSPATH' ) || exit;

define( 'MITOSCHK_VERSION', '0.1.0' );
define( 'MITOSCHK_FILE', __FILE__ );
define( 'MITOSCHK_DIR', plugin_dir_path( __FILE__ ) );
define( 'MITOSCHK_URL', plugin_dir_url( __FILE__ ) );

require_once MITOSCHK_DIR . 'includes/class-strings.php';
require_once MITOSCHK_DIR . 'includes/class-sync.php';
require_once MITOSCHK_DIR . 'includes/class-rest.php';
require_once MITOSCHK_DIR . 'includes/class-shortcode.php';
require_once MITOSCHK_DIR . 'includes/class-admin.php';

register_activation_hook( __FILE__, array( 'Mitoschk_Sync', 'schedule' ) );
register_deactivation_hook( __FILE__, array( 'Mitoschk_Sync', 'unschedule' ) );

add_action( 'plugins_loaded', function () {
	Mitoschk_Sync::init();
	Mitoschk_Rest::init();
	Mitoschk_Shortcode::init();
	Mitoschk_Admin::init();
} );
