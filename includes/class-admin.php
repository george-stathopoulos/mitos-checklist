<?php
defined( 'ABSPATH' ) || exit;

/** Settings > Mitos Checklist: choose procedures, sync, mark as reviewed. */
class Mitoschk_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_mitoschk_save', array( __CLASS__, 'save' ) );
	}

	public static function menu() {
		add_options_page( 'Mitos Checklist', 'Mitos Checklist', 'manage_options', 'mitos-checklist', array( __CLASS__, 'page' ) );
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'mitoschk_save' );

		$ids = sanitize_textarea_field( wp_unslash( $_POST['ids'] ?? '' ) );
		update_option( Mitoschk_Sync::OPT_IDS, $ids, false );

		$reviewed = get_option( Mitoschk_Sync::OPT_REVIEW, array() );
		$reviewed = is_array( $reviewed ) ? $reviewed : array();
		$checked  = array_map( 'absint', (array) ( $_POST['reviewed'] ?? array() ) );
		foreach ( Mitoschk_Sync::ids() as $id ) {
			if ( in_array( (int) $id, $checked, true ) ) {
				$reviewed[ $id ] = $reviewed[ $id ] ?? time();
			} else {
				unset( $reviewed[ $id ] );
			}
		}
		update_option( Mitoschk_Sync::OPT_REVIEW, $reviewed, false );

		$msg = 'saved';
		if ( ! empty( $_POST['index'] ) ) {
			$msg = Mitoschk_Index::build() ? 'indexed' : 'indexing';
		}
		if ( ! empty( $_POST['sync'] ) ) {
			$r   = Mitoschk_Sync::run();
			$msg = $r['errors'] ? 'partial' : 'synced';
			set_transient( 'mitoschk_errors', $r['errors'], 60 );
		}
		wp_safe_redirect( add_query_arg( 'mitoschk', $msg, admin_url( 'options-general.php?page=mitos-checklist' ) ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$data     = Mitoschk_Sync::data();
		$reviewed = get_option( Mitoschk_Sync::OPT_REVIEW, array() );
		$notice   = isset( $_GET['mitoschk'] ) ? sanitize_key( $_GET['mitoschk'] ) : '';
		?>
		<div class="wrap">
			<h1>Mitos Checklist</h1>
			<?php if ( 'saved' === $notice || 'synced' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo 'synced' === $notice ? 'Saved and synced.' : 'Saved.'; ?></p></div>
			<?php elseif ( 'indexed' === $notice || 'indexing' === $notice ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php echo 'indexed' === $notice ? 'Index complete.' : 'Index partly built; it continues in the background (or press the button again).'; ?></p></div>
			<?php elseif ( 'partial' === $notice ) : ?>
				<div class="notice notice-warning is-dismissible"><p>Saved. Some procedures could not be fetched:
				<?php
				foreach ( (array) get_transient( 'mitoschk_errors' ) as $id => $err ) {
					echo esc_html( $id . ' (' . $err . ') ' );
				}
				?>
				</p></div>
			<?php endif; ?>

			<p>Paste the registration codes (MAK) of the procedures you want to offer, one per line. A code is the number in a Mitos link such as <code>https://id.mitos.gov.gr/439993</code>. Up to <?php echo (int) Mitoschk_Sync::MAX_IDS; ?>. Data refreshes daily.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mitoschk_save">
				<?php wp_nonce_field( 'mitoschk_save' ); ?>
				<textarea name="ids" rows="8" cols="40" class="code"><?php echo esc_textarea( (string) get_option( Mitoschk_Sync::OPT_IDS, '' ) ); ?></textarea>

				<?php if ( $data ) : ?>
					<h2>Synced procedures</h2>
					<table class="widefat striped" style="max-width:900px">
						<thead><tr><th>Reviewed</th><th>Code</th><th>Title</th><th>Registry updated</th><th>Synced</th></tr></thead>
						<tbody>
						<?php foreach ( $data as $id => $p ) : ?>
							<tr>
								<td><input type="checkbox" name="reviewed[]" value="<?php echo esc_attr( $id ); ?>" <?php checked( ! empty( $reviewed[ $id ] ) ); ?>></td>
								<td><a href="<?php echo esc_url( $p['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $id ); ?></a></td>
								<td><?php echo esc_html( $p['title'] ); ?></td>
								<td><?php echo esc_html( $p['source_date'] ? mysql2date( 'j M Y', $p['source_date'] ) : '–' ); ?></td>
								<td><?php echo esc_html( wp_date( 'j M Y H:i', $p['synced'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">Tick “Reviewed” only after you have checked the procedure against the official service. Visitors see the review date.</p>
				<?php endif; ?>

				<p>
					<button class="button button-primary" type="submit">Save</button>
					<button class="button" type="submit" name="sync" value="1">Save and sync now</button>
					<button class="button" type="submit" name="index" value="1">Build search index</button>
				</p>
			</form>

			<?php $idx = Mitoschk_Index::get(); ?>
			<p>Search index: <?php echo $idx ? esc_html( count( $idx['items'] ) . ' procedures, built ' . wp_date( 'j M Y H:i', $idx['built'] ) ) : 'not built yet (it builds itself in the background after activation).'; ?></p>

			<h2>Use it</h2>
			<p><code>[mitos_guide]</code> is the full start page: journeys plus search over every Mitos procedure. Add <code>[mitos_checklist]</code> to any page for the searchable list, or <code>[mitos_checklist id="439993"]</code> for one procedure.</p>
			<p>Procedure information © its publishers via <a href="https://mitos.gov.gr" target="_blank" rel="noopener">Mitos</a>, CC BY-SA 4.0. The plugin shows the credit automatically.</p>
		</div>
		<?php
	}
}
