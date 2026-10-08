( function () {
	'use strict';
	var cfg = window.MitosChecklist || {};
	var S = cfg.strings || {};

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( text != null ) { n.textContent = text; }
		return n;
	}
	function fmt( str ) {
		var a = arguments;
		var i = 1;
		return str.replace( /%(\d\$)?[ds]/g, function () { return a[ i++ ]; } );
	}
	function link( url, text ) {
		var a = el( 'a', null, text || url );
		a.href = url;
		a.target = '_blank';
		a.rel = 'noopener';
		return a;
	}
	function get( path ) {
		return fetch( cfg.rest + path ).then( function ( r ) {
			if ( ! r.ok ) { throw new Error( r.status ); }
			return r.json();
		} );
	}
	function date( ts ) {
		return new Date( ts * 1000 ).toLocaleDateString( cfg.dateFmt || 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' } );
	}
	function load( id ) {
		try { return JSON.parse( localStorage.getItem( 'mitoschk:' + id ) ) || {}; } catch ( e ) { return {}; }
	}
	function save( id, state ) {
		try { localStorage.setItem( 'mitoschk:' + id, JSON.stringify( state ) ); } catch ( e ) {}
	}

	/* Keep sticky bars below the WordPress admin bar and stack the document bar under the journey bar. */
	function stickyOffsets( root ) {
		var ab = document.getElementById( 'wpadminbar' );
		var top = ab && getComputedStyle( ab ).position === 'fixed' ? ab.offsetHeight : 0;
		var site = parseInt( getComputedStyle( root ).getPropertyValue( '--mitoschk-site-top' ), 10 ) || 0;
		root.style.setProperty( '--mitoschk-top', ( top + site ) + 'px' );
		var apply = function () {
			root.style.setProperty( '--mitoschk-bar', ( root.jbar ? root.jbar.offsetHeight : 0 ) + 'px' );
		};
		apply();
		window.addEventListener( 'resize', apply );
		setTimeout( apply, 300 );
	}

	function footer( root ) {
		var f = el( 'p', 'mitoschk-foot' );
		f.appendChild( document.createTextNode( S.sourceLead + ' ' ) );
		f.appendChild( link( 'https://mitos.gov.gr', S.sourceName ) );
		f.appendChild( document.createTextNode( ' · ' ) );
		f.appendChild( link( 'https://creativecommons.org/licenses/by-sa/4.0/deed.el', S.licence ) );
		root.appendChild( f );
		root.appendChild( el( 'p', 'mitoschk-foot', S.adapted ) );
		root.appendChild( el( 'p', 'mitoschk-foot', S.disclaimer ) );
		var all = el( 'p', 'mitoschk-foot' );
		var b = el( 'button', 'mitoschk-print mitoschk-danger', S.resetAll );
		b.type = 'button';
		b.onclick = resetAll;
		all.appendChild( b );
		root.appendChild( all );
	}

	/* Delete every key this plugin stored, then reload so inputs and searches are cleared too. */
	function resetAll() {
		if ( ! window.confirm( S.confirmAll ) ) { return; }
		try {
			Object.keys( localStorage ).filter( function ( k ) { return k.indexOf( 'mitoschk:' ) === 0; } )
				.forEach( function ( k ) { localStorage.removeItem( k ); } );
			sessionStorage.setItem( 'mitoschk-cleared', '1' );
		} catch ( e ) {}
		history.replaceState( null, '', location.pathname + location.search );
		location.reload();
	}

	function section( root, title, items, render ) {
		if ( ! items || ! items.length ) { return; }
		var sec = el( 'section', 'mitoschk-sec' );
		sec.appendChild( el( 'h3', null, title ) );
		var ul = el( 'ul' );
		items.forEach( function ( it, i ) { ul.appendChild( render( it, i ) ); } );
		sec.appendChild( ul );
		root.appendChild( sec );
	}

	function detail( root, p, single, compact ) {
		root.textContent = '';
		var state = load( p.id );
		state.done = state.done || {};

		if ( ! single ) {
			var back = el( 'button', 'mitoschk-back', S.back );
			back.type = 'button';
			back.onclick = function () { history.pushState( null, '', location.pathname + location.search ); ( root._home || list )( root ); };
			root.appendChild( back );
		}
		if ( ! compact ) {
			root.appendChild( el( 'h2', null, p.title ) );
			if ( p.owner ) { root.appendChild( el( 'p', 'mitoschk-owner', p.owner ) ); }
			if ( p.description ) { root.appendChild( el( 'p', null, p.description ) ); }
		}

		if ( p.source_date ) {
			if ( ! state.seen ) { state.seen = p.source_date; save( p.id, state ); }
			else if ( state.seen !== p.source_date ) {
				var ch = el( 'div', 'mitoschk-warn' );
				ch.appendChild( el( 'span', null, fmt( S.changed, new Date( p.source_date ).toLocaleDateString( cfg.dateFmt || 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' } ) ) + ' ' ) );
				var ok = el( 'button', 'mitoschk-back', S.gotIt );
				ok.type = 'button';
				ok.onclick = function () { state.seen = p.source_date; save( p.id, state ); ch.remove(); };
				ch.appendChild( ok );
				root.appendChild( ch );
			}
		}

		var trust = el( 'p', 'mitoschk-trust' );
		if ( p.reviewed ) { trust.appendChild( el( 'span', 'mitoschk-badge', fmt( S.reviewed, date( p.reviewed ) ) ) ); }
		trust.appendChild( el( 'span', null, fmt( S.refreshed, date( p.synced ) ) ) );
		root.appendChild( trust );

		var bar = el( 'div', 'mitoschk-progress' );
		var msg = el( 'p', 'mitoschk-msg' );
		var rem = el( 'ul', 'mitoschk-rem' );
		var track = el( 'div', 'mitoschk-track' );
		var fill = el( 'div', 'mitoschk-fill' );
		track.appendChild( fill );
		bar.appendChild( msg ); bar.appendChild( track );
		root.appendChild( bar );
		root.appendChild( rem );
		stickyOffsets( root );

		function refresh() {
			var docs = p.documents || [];
			if ( ! docs.length ) { msg.textContent = S.noDocs; track.hidden = true; return; }
			var n = docs.filter( function ( d ) { return state.done[ 'd' + d.key ]; } ).length;
			msg.textContent = n === docs.length ? S.allDone : fmt( S.progress, n, docs.length );
			fill.style.width = Math.round( n / docs.length * 100 ) + '%';
			rem.textContent = '';
			if ( n < docs.length ) {
				rem.appendChild( el( 'li', 'mitoschk-remhead', S.remaining ) );
				docs.forEach( function ( d ) {
					if ( ! state.done[ 'd' + d.key ] ) { var t = d.text ? ( d.text.length > 90 ? d.text.slice( 0, 90 ) + '…' : d.text ) : '';
						rem.appendChild( el( 'li', null, [ d.title, t ].filter( Boolean ).join( ': ' ) ) ); }
				} );
			}
		}

		function check( prefix, it, i ) {
			var li = el( 'li', 'mitoschk-item' + ( it.alt ? ' is-alt' : '' ) );
			if ( it.groupStart ) { li.appendChild( el( 'p', 'mitoschk-anyone', S.anyOne ) ); }
			var key = prefix + ( it.key || i );
			var lab = el( 'label' );
			var cb = el( 'input' );
			cb.type = 'checkbox';
			cb.checked = !! state.done[ key ];
			cb.onchange = function () {
				if ( cb.checked ) { state.done[ key ] = 1; } else { delete state.done[ key ]; }
				save( p.id, state );
				refresh();
			};
			lab.appendChild( cb );
			lab.appendChild( el( 'strong', null, ' ' + ( it.title || it.type || '' ) ) );
			if ( it.alt ) { lab.appendChild( el( 'em', null, ' (' + S.alternative + ')' ) ); }
			li.appendChild( lab );
			if ( it.text ) { li.appendChild( el( 'p', null, it.text ) ); }
			if ( it.how ) { li.appendChild( el( 'p', 'mitoschk-small', it.how ) ); }
			if ( it.note ) { li.appendChild( el( 'p', 'mitoschk-small', it.note ) ); }
			if ( it.url ) { li.appendChild( link( it.url ) ); }
			if ( it.source ) {
				var w = el( 'p', 'mitoschk-small', fmt( S.whereGet, '' ) );
				w.appendChild( link( it.source.url, it.source.title || it.source.url ) );
				li.appendChild( w );
			}
			return li;
		}

		( p.documents || [] ).forEach( function ( d, i, all ) {
			d.groupStart = d.alt && ( i === 0 || ! all[ i - 1 ].alt );
		} );

		section( root, S.conditions, p.conditions, function ( c, i ) { return check( 'c', c, i ); } );
		section( root, S.documents, p.documents, function ( d, i ) { d.key = d.key || i + 1; return check( 'd', d, i ); } );
		section( root, S.fees, p.fees, function ( f ) {
			var li = el( 'li', 'mitoschk-item' );
			li.appendChild( el( 'strong', null, f.type ) );
			if ( f.min || f.max ) { li.appendChild( el( 'span', null, ' ' + [ f.min, f.max ].filter( Boolean ).join( '–' ) + ' €' ) ); }
			if ( f.text ) { li.appendChild( el( 'p', null, f.text ) ); }
			return li;
		} );
		section( root, S.steps, p.steps, function ( s, i ) {
			var li = el( 'li', 'mitoschk-item' );
			li.appendChild( el( 'strong', null, ( i + 1 ) + '. ' + s.title ) );
			if ( s.time ) { li.appendChild( el( 'span', 'mitoschk-small', ' (' + s.time + ')' ) ); }
			if ( s.text ) { li.appendChild( el( 'p', null, s.text ) ); }
			if ( s.url ) { li.appendChild( link( s.url ) ); }
			return li;
		} );

		var go = el( 'div', 'mitoschk-go' );
		( p.apply || [] ).forEach( function ( a ) {
			var l = link( a.url, S.apply + ': ' + a.title );
			l.className = 'mitoschk-btn';
			go.appendChild( l );
		} );
		root.appendChild( go );

		section( root, S.legislation, p.legislation, function ( l ) {
			var li = el( 'li' );
			li.appendChild( link( l.url, l.title ) );
			return li;
		} );
		section( root, S.links, p.links.concat( [ { title: S.source, url: p.source_url } ] ), function ( l ) {
			var li = el( 'li' );
			li.appendChild( link( l.url, l.title ) );
			return li;
		} );

		var notes = el( 'section', 'mitoschk-sec mitoschk-notes' );
		notes.appendChild( el( 'h3', null, S.notes ) );
		var ta = el( 'textarea' );
		ta.rows = 4;
		ta.value = state.notes || '';
		ta.oninput = function () { state.notes = ta.value; save( p.id, state ); };
		notes.appendChild( ta );
		root.appendChild( notes );

		var pr = el( 'button', 'mitoschk-print', S.exportPdf );
		pr.type = 'button';
		pr.onclick = function () {
			var out = el( 'div', 'mx' );
			out.appendChild( el( 'h1', null, p.title ) );
			out.appendChild( el( 'p', 'mx-meta', fmt( S.preparedOn, new Date().toLocaleDateString( cfg.dateFmt || 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' } ) ) ) );
			if ( p.description ) { out.appendChild( el( 'p', null, p.description ) ); }
			exportProcedureBlock( out, p );
			exportFooter( out );
			runPrint( out, S.exportName + ' – ' + p.title );
		};
		root.appendChild( pr );

		if ( ! compact ) { footer( root ); }
		refresh();
	}

	/* ---------- journeys ---------- */

	function deadlineFor( step, startISO ) {
		if ( ! step.deadline || ! startISO ) { return null; }
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( startISO );
		if ( ! m ) { return null; }
		if ( step.deadline.days ) { return new Date( Date.UTC( +m[ 1 ], +m[ 2 ] - 1, +m[ 3 ] + step.deadline.days ) ); }
		return new Date( Date.UTC( +m[ 1 ], ( +m[ 2 ] - 1 ) + step.deadline.month_offset, step.deadline.day ) );
	}
	function dayText( d ) {
		return d.toLocaleDateString( cfg.dateFmt || 'en-GB', { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' } );
	}
	function ics( title, d ) {
		function p2( n ) { return ( n < 10 ? '0' : '' ) + n; }
		var ymd = function ( x ) { return x.getUTCFullYear() + p2( x.getUTCMonth() + 1 ) + p2( x.getUTCDate() ); };
		var next = new Date( d.getTime() + 86400000 );
		var lines = [ 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Mitos Checklist//EN', 'BEGIN:VEVENT',
			'UID:' + ymd( d ) + '-' + Math.random().toString( 36 ).slice( 2 ) + '@mitos-checklist',
			'DTSTAMP:' + new Date().toISOString().replace( /[-:]/g, '' ).replace( /\.\d+/, '' ),
			'DTSTART;VALUE=DATE:' + ymd( d ), 'DTEND;VALUE=DATE:' + ymd( next ),
			'SUMMARY:' + title.replace( /[,;\n]/g, ' ' ),
			'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' + title.replace( /[,;\n]/g, ' ' ), 'TRIGGER:-P3D', 'END:VALARM',
			'END:VEVENT', 'END:VCALENDAR' ];
		var a = el( 'a', 'mitoschk-btn' );
		a.textContent = S.remind;
		a.href = URL.createObjectURL( new Blob( [ lines.join( '\r\n' ) ], { type: 'text/calendar' } ) );
		a.download = 'reminder.ics';
		return a;
	}

	function visibleSteps( j, st ) {
		return ( j.steps || [] ).filter( function ( s ) {
			var cond = s.show_if || {};
			var only = s.show_only || {};
			return Object.keys( cond ).every( function ( k ) { return st.answers[ k ] !== 'yes' || cond[ k ] !== 'no'; } ) &&
				Object.keys( only ).every( function ( k ) { return st.answers[ k ] === only[ k ]; } );
		} );
	}

	/* ---------- PDF export (print layout; the browser saves it as PDF) ---------- */

	function box( done ) { return done ? '☑ ' : '☐ '; }
	function urlLine( ul, title, url ) {
		var li = el( 'li' );
		li.appendChild( document.createTextNode( ( title ? title + ': ' : '' ) + url ) );
		ul.appendChild( li );
	}

	function exportProcedureBlock( out, p, stepState ) {
		var ps = load( p.id );
		ps.done = ps.done || {};
		var docs = p.documents || [];
		if ( docs.length ) {
			out.appendChild( el( 'h4', null, S.documents ) );
			var ul = el( 'ul', 'mx-list' );
			docs.forEach( function ( d, i ) {
				var li = el( 'li', null, box( ps.done[ 'd' + ( d.key || i + 1 ) ] ) + ( d.title || '' ) + ( d.text ? ' – ' + d.text : '' ) );
				ul.appendChild( li );
			} );
			out.appendChild( ul );
		}
		if ( ( p.conditions || [] ).length ) {
			out.appendChild( el( 'h4', null, S.conditions ) );
			var cu = el( 'ul', 'mx-list' );
			p.conditions.forEach( function ( c, i ) {
				cu.appendChild( el( 'li', null, box( ps.done[ 'c' + i ] ) + ( c.type ? c.type + ': ' : '' ) + c.text ) );
			} );
			out.appendChild( cu );
		}
		if ( ( p.fees || [] ).length ) {
			out.appendChild( el( 'h4', null, S.fees ) );
			var fu = el( 'ul', 'mx-list' );
			p.fees.forEach( function ( f ) { fu.appendChild( el( 'li', null, f.type + ( f.text ? ' – ' + f.text : '' ) ) ); } );
			out.appendChild( fu );
		}
		var links = el( 'ul', 'mx-links' );
		( p.apply || [] ).forEach( function ( a ) { urlLine( links, S.apply, a.url ); } );
		urlLine( links, S.officialDesc, p.source_url );
		( p.legislation || [] ).forEach( function ( l ) { urlLine( links, l.title, l.url ); } );
		out.appendChild( el( 'h4', null, S.links ) );
		out.appendChild( links );
		if ( ps.notes ) {
			out.appendChild( el( 'h4', null, S.notesShort ) );
			out.appendChild( el( 'p', 'mx-notes', ps.notes ) );
		}
	}

	function runPrint( node, title ) {
		var old = document.title;
		node.id = 'mitoschk-export';
		document.body.appendChild( node );
		document.body.classList.add( 'mitoschk-printing' );
		document.title = title;
		var cleanup = function () {
			document.body.classList.remove( 'mitoschk-printing' );
			document.title = old;
			node.remove();
			window.removeEventListener( 'afterprint', cleanup );
		};
		window.addEventListener( 'afterprint', cleanup );
		setTimeout( function () { window.print(); }, 50 );
	}

	function exportFooter( out ) {
		out.appendChild( el( 'p', 'mx-foot', S.sourceLead + ' ' + S.sourceName + ' · mitos.gov.gr · ' + S.licence + ' (creativecommons.org/licenses/by-sa/4.0). ' + S.adapted ) );
		out.appendChild( el( 'p', 'mx-foot', S.disclaimer ) );
	}

	function exportJourney( j, jid, st ) {
		var steps = visibleSteps( j, st );
		return Promise.all( steps.map( function ( s ) {
			return get( 'procedures/' + s.procedure ).catch( function () { return null; } );
		} ) ).then( function ( procs ) {
			var out = el( 'div', 'mx' );
			out.appendChild( el( 'h1', null, j.title ) );
			out.appendChild( el( 'p', 'mx-meta', fmt( S.preparedOn, new Date().toLocaleDateString( cfg.dateFmt || 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' } ) ) ) );
			var answered = ( j.questions || [] ).filter( function ( q ) { return st.answers[ q.id ]; } );
			if ( answered.length ) {
				var au = el( 'ul', 'mx-list' );
				answered.forEach( function ( q ) { au.appendChild( el( 'li', null, q.label + ' → ' + S[ st.answers[ q.id ] ] ) ); } );
				out.appendChild( au );
			}
			steps.forEach( function ( s, i ) {
				var sec = el( 'section', 'mx-step' );
				sec.appendChild( el( 'h2', null, box( st.done[ s.id ] ) + ( i + 1 ) + '. ' + ( s.title || s.procedure ) ) );
				if ( s.note ) { sec.appendChild( el( 'p', null, s.note ) ); }
				if ( s.ask_date && st.dates[ s.id ] ) { sec.appendChild( el( 'p', 'mx-meta', s.ask_date + ': ' + dayText( new Date( st.dates[ s.id ] + 'T00:00:00Z' ) ) ) ); }
				if ( s.deadline ) {
					var d = deadlineFor( s, st.dates[ s.deadline.from ] );
					if ( d ) { sec.appendChild( el( 'p', 'mx-deadline', fmt( S.deadline, dayText( d ) ) ) ); }
				}
				if ( procs[ i ] ) { exportProcedureBlock( sec, procs[ i ] ); }
				else { var l = el( 'ul', 'mx-links' ); urlLine( l, S.officialDesc, s.source_url ); sec.appendChild( l ); }
				( s.links || [] ).forEach( function ( x ) { var u = el( 'ul', 'mx-links' ); urlLine( u, x.title, x.url ); sec.appendChild( u ); } );
				out.appendChild( sec );
			} );
			exportFooter( out );
			return out;
		} );
	}

	function journey( root, j, jid ) {
		root.textContent = '';
		var key = 'journey-' + jid;
		var st = load( key );
		st.answers = st.answers || {};
		st.done = st.done || {};
		st.dates = st.dates || {};
		function persist() { save( key, st ); }

		if ( root._home ) {
			var jb = el( 'button', 'mitoschk-back', S.backGuide );
			jb.type = 'button';
			jb.onclick = function () { history.pushState( null, '', location.pathname + location.search ); root._home( root ); };
			root.appendChild( jb );
		}
		root.appendChild( el( 'h2', null, j.title ) );
		root.appendChild( el( 'p', null, j.intro ) );

		var qbox = el( 'div', 'mitoschk-q' );
		( j.questions || [] ).forEach( function ( q ) {
			var f = el( 'fieldset' );
			f.appendChild( el( 'legend', null, q.label ) );
			[ 'yes', 'no' ].forEach( function ( v ) {
				var lab = el( 'label', 'mitoschk-opt' );
				var r = el( 'input' );
				r.type = 'radio'; r.name = key + '-' + q.id; r.value = v;
				r.checked = st.answers[ q.id ] === v;
				r.onchange = function () { st.answers[ q.id ] = v; persist(); journey( root, j, jid ); };
				lab.appendChild( r );
				lab.appendChild( document.createTextNode( ' ' + S[ v ] ) );
				f.appendChild( lab );
			} );
			if ( q.warn && st.answers[ q.id ] === q.warn_if ) { f.appendChild( el( 'p', 'mitoschk-warn', q.warn ) ); }
			qbox.appendChild( f );
		} );
		root.appendChild( qbox );

		var jbar = el( 'div', 'mitoschk-progress mitoschk-jbar' );
		var prog = el( 'p', 'mitoschk-msg' );
		var track = el( 'div', 'mitoschk-track' );
		var fill = el( 'div', 'mitoschk-fill' );
		track.appendChild( fill );
		jbar.appendChild( prog ); jbar.appendChild( track );
		root.appendChild( jbar );
		root.jbar = jbar;
		stickyOffsets( root );
		var openSteps = {};
		var list = el( 'ol', 'mitoschk-steps' );
		root.appendChild( list );

		var reset = el( 'button', 'mitoschk-print', S.startOver );
		reset.type = 'button';
		reset.onclick = function () {
			if ( ! window.confirm( S.confirmReset ) ) { return; }
			try {
				localStorage.removeItem( 'mitoschk:' + key );
				( j.steps || [] ).forEach( function ( s ) { localStorage.removeItem( 'mitoschk:' + s.procedure ); } );
			} catch ( e ) {}
			window.scrollTo( 0, root.getBoundingClientRect().top + window.pageYOffset - 20 );
			journey( root, j, jid );
		};
		var exp = el( 'button', 'mitoschk-btn mitoschk-export', S.exportPdf );
		exp.type = 'button';
		exp.onclick = function () {
			exp.disabled = true;
			exportJourney( j, jid, st ).then( function ( node ) { exp.disabled = false; runPrint( node, S.exportName + ' – ' + j.title ); } );
		};
		root.insertBefore( exp, jbar.nextSibling );
		root.appendChild( reset );
		footer( root );

		function draw() {
			// Re-render the question warnings by redrawing the whole journey view only on answer change.
			var steps = visibleSteps( j, st );
			var done = steps.filter( function ( s ) { return st.done[ s.id ]; } ).length;
			prog.textContent = fmt( S.stepsDone, done, steps.length );
			fill.style.width = steps.length ? Math.round( done / steps.length * 100 ) + '%' : '0';
			list.textContent = '';
			steps.forEach( function ( s, i ) {
				var li = el( 'li', 'mitoschk-step' + ( st.done[ s.id ] ? ' is-done' : '' ) );
				var head = el( 'label', 'mitoschk-stephead' );
				var cb = el( 'input' );
				cb.type = 'checkbox'; cb.checked = !! st.done[ s.id ];
				cb.onchange = function () { if ( cb.checked ) { st.done[ s.id ] = 1; } else { delete st.done[ s.id ]; } persist(); draw(); };
				head.appendChild( cb );
				head.appendChild( el( 'strong', null, ' ' + ( i + 1 ) + '. ' + ( s.title || s.procedure ) ) );
				if ( s.reviewed ) { head.appendChild( el( 'span', 'mitoschk-badge', '✓' ) ); }
				li.appendChild( head );
				if ( s.note ) { li.appendChild( el( 'p', null, s.note ) ); }
				if ( s.after && ! st.done[ s.after ] ) { li.appendChild( el( 'p', 'mitoschk-small', S.waitFor ) ); }

				if ( s.ask_date ) {
					var dl = el( 'label', 'mitoschk-date', s.ask_date + ': ' );
					var di = el( 'input' );
					di.type = 'date'; di.value = st.dates[ s.id ] || '';
					di.onchange = function () { st.dates[ s.id ] = di.value; persist(); draw(); };
					dl.appendChild( di );
					li.appendChild( dl );
				}
				if ( s.deadline ) {
					var d = deadlineFor( s, st.dates[ s.deadline.from ] );
					if ( d ) {
						var past = d.getTime() < Date.now() - 86400000;
						li.appendChild( el( 'p', past ? 'mitoschk-warn' : 'mitoschk-deadline', past ? fmt( S.deadlinePast, dayText( d ) ) : fmt( S.deadline, dayText( d ) ) ) );
						if ( ! past ) { li.appendChild( ics( fmt( S.remindTitle, s.title || '' ), d ) ); }
					}
				}

				var lk = el( 'div', 'mitoschk-go' );
				( s.apply || [] ).forEach( function ( a ) {
					var l = link( a.url, S.apply + ': ' + a.title );
					l.className = 'mitoschk-btn';
					lk.appendChild( l );
				} );
				li.appendChild( lk );
				var more = el( 'ul', 'mitoschk-links' );
				( s.links || [] ).concat( [ { title: S.officialDesc, url: s.source_url } ] ).forEach( function ( l ) {
					var x = el( 'li' );
					x.appendChild( link( l.url, l.title ) );
					more.appendChild( x );
				} );
				li.appendChild( more );

				if ( s.available ) {
					var box = el( 'div', 'mitoschk-inline' );
					var tg = el( 'button', 'mitoschk-back' );
					tg.type = 'button';
					var loaded = false;
					var show = function ( on ) {
						openSteps[ s.id ] = on;
						box.hidden = ! on;
						tg.textContent = on ? S.hideList : S.openList;
						if ( on && ! loaded ) {
							loaded = true;
							box.textContent = S.loading;
							get( 'procedures/' + s.procedure ).then( function ( p ) { detail( box, p, true, true ); } )
								.catch( function () { box.textContent = S.error; } );
						}
					};
					tg.onclick = function () { show( box.hidden ); };
					show( !! openSteps[ s.id ] );
					li.appendChild( tg );
					li.appendChild( box );
				} else {
					li.appendChild( el( 'p', 'mitoschk-small', S.unavailable ) );
				}
				list.appendChild( li );
			} );
		}
		draw();
	}

	function open( root, id, single ) {
		root.textContent = S.loading;
		if ( root._home ) { root.scrollIntoView( { block: 'start' } ); }
		get( 'procedures/' + id ).then( function ( p ) { detail( root, p, single ); } )
			.catch( function () { root.textContent = S.error; } );
	}

	function list( root ) {
		root.textContent = '';
		var q = el( 'input', 'mitoschk-search' );
		q.type = 'search';
		q.placeholder = S.search;
		q.setAttribute( 'aria-label', S.search );
		var ul = el( 'ul', 'mitoschk-list' );
		root.appendChild( q ); root.appendChild( ul );
		get( 'procedures' ).then( function ( items ) {
			function draw() {
				var t = q.value.trim().toLowerCase();
				ul.textContent = '';
				var shown = items.filter( function ( it ) {
					return ! t || ( it.title + ' ' + it.description + ' ' + it.owner ).toLowerCase().indexOf( t ) > -1;
				} );
				if ( ! shown.length ) { ul.appendChild( el( 'li', null, S.none ) ); }
				shown.forEach( function ( it ) {
					var li = el( 'li' );
					var b = el( 'button', 'mitoschk-row' );
					b.type = 'button';
					b.appendChild( el( 'strong', null, it.title ) );
					if ( it.owner ) { b.appendChild( el( 'span', 'mitoschk-small', it.owner ) ); }
					b.onclick = function () { history.pushState( null, '', '#mitos-' + it.id ); open( root, it.id, false ); };
					li.appendChild( b );
					ul.appendChild( li );
				} );
			}
			q.oninput = draw;
			draw();
			footer( root );
		} ).catch( function () { root.textContent = S.error; } );
	}

	function guide( root ) {
		root.textContent = '';
		root._home = guide;
		root.appendChild( el( 'h2', null, S.guideTitle ) );
		root.appendChild( el( 'p', null, S.guideIntro ) );

		var jh = el( 'h3', null, S.journeys );
		var jl = el( 'ul', 'mitoschk-list mitoschk-cards' );
		root.appendChild( jh ); root.appendChild( jl );
		get( 'journeys' ).then( function ( items ) {
			items.forEach( function ( j ) {
				var li = el( 'li' );
				var b = el( 'button', 'mitoschk-row' );
				b.type = 'button';
				b.appendChild( el( 'strong', null, j.title ) );
				b.appendChild( el( 'span', 'mitoschk-small', j.intro + ' · ' + fmt( S.stepsCount, j.steps ) ) );
				b.onclick = function () { history.pushState( null, '', '#journey-' + j.id ); openJourney( root, j.id ); };
				li.appendChild( b );
				jl.appendChild( li );
			} );
		} ).catch( function () { jl.textContent = S.error; } );

		root.appendChild( el( 'h3', null, S.searchAll ) );
		var q = el( 'input', 'mitoschk-search' );
		q.type = 'search'; q.placeholder = S.search; q.setAttribute( 'aria-label', S.searchAll );
		var res = el( 'ul', 'mitoschk-list' );
		root.appendChild( q ); root.appendChild( res );
		var timer;
		q.oninput = function () {
			clearTimeout( timer );
			timer = setTimeout( function () {
				var t = q.value.trim();
				res.textContent = '';
				if ( t.length < 2 ) { return; }
				get( 'search?q=' + encodeURIComponent( t ) ).then( function ( r ) {
					res.textContent = '';
					if ( ! r.indexed ) { res.appendChild( el( 'li', null, S.noIndex ) ); return; }
					if ( ! r.results.length ) { res.appendChild( el( 'li', null, S.none ) ); return; }
					r.results.forEach( function ( it ) {
						var li = el( 'li' );
						var b = el( 'button', 'mitoschk-row' );
						b.type = 'button';
						b.appendChild( el( 'strong', null, it.title ) );
						b.onclick = function () { history.pushState( null, '', '#mitos-' + it.id ); open( root, it.id, false ); };
						li.appendChild( b );
						res.appendChild( li );
					} );
				} ).catch( function () { res.textContent = S.error; } );
			}, 250 );
		};
		footer( root );
	}

	function toTop( root ) {
		if ( root._home && root.scrollIntoView ) { root.scrollIntoView( { block: 'start' } ); }
	}

	function openJourney( root, id ) {
		root.textContent = S.loading;
		toTop( root );
		get( 'journeys/' + id ).then( function ( j ) { journey( root, j, id ); } )
			.catch( function () { root.textContent = S.error; } );
	}

	function route( root ) {
		var h = location.hash;
		if ( h && ! /^#(mitos-\d+|journey-[a-z0-9_-]+)$/.test( h ) ) { return; } // page anchors such as #faq
		var m = /^#mitos-(\d+)$/.exec( h );
		var j = /^#journey-([a-z0-9_-]+)$/.exec( h );
		if ( m ) { open( root, m[ 1 ], false ); } else if ( j ) { openJourney( root, j[ 1 ] ); } else { guide( root ); }
	}

	function start( root ) {
		if ( root.getAttribute( 'data-guide' ) ) {
			route( root );
			window.addEventListener( 'popstate', function () { route( root ); } );
			return;
		}
		var jid = root.getAttribute( 'data-journey' );
		if ( jid ) {
			get( 'journeys/' + jid ).then( function ( j ) { journey( root, j, jid ); } )
				.catch( function () { root.textContent = S.error; } );
			return;
		}
		var fixed = root.getAttribute( 'data-id' );
		if ( fixed ) { open( root, fixed, true ); return; }
		var m = /^#mitos-(\d+)$/.exec( location.hash );
		if ( m ) { open( root, m[ 1 ], false ); } else { list( root ); }
		window.addEventListener( 'popstate', function () {
			var h = /^#mitos-(\d+)$/.exec( location.hash );
			if ( h ) { open( root, h[ 1 ], false ); } else { list( root ); }
		} );
	}

	document.querySelectorAll( '.mitoschk' ).forEach( start );
	try {
		if ( sessionStorage.getItem( 'mitoschk-cleared' ) ) {
			sessionStorage.removeItem( 'mitoschk-cleared' );
			var first = document.querySelector( '.mitoschk' );
			if ( first ) {
				var n = el( 'p', 'mitoschk-deadline', S.clearedAll );
				n.setAttribute( 'role', 'status' );
				first.parentNode.insertBefore( n, first );
				setTimeout( function () { n.remove(); }, 6000 );
			}
		}
	} catch ( e ) {}
}() );
