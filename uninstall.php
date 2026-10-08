<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'mitoschk_ids', 'mitoschk_procedures', 'mitoschk_reviewed', 'mitoschk_takeover', 'mitoschk_brand', 'mitoschk_index', 'mitoschk_index_partial' ) as $opt ) {
	delete_option( $opt );
}
wp_clear_scheduled_hook( 'mitoschk_daily_sync' );
