<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ordered multi-procedure journeys. The order and deadlines below come from the
 * official Mitos entries (see docs/sprint-1.md); change them only after checking
 * the sources again. Filter `mitoschk_journeys` to add or edit journeys.
 */
class Mitoschk_Journeys {

	private static function l( $en, $el ) {
		return 0 === strpos( determine_locale(), 'el' ) ? $el : $en;
	}

	public static function all() {
		$l = array( __CLASS__, 'l' );

		$journeys = array(
			'freelancer' => array(
				'title'     => self::l( 'Start as a sole trader', 'Έναρξη ως ατομική επιχείρηση' ),
				'intro'     => self::l(
					'Four to five steps, in this order. Answer three questions and we hide what you do not need.',
					'Τέσσερα με πέντε βήματα, με αυτή τη σειρά. Απαντήστε σε τρεις ερωτήσεις και κρύβουμε ό,τι δεν χρειάζεστε.'
				),
				'questions' => array(
					array(
						'id'    => 'eligible',
						'label' => self::l( 'Are you an adult Greek or EU citizen, permanently resident in Greece, starting a commercial activity?', 'Είστε ενήλικος Έλληνας ή πολίτης της ΕΕ, μόνιμος κάτοικος Ελλάδας, και θα ασκείτε εμπορική δραστηριότητα;' ),
						'warn'  => self::l( 'The online start in this guide covers only that case. Other cases (non-EU citizens, minors, non-residents, non-commercial activity) go through the tax office and the business register (ΓΕΜΗ). Check the official service before relying on this checklist.', 'Η ηλεκτρονική έναρξη σε αυτόν τον οδηγό καλύπτει μόνο αυτή την περίπτωση. Οι άλλες περιπτώσεις (πολίτες τρίτων χωρών, ανήλικοι, μη κάτοικοι, μη εμπορική δραστηριότητα) γίνονται μέσω ΔΟΥ και ΓΕΜΗ. Ελέγξτε την επίσημη υπηρεσία πριν βασιστείτε σε αυτή τη λίστα.' ),
						'warn_if' => 'no',
					),
					array( 'id' => 'has_afm', 'label' => self::l( 'Do you already have a tax number (ΑΦΜ)?', 'Έχετε ήδη ΑΦΜ;' ) ),
					array( 'id' => 'has_amka', 'label' => self::l( 'Do you already have a social security number (ΑΜΚΑ)?', 'Έχετε ήδη ΑΜΚΑ;' ) ),
				),
				'steps'     => array(
					array(
						'id'        => 'afm',
						'procedure' => '557869',
						'show_if'   => array( 'has_afm' => 'no' ),
						'note'      => self::l( 'You need a tax number before you can start.', 'Χρειάζεστε ΑΦΜ πριν την έναρξη.' ),
					),
					array(
						'id'        => 'amka',
						'procedure' => '791797',
						'show_if'   => array( 'has_amka' => 'no' ),
						'note'      => self::l( 'The social security registration matches your ΑΜΚΑ record.', 'Η ασφαλιστική εγγραφή αντιστοιχίζεται με το ΑΜΚΑ σας.' ),
					),
					array(
						'id'        => 'tax_start',
						'procedure' => '113509',
						'note'      => self::l( 'Online via myAADE, about 12 minutes, free, no documents. You need your TAXISnet codes. This is where you choose your activity codes (ΚΑΔ). Keep the certificate of commencement it produces.', 'Ηλεκτρονικά μέσω myAADE, περίπου 12 λεπτά, δωρεάν, χωρίς δικαιολογητικά. Χρειάζεστε κωδικούς TAXISnet. Εδώ επιλέγετε τους ΚΑΔ. Κρατήστε τη βεβαίωση έναρξης εργασιών.' ),
						'ask_date'  => self::l( 'Date your activity started', 'Ημερομηνία έναρξης εργασιών' ),
					),
					array(
						'id'        => 'efka',
						'procedure' => '185609',
						'after'     => 'tax_start',
						'deadline'  => array( 'day' => 10, 'month_offset' => 1, 'from' => 'tax_start' ),
						'note'      => self::l( 'Apply by the 10th of the month after your tax start. If you do not, e-EFKA can register you on its own and contributions still count from the start.', 'Υποβάλετε αίτηση έως τις 10 του επόμενου μήνα από την έναρξη. Αν δεν το κάνετε, ο e-ΕΦΚΑ μπορεί να σας εγγράψει αυτεπάγγελτα και οι εισφορές μετρούν από την έναρξη.' ),
					),
					array(
						'id'        => 'mydata',
						'procedure' => '963556',
						'after'     => 'tax_start',
						'note'      => self::l( 'Read this procedure to see whether and when it applies to you; its timing relative to the start is not confirmed here.', 'Διαβάστε τη διαδικασία για να δείτε αν και πότε ισχύει για εσάς. Ο χρόνος σε σχέση με την έναρξη δεν έχει επιβεβαιωθεί εδώ.' ),
					),
				),
			),
		);

		$journeys = apply_filters( 'mitoschk_journeys', $journeys );
		return is_array( $journeys ) ? $journeys : array();
	}

	public static function get( $id ) {
		$all = self::all();
		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/** Procedure codes that the journeys need, so the sync always fetches them. */
	public static function procedure_ids() {
		$ids = array();
		foreach ( self::all() as $j ) {
			foreach ( (array) ( $j['steps'] ?? array() ) as $s ) {
				if ( ! empty( $s['procedure'] ) ) {
					$ids[] = (string) $s['procedure'];
				}
			}
		}
		return array_unique( $ids );
	}
}
