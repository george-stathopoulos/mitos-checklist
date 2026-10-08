<?php
defined( 'ABSPATH' ) || exit;

/**
 * Standalone mode: when enabled, the plugin serves its own designed Greek
 * website on the front end instead of the active theme. wp-admin, wp-login,
 * the REST API, feeds, cron and AJAX are untouched.
 */
class Mitoschk_Site {

	const OPT_ON    = 'mitoschk_takeover';
	const OPT_BRAND = 'mitoschk_brand';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 1 );
	}

	public static function activate() {
		if ( false === get_option( self::OPT_ON, false ) ) {
			add_option( self::OPT_ON, '1', '', false );
		}
	}

	public static function enabled() {
		// On unless the owner switched it off (also covers upgrades, where no activation runs).
		return '0' !== (string) get_option( self::OPT_ON, '1' );
	}

	public static function brand() {
		$b = trim( (string) get_option( self::OPT_BRAND, '' ) );
		return '' !== $b ? $b : 'Οδηγός Γραφειοκρατίας';
	}

	public static function maybe_render() {
		if ( ! self::enabled() ) {
			return;
		}
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots() || is_trackback() || is_customize_preview()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}
		// Administrators can look at the normal theme with ?mitoschk=theme.
		if ( isset( $_GET['mitoschk'] ) && 'theme' === $_GET['mitoschk'] && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( ! apply_filters( 'mitoschk_takeover', true ) ) {
			return;
		}
		if ( is_404() ) {
			status_header( 404 );
		}
		header( 'Content-Type: text/html; charset=UTF-8' );
		self::page();
		exit;
	}

	private static function page() {
		$brand = self::brand();
		$ver   = MITOSCHK_VERSION;
		$boot  = array(
			'rest'    => esc_url_raw( rest_url( 'mitos-checklist/v1/' ) ),
			'strings' => Mitoschk_Strings::all(),
			'dateFmt' => 'el-GR',
		);
		$desc  = 'Ετοιμάστε τα δικαιολογητικά σας για έναρξη, μεταβολή ή διακοπή επιχείρησης με επίσημα στοιχεία από τον Μίτο: τι χρειάζεστε, με ποια σειρά και μέχρι πότε.';
		?>
<!doctype html>
<html lang="el">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $brand ); ?> – Η γραφειοκρατία, βήμα βήμα</title>
<meta name="description" content="<?php echo esc_attr( $desc ); ?>">
<meta property="og:title" content="<?php echo esc_attr( $brand ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $desc ); ?>">
<meta name="color-scheme" content="light">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 36 36'%3E%3Cdefs%3E%3CclipPath id='r'%3E%3Crect width='36' height='36' rx='10'/%3E%3C/clipPath%3E%3C/defs%3E%3Cg clip-path='url(%23r)'%3E%3Crect width='36' height='36' fill='%23fff'/%3E%3Cg fill='%230d5eaf'%3E%3Crect y='0' width='36' height='4'/%3E%3Crect y='8' width='36' height='4'/%3E%3Crect y='16' width='36' height='4'/%3E%3Crect y='24' width='36' height='4'/%3E%3Crect y='32' width='36' height='4'/%3E%3Crect width='20' height='20'/%3E%3C/g%3E%3Cg fill='%23fff'%3E%3Crect x='8' width='4' height='20'/%3E%3Crect y='8' width='20' height='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E">
<link rel="stylesheet" href="<?php echo esc_url( MITOSCHK_URL . 'assets/app.css?ver=' . $ver ); ?>">
<link rel="stylesheet" href="<?php echo esc_url( MITOSCHK_URL . 'assets/site.css?ver=' . $ver ); ?>">
</head>
<body class="mc-site">
<a class="mc-skip" href="#app">Μετάβαση στο περιεχόμενο</a>

