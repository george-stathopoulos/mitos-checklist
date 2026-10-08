<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ordered multi-procedure journeys. The order and deadlines below come from the
 * official Mitos entries (see docs/sprint-1.md); change them only after checking
 * the sources again. Filter `mitoschk_journeys` to add or edit journeys.
 */
class Mitoschk_Journeys {

	private static function l( $en, $el ) {
		return Mitoschk_Strings::is_greek() ? $el : $en;
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
						'links'     => array(
							array( 'title' => self::l( 'AADE guide: starting a business', 'Οδηγός ΑΑΔΕ: έναρξη επιχειρηματικής δραστηριότητας' ), 'url' => 'https://www.aade.gr/menoy/hristikoi-odigoi/enarxi-epiheirimatikis-drastiriotitas' ),
							array( 'title' => 'myAADE', 'url' => 'https://www1.aade.gr/gsisapps5/myaade/' ),
						),
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

			'close' => array(
				'title'     => self::l( 'Close a sole trader business', 'Διακοπή ατομικής επιχείρησης' ),
				'intro'     => self::l( 'Close with the tax authority first, then end your e-EFKA insurance.', 'Πρώτα η διακοπή στην εφορία, μετά η λήξη ασφάλισης στον e-ΕΦΚΑ.' ),
				'questions' => array(
					array(
						'id'      => 'simple_close',
						'label'   => self::l( 'Are you a living individual closing a sole proprietorship, with no stock or fixed assets left?', 'Είστε εν ζωή φυσικό πρόσωπο που κλείνει ατομική επιχείρηση, χωρίς αποθέματα ή πάγια;' ),
						'warn'    => self::l( 'The online closure covers only that case. Others (bankruptcy, a deceased trader closed by heirs, mergers, cases needing an inspection) go through the AADE "Τα Αιτήματά μου" application. Check the official service.', 'Η ηλεκτρονική διακοπή καλύπτει μόνο αυτή την περίπτωση. Οι υπόλοιπες (πτώχευση, θανών επιτηδευματίας από κληρονόμους, μετατροπές, ανάγκη ελέγχου) γίνονται μέσω της εφαρμογής «Τα Αιτήματά μου» της ΑΑΔΕ. Ελέγξτε την επίσημη υπηρεσία.' ),
						'warn_if' => 'no',
					),
				),
				'steps'     => array(
					array(
						'id'        => 'tax_close',
						'procedure' => '643276',
						'note'      => self::l( 'File through myAADE with your TAXISnet codes. Declare within 30 days of the actual cessation; a later declaration is treated as late and tax-code penalties apply. You receive a certificate of cessation in your myAADE mailbox.', 'Υποβολή μέσω myAADE με κωδικούς TAXISnet. Δηλώστε εντός 30 ημερών από την πραγματική παύση· αργότερη δήλωση θεωρείται εκπρόθεσμη και ισχύουν οι κυρώσεις του ΚΦΔ. Η βεβαίωση διακοπής εργασιών αποστέλλεται στο γραμματοκιβώτιό σας στο myAADE.' ),
						'ask_date'  => self::l( 'Date the activity actually stopped', 'Ημερομηνία οριστικής παύσης εργασιών' ),
						'deadline'  => array( 'days' => 30, 'from' => 'tax_close' ),
					),
					array(
						'id'        => 'efka_end',
						'procedure' => '861414',
						'after'     => 'tax_close',
						'note'      => self::l( 'Possible only after the tax closure. About 3 minutes online; pick the activity codes you closed and enter the closure date. It does not apply if you still carry on another activity that needs e-EFKA insurance.', 'Γίνεται μόνο μετά τη διακοπή στην εφορία. Περίπου 3 λεπτά ηλεκτρονικά· επιλέγετε τους ΚΑΔ που διακόψατε και την ημερομηνία διακοπής. Δεν ισχύει αν ασκείτε άλλη δραστηριότητα που απαιτεί ασφάλιση στον e-ΕΦΚΑ.' ),
					),
				),
			),
			'change' => array(
				'title'     => self::l( 'Change business details or activity', 'Μεταβολή στοιχείων ή δραστηριότητας επιχείρησης' ),
				'intro'     => self::l( 'Tell the tax authority first. If your activity codes changed, update e-EFKA too.', 'Πρώτα ενημερώστε την εφορία. Αν άλλαξαν οι ΚΑΔ, ενημερώστε και τον e-ΕΦΚΑ.' ),
				'questions' => array(
					array( 'id' => 'changes_kad', 'label' => self::l( 'Are you adding or removing an activity code (ΚΑΔ)?', 'Προσθέτετε ή αφαιρείτε ΚΑΔ (δραστηριότητα);' ) ),
				),
				'steps'     => array(
					array(
						'id'        => 'tax_change',
						'procedure' => '228727',
						'note'      => self::l( 'Covers a change of registered office or premises, activity codes, VAT regime and more. Online via myAADE (Μητρώο & Επικοινωνία) with TAXISnet codes; some changes need form Δ211 through "Τα Αιτήματά μου". Declare within 30 days of the change. Changes with different effective dates must be declared separately.', 'Καλύπτει αλλαγή έδρας ή εγκατάστασης, ΚΑΔ, καθεστώτος ΦΠΑ κ.ά. Ηλεκτρονικά μέσω myAADE (Μητρώο & Επικοινωνία) με κωδικούς TAXISnet· ορισμένες αλλαγές απαιτούν το έντυπο Δ211 μέσω «Τα Αιτήματά μου». Δηλώστε εντός 30 ημερών από τη μεταβολή. Μεταβολές με διαφορετική ημερομηνία ισχύος δηλώνονται χωριστά.' ),
						'ask_date'  => self::l( 'Date the change took effect', 'Ημερομηνία ισχύος της μεταβολής' ),
						'deadline'  => array( 'days' => 30, 'from' => 'tax_change' ),
					),
					array(
						'id'        => 'efka_change',
						'procedure' => '411584',
						'after'     => 'tax_change',
						'show_only' => array( 'changes_kad' => 'yes' ),
						'note'      => self::l( 'About 1 minute online. To add an activity code you must be up to date with your contributions. The registry gives no filing deadline for this step.', 'Περίπου 1 λεπτό ηλεκτρονικά. Για προσθήκη ΚΑΔ πρέπει να είστε ενήμεροι στις εισφορές σας. Το μητρώο δεν ορίζει προθεσμία για αυτό το βήμα.' ),
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
		return array_unique( array_merge( $ids, array( '160473' ) ) );
	}
}
