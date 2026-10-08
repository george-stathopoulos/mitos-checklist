<?php
defined( 'ABSPATH' ) || exit;

/** UI strings for the front end, Greek or English by site locale. */
class Mitoschk_Strings {

	public static function all() {
		$el = 0 === strpos( determine_locale(), 'el' );
		$s  = array(
			'search'      => array( 'Search a procedure…', 'Αναζήτηση διαδικασίας…' ),
			'none'        => array( 'No procedures found.', 'Δεν βρέθηκαν διαδικασίες.' ),
			'back'        => array( '← All procedures', '← Όλες οι διαδικασίες' ),
			'conditions'  => array( 'Conditions', 'Προϋποθέσεις' ),
			'documents'   => array( 'Documents to collect', 'Δικαιολογητικά' ),
			'fees'        => array( 'Fees', 'Παράβολα και κόστος' ),
			'steps'       => array( 'Steps', 'Βήματα' ),
			'links'       => array( 'Official links', 'Επίσημοι σύνδεσμοι' ),
			'apply'       => array( 'Go to the official service', 'Μετάβαση στην επίσημη υπηρεσία' ),
			'progress'    => array( '%1$d of %2$d documents collected', '%1$d από %2$d δικαιολογητικά έτοιμα' ),
			'remaining'   => array( 'Still to collect:', 'Απομένουν:' ),
			'allDone'     => array( 'All documents collected. Next step: the official service below.', 'Έχετε όλα τα δικαιολογητικά. Επόμενο βήμα: η επίσημη υπηρεσία παρακάτω.' ),
			'noDocs'      => array( 'The registry lists no documents for this procedure.', 'Το μητρώο δεν αναφέρει δικαιολογητικά για αυτή τη διαδικασία.' ),
			'notes'       => array( 'My notes and reminders (saved on this device only)', 'Οι σημειώσεις μου (αποθηκεύονται μόνο σε αυτή τη συσκευή)' ),
			'print'       => array( 'Print checklist', 'Εκτύπωση λίστας' ),
			'alternative' => array( 'alternative', 'εναλλακτικά' ),
			'reviewed'    => array( 'Reviewed by our editors on %s', 'Ελέγχθηκε από τη συντακτική ομάδα στις %s' ),
			'refreshed'   => array( 'Information last refreshed: %s', 'Τελευταία ενημέρωση πληροφοριών: %s' ),
			'source'      => array( 'Source: Mitos – National Registry of Administrative Procedures', 'Πηγή: Μίτος – Εθνικό Μητρώο Διοικητικών Διαδικασιών' ),
			'licence'     => array( 'Licensed CC BY-SA 4.0', 'Άδεια CC BY-SA 4.0' ),
			'disclaimer'  => array( 'Informational only. Always confirm on the official service before you apply.', 'Μόνο ενημερωτικό. Επιβεβαιώστε πάντα στην επίσημη υπηρεσία πριν υποβάλετε αίτηση.' ),
			'loading'     => array( 'Loading…', 'Φόρτωση…' ),
			'error'       => array( 'Could not load the data. Please try again later.', 'Δεν ήταν δυνατή η φόρτωση. Δοκιμάστε ξανά αργότερα.' ),
		);
		return array_map( function ( $pair ) use ( $el ) {
			return $pair[ $el ? 1 : 0 ];
		}, $s );
	}
}