<header class="mc-header">
	<div class="mc-wrap mc-header-in">
		<a class="mc-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><svg class="mc-logo-svg" viewBox="0 0 36 36" width="36" height="36" aria-hidden="true" focusable="false"><defs><clipPath id="mcr"><rect width="36" height="36" rx="10"/></clipPath></defs><g clip-path="url(#mcr)"><rect width="36" height="36" fill="#fff"/><g fill="#0d5eaf"><rect y="0" width="36" height="4"/><rect y="8" width="36" height="4"/><rect y="16" width="36" height="4"/><rect y="24" width="36" height="4"/><rect y="32" width="36" height="4"/><rect width="20" height="20"/></g><g fill="#fff"><rect x="8" y="0" width="4" height="20"/><rect x="0" y="8" width="20" height="4"/></g></g></svg><span><?php echo esc_html( $brand ); ?></span></a>
		<nav class="mc-nav" aria-label="Κύριο μενού">
			<a href="#app">Οδηγοί</a>
			<a href="#how">Πώς λειτουργεί</a>
			<a href="#trust">Αξιοπιστία</a>
			<a href="#faq">Ερωτήσεις</a>
		</nav>
	</div>
</header>

<main>
	<section class="mc-hero">
		<div class="mc-wrap">
			<p class="mc-eyebrow">Για ελεύθερους επαγγελματίες και μικρές επιχειρήσεις</p>
			<h1>Η γραφειοκρατία, <span>βήμα βήμα</span></h1>
			<p class="mc-rotor-line" aria-label="Δείτε τι χρειάζεστε, με ποια σειρά και μέχρι πότε.">
				<span aria-hidden="true">Δείτε </span><span class="mc-rotor" aria-hidden="true" data-words='["τι χρειάζεστε","με ποια σειρά","μέχρι πότε","πού να πάτε","από επίσημη πηγή"]'>τι χρειάζεστε</span>
			</p>
			<p class="mc-lead">Ετοιμάστε τα δικαιολογητικά σας για έναρξη, μεταβολή ή διακοπή επιχείρησης, με επίσημα στοιχεία από το Εθνικό Μητρώο Διοικητικών Διαδικασιών (Μίτος).</p>
			<div class="mc-cta">
				<a class="mc-btn mc-btn-primary" href="#app">Ξεκινήστε τώρα</a>
				<a class="mc-btn" href="#how">Πώς λειτουργεί</a>
			</div>
			<ul class="mc-chips">
				<li>Επίσημα στοιχεία</li>
				<li>Χωρίς λογαριασμό</li>
				<li>Η πρόοδός σας μένει στη συσκευή σας</li>
			</ul>
		</div>
	</section>

	<div class="mc-meander" aria-hidden="true"></div>

	<section class="mc-section mc-app" id="app">
		<div class="mc-wrap mc-app-in">
			<div class="mitoschk" data-guide="1"><p>Φόρτωση…</p></div>
			<noscript><p>Η εφαρμογή χρειάζεται JavaScript.</p></noscript>
		</div>
	</section>

	<section class="mc-section" id="how">
		<div class="mc-wrap">
			<h2>Πώς λειτουργεί</h2>
			<ol class="mc-cards mc-steps">
				<li><span class="mc-num">1</span><h3>Διαλέγετε οδηγό</h3><p>Έναρξη, μεταβολή ή διακοπή επιχείρησης. Απαντάτε σε δύο-τρεις ερωτήσεις και κρύβουμε ό,τι δεν σας αφορά.</p></li>
				<li><span class="mc-num">2</span><h3>Ακολουθείτε τα βήματα</h3><p>Κάθε βήμα έχει δικαιολογητικά, σύνδεσμο προς την επίσημη υπηρεσία και την περιγραφή στον Μίτο.</p></li>
				<li><span class="mc-num">3</span><h3>Δεν χάνετε προθεσμίες</h3><p>Βάζετε την ημερομηνία και βλέπετε την προθεσμία. Κατεβάζετε υπενθύμιση για το ημερολόγιό σας.</p></li>
			</ol>
		</div>
	</section>

	<section class="mc-section mc-alt" id="trust">
		<div class="mc-wrap">
			<h2>Γιατί να το εμπιστευτείτε</h2>
			<div class="mc-cards">
				<div><h3>Από επίσημη πηγή</h3><p>Τα στοιχεία προέρχονται από τον <a href="https://mitos.gov.gr" target="_blank" rel="noopener">Μίτο</a>. Δίπλα σε κάθε διαδικασία βλέπετε πότε ενημερώθηκε.</p></div>
				<div><h3>Σας λέμε τι άλλαξε</h3><p>Αν μια διαδικασία ενημερωθεί μετά την τελευταία φορά που την είδατε, εμφανίζεται ειδοποίηση.</p></div>
				<div><h3>Ιδιωτικό από τον σχεδιασμό</h3><p>Τα τικ, οι ημερομηνίες και οι σημειώσεις σας αποθηκεύονται μόνο στον περιηγητή σας. Δεν στέλνονται πουθενά.</p></div>
			</div>
		</div>
	</section>

	<section class="mc-section" id="faq">
		<div class="mc-wrap mc-narrow">
			<h2>Συχνές ερωτήσεις</h2>
			<details><summary>Είναι επίσημη υπηρεσία του Δημοσίου;</summary><p>Όχι. Είναι ανεξάρτητος οδηγός που οργανώνει δημόσιες πληροφορίες. Δεν συνδέεται με το Ελληνικό Δημόσιο. Η αίτηση υποβάλλεται πάντα στην επίσημη υπηρεσία, στην οποία σας στέλνουμε με σύνδεσμο.</p></details>
			<details><summary>Μπορώ να υποβάλω αίτηση εδώ;</summary><p>Όχι. Σας βοηθάμε να προετοιμαστείτε. Η υποβολή γίνεται στο myAADE, στο gov.gr ή στον e-ΕΦΚΑ.</p></details>
			<details><summary>Πόσο ενημερωμένες είναι οι πληροφορίες;</summary><p>Ανανεώνονται από τον Μίτο καθημερινά και δείχνουμε την ημερομηνία ενημέρωσης. Επιβεβαιώστε πάντα στην επίσημη υπηρεσία πριν υποβάλετε αίτηση.</p></details>
			<details><summary>Αποθηκεύονται τα δεδομένα μου;</summary><p>Τα προσωπικά σας δεδομένα (τικ, ημερομηνίες, σημειώσεις) μένουν στη συσκευή σας. Αν καθαρίσετε τα δεδομένα του περιηγητή ή αλλάξετε συσκευή, δεν θα τα βρείτε.</p></details>
			<details><summary>Τι γίνεται αν δεν βρίσκω τη διαδικασία που θέλω;</summary><p>Χρησιμοποιήστε την αναζήτηση σε όλες τις επίσημες διαδικασίες, στο πεδίο πάνω από τη λίστα. Καλύπτει όλο το μητρώο.</p></details>
		</div>
	</section>
</main>

<div class="mc-meander mc-meander-dark" aria-hidden="true"></div>
<footer class="mc-footer">
	<div class="mc-wrap">
		<p><strong><?php echo esc_html( $brand ); ?></strong> · Ανεξάρτητη υπηρεσία, χωρίς σύνδεση με το Ελληνικό Δημόσιο. Μόνο ενημερωτική χρήση: επιβεβαιώστε πάντα στην επίσημη υπηρεσία πριν υποβάλετε αίτηση.</p>
		<p>Πηγή πληροφοριών: <a href="https://mitos.gov.gr" target="_blank" rel="noopener">Μίτος – Εθνικό Μητρώο Διοικητικών Διαδικασιών</a>, με άδεια <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.el" target="_blank" rel="noopener">CC BY-SA 4.0</a>. Το περιεχόμενο έχει προσαρμοστεί σε λίστες ελέγχου.</p>
		<p><a href="https://github.com/george-stathopoulos/mitos-checklist" target="_blank" rel="noopener">Ανοιχτός κώδικας (WordPress plugin Mitos Checklist)</a></p>
	</div>
</footer>

<script>window.MitosChecklist = <?php echo wp_json_encode( $boot ); ?>;</script>
<script>
(function () {
	var r = document.querySelector('.mc-rotor');
	if (!r || window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
	var words; try { words = JSON.parse(r.getAttribute('data-words')); } catch (e) { return; }
	var i = 0;
	setInterval(function () {
		r.classList.add('is-out');
		setTimeout(function () { i = (i + 1) % words.length; r.textContent = words[i]; r.classList.remove('is-out'); }, 380);
	}, 2600);
}());
</script>
<script src="<?php echo esc_url( MITOSCHK_URL . 'assets/app.js?ver=' . $ver ); ?>" defer></script>
</body>
</html>
		<?php
	}
}
