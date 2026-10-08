<?php
defined( 'ABSPATH' ) || exit;

/** UI strings for the front end, Greek or English by site locale. */
class Mitoschk_Strings {

	/** Greek by default for every visitor. Return 'en' from the `mitoschk_language` filter for English. */
	public static function is_greek() {
		return 'en' !== apply_filters( 'mitoschk_language', 'el' );
	}

	public static function all() {
		$el = self::is_greek();
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
			'sourceLead'  => array( 'Source:', 'Πηγή:' ),
			'sourceName'  => array( 'Mitos – National Registry of Administrative Procedures (mitos.gov.gr)', 'Μίτος – Εθνικό Μητρώο Διοικητικών Διαδικασιών (mitos.gov.gr)' ),
			'adapted'     => array( 'Adapted: text is reorganised into a checklist and durations are reformatted; the original is at the link above.', 'Προσαρμογή: το κείμενο αναδιατάσσεται σε λίστα ελέγχου και οι διάρκειες μορφοποιούνται· το πρωτότυπο βρίσκεται στον παραπάνω σύνδεσμο.' ),
			'licence'     => array( 'Licensed CC BY-SA 4.0', 'Άδεια CC BY-SA 4.0' ),
			'disclaimer'  => array( 'Informational only. Always confirm on the official service before you apply.', 'Μόνο ενημερωτικό. Επιβεβαιώστε πάντα στην επίσημη υπηρεσία πριν υποβάλετε αίτηση.' ),
			'yes'         => array( 'Yes', 'Ναι' ),
			'no'          => array( 'No', 'Όχι' ),
			'stepsDone'   => array( '%1$d of %2$d steps done', '%1$d από %2$d βήματα ολοκληρώθηκαν' ),
			'openList'    => array( 'Show checklist', 'Εμφάνιση λίστας' ),
			'hideList'    => array( 'Hide checklist', 'Απόκρυψη λίστας' ),
			'deadline'    => array( 'Deadline: %s', 'Προθεσμία: %s' ),
			'deadlinePast'=> array( 'The deadline (%s) has passed. Apply as soon as you can; the fund can otherwise register you itself and contributions count from the start.', 'Η προθεσμία (%s) έχει περάσει. Υποβάλετε το συντομότερο· διαφορετικά ο φορέας μπορεί να σας εγγράψει αυτεπάγγελτα και οι εισφορές μετρούν από την έναρξη.' ),
			'remind'      => array( 'Add a reminder to my calendar', 'Προσθήκη υπενθύμισης στο ημερολόγιο' ),
			'remindTitle' => array( 'Deadline: %s', 'Προθεσμία: %s' ),
			'waitFor'     => array( 'Do the previous step first.', 'Ολοκληρώστε πρώτα το προηγούμενο βήμα.' ),
			'unavailable' => array( 'This procedure has not been loaded yet.', 'Η διαδικασία δεν έχει φορτωθεί ακόμη.' ),
			'startOver'   => array( 'Start over', 'Ξεκίνημα από την αρχή' ),
			'changed'     => array( 'This procedure was updated on %s, after you last viewed it. Re-check the details below.', 'Η διαδικασία ενημερώθηκε στις %s, μετά την τελευταία φορά που την είδατε. Ελέγξτε ξανά τις λεπτομέρειες.' ),
			'gotIt'       => array( 'Got it', 'Το είδα' ),
			'anyOne'      => array( 'Any one of these is enough:', 'Αρκεί ένα από τα παρακάτω:' ),
			'whereGet'    => array( 'Where to get it: %s', 'Πού το βρίσκω: %s' ),
			'loading'     => array( 'Loading…', 'Φόρτωση…' ),
			'error'       => array( 'Could not load the data. Please try again later.', 'Δεν ήταν δυνατή η φόρτωση. Δοκιμάστε ξανά αργότερα.' ),
		);
		return array_map( function ( $pair ) use ( $el ) {
			return $pair[ $el ? 1 : 0 ];
		}, $s );
	}
}
