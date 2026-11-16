<?php
/**
 * Plugin Name: Travel Diary Setup
 * Description: Builds a Sri Lanka travel blog on activation: pages (Home, About, Contact), 6 posts with featured images, 3 categories, the main menu, homepage setting and CSS. Also adds a contact form ([td_contact]) with a Messages inbox and a [td_latest] shortcode for the latest posts.
 * Version: 1.0
 * Author: Travel Diary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Contact form ---------- */
add_action( 'init', function () {
	register_post_type( 'td_message', array(
		'labels'       => array( 'name' => 'Messages', 'singular_name' => 'Message', 'menu_name' => 'Messages', 'not_found' => 'No messages yet.' ),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-email-alt',
		'menu_position'=> 26,
		'supports'     => array( 'title', 'editor' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

add_shortcode( 'td_contact', function () {
	$status = isset( $_GET['td_sent'] ) ? sanitize_text_field( wp_unslash( $_GET['td_sent'] ) ) : '';
	ob_start();
	?>
	<style>
		.td-form{max-width:640px;margin:20px 0}
		.td-form label{display:block;font-weight:600;margin-bottom:14px}
		.td-form input[type=text],.td-form input[type=email],.td-form textarea{display:block;width:100%;margin-top:6px;padding:12px 14px;border:1px solid #d7d2c6;border-radius:10px;font:inherit}
		.td-form button{background:#1f6f78;color:#fff;border:0;border-radius:30px;padding:12px 32px;font-weight:600;cursor:pointer}
		.td-form button:hover{background:#124a50}
		.td-form .td-hp{position:absolute;left:-9999px}
		.td-note{max-width:640px;padding:14px 18px;border-radius:10px;margin-bottom:16px}
		.td-ok{background:#e6f4f1;color:#124a50}.td-err{background:#fdecea;color:#b71c1c}
	</style>
	<?php if ( '1' === $status ) : ?>
		<div class="td-note td-ok">Thank you! Your message has been received.</div>
	<?php elseif ( '0' === $status ) : ?>
		<div class="td-note td-err">Please enter your name, a valid email and a message.</div>
	<?php endif; ?>
	<form class="td-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="td_contact_submit">
		<input type="hidden" name="td_back" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'td_contact', 'td_nonce' ); ?>
		<label>Your name <input type="text" name="td_name" required></label>
		<label>Email address <input type="email" name="td_email" required></label>
		<label>Message <textarea name="td_message" rows="6" required></textarea></label>
		<p class="td-hp" aria-hidden="true"><label>Leave empty <input type="text" name="td_website" tabindex="-1" autocomplete="off"></label></p>
		<button type="submit">Send message</button>
	</form>
	<?php
	return ob_get_clean();
} );

function td_contact_handle() {
	$back  = isset( $_POST['td_back'] ) ? esc_url_raw( wp_unslash( $_POST['td_back'] ) ) : home_url( '/' );
	$nonce = isset( $_POST['td_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['td_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'td_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'td_sent', '0', $back ) );
		exit;
	}
	if ( ! empty( $_POST['td_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'td_sent', '1', $back ) );
		exit;
	}
	$name    = isset( $_POST['td_name'] ) ? sanitize_text_field( wp_unslash( $_POST['td_name'] ) ) : '';
	$email   = isset( $_POST['td_email'] ) ? sanitize_email( wp_unslash( $_POST['td_email'] ) ) : '';
	$message = isset( $_POST['td_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['td_message'] ) ) : '';
	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'td_sent', '0', $back ) );
		exit;
	}
	wp_insert_post( array(
		'post_type'    => 'td_message',
		'post_status'  => 'publish',
		'post_title'   => 'Message from ' . $name,
		'post_content' => 'From: ' . $name . ' <' . $email . ">\n\n" . $message,
	) );
	@wp_mail( get_option( 'admin_email' ), '[Travel Diary] Message from ' . $name, "Name: $name\nEmail: $email\n\n$message\n" );
	wp_safe_redirect( add_query_arg( 'td_sent', '1', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_td_contact_submit', 'td_contact_handle' );
add_action( 'admin_post_td_contact_submit', 'td_contact_handle' );

/* ---------- [td_latest] : latest posts as cards (updates automatically) ---------- */
function td_cards_html( $q ) {
	$out = '<div class="td-grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$cats = get_the_category();
		$cat  = $cats ? $cats[0]->name : '';
		$out .= '<div class="td-card">';
		if ( has_post_thumbnail() ) {
			$out .= '<a class="td-img" href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( null, 'medium_large' ) . '</a>';
		}
		$out .= '<div class="td-body"><span class="td-cat">' . esc_html( $cat ) . '</span><h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3><p>' . esc_html( wp_trim_words( get_the_excerpt(), 22 ) ) . '</p><a class="td-more" href="' . esc_url( get_permalink() ) . '">Read more &rarr;</a></div></div>';
	}
	wp_reset_postdata();
	return $out . '</div>';
}

add_shortcode( 'td_latest', function ( $atts ) {
	$atts = shortcode_atts( array( 'count' => 6 ), $atts );
	$q    = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => (int) $atts['count'], 'ignore_sticky_posts' => true ) );
	if ( ! $q->have_posts() ) {
		return '<p>No posts yet.</p>';
	}
	return td_cards_html( $q );
} );

/* ---------- One-time setup on activation ---------- */
register_activation_hook( __FILE__, 'td_setup' );

function td_attachment_id( $slug ) {
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $post ) {
		$thumb = get_post_thumbnail_id( $post->ID );
		if ( $thumb ) {
			return (int) $thumb;
		}
	}
	global $wpdb;
	$like = '%/' . $wpdb->esc_like( 'td-' . $slug ) . '%';
	$id   = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id ASC LIMIT 1", $like ) );
	return $id ? (int) $id : 0;
}

function td_sideload( $slug, $title ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$existing = td_attachment_id( $slug );
	if ( $existing ) {
		return $existing;
	}
	$src = plugin_dir_path( __FILE__ ) . 'images/' . $slug . '.jpg';
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	$tmp = wp_tempnam( $slug . '.jpg' );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => 'td-' . $slug . '.jpg', 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return (int) $id;
}

function td_make_page( $title, $slug, $html ) {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		return $existing->ID;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->",
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, 'site-sidebar-layout', 'no-sidebar' );
		update_post_meta( $id, 'site-content-layout', 'normal-width-container' );
		update_post_meta( $id, 'site-post-title', 'disabled' );
		return $id;
	}
	return 0;
}

function td_posts_data() {
	return array(
		array( 'ella', 'Ella: Hills, Trains and Tea Views', 'Mountains',
			'A relaxed hill-country village with some of the best views in Sri Lanka.',
			array(
				'Ella is a small town in Sri Lanka\'s Badulla district, surrounded by green hills, waterfalls and tea estates. It is popular with travellers who enjoy walking, scenic train rides and cool mountain air.',
				'<strong>Nine Arches Bridge.</strong> This stone viaduct near Demodara is one of the best-known sights in the country. Visitors often walk along the track to watch a train cross the arches.',
				'<strong>Little Adam\'s Peak.</strong> An easy walk of about an hour leads to a viewpoint over the valley. It is best early in the morning, when the air is clear.',
				'<strong>Getting there.</strong> The train from Kandy to Ella passes through tea country and is often described as one of the most beautiful rail journeys in the world. Book seats in advance, as popular classes fill up quickly.',
			) ),
		array( 'sigiriya', 'Sigiriya: The Rock Fortress', 'Culture',
			'A rock fortress rising from the jungle, and one of Sri Lanka\'s UNESCO World Heritage Sites.',
			array(
				'Sigiriya is an ancient rock fortress in the central Matale district. The huge rock rises about 200 metres above the surrounding forest, and a palace complex was built on its summit in the 5th century.',
				'<strong>What to see.</strong> Visitors climb through the Water Gardens, the Mirror Wall and the Lion\'s Paws before reaching the top. Along the way there are old frescoes of women painted on the rock face.',
				'<strong>Best time to visit.</strong> Start early to avoid the midday heat and the crowds. Wear comfortable shoes, bring water and allow about three hours for the climb and the grounds.',
				'<strong>Nearby.</strong> Pidurangala Rock, just across the road, offers a free-standing viewpoint with a great view of Sigiriya itself.',
			) ),
		array( 'galle', 'Galle Fort: A Walk Along the Walls', 'Beaches',
			'A historic coastal fort with sea views, cafes and old streets.',
			array(
				'Galle Fort stands on the south-west coast and is a UNESCO World Heritage Site. It was first built by the Portuguese in the 16th century and later expanded by the Dutch.',
				'<strong>Walk the ramparts.</strong> A stroll along the walls takes about an hour and ends near the white lighthouse, one of the most photographed spots in the fort.',
				'<strong>Inside the fort.</strong> Small streets are lined with cafes, boutiques, galleries and old churches. It is easy to explore on foot.',
				'<strong>Sunset.</strong> Many visitors gather on the walls in the evening to watch the sun go down over the Indian Ocean.',
			) ),
		array( 'mirissa', 'Mirissa: Beach Days and Sunsets', 'Beaches',
			'A laid-back beach town on the south coast, known for sunsets and whale watching.',
			array(
				'Mirissa is a small coastal town on the south coast of Sri Lanka, known for its golden beach, palm trees and relaxed atmosphere.',
				'<strong>Whale watching.</strong> Boat trips leave early in the morning in the main season, which runs roughly from November to April. Blue whales and dolphins can sometimes be seen. Choose an operator that follows responsible viewing guidelines.',
				'<strong>Coconut Tree Hill.</strong> A small headland covered in palm trees, popular for sunrise and sunset photos.',
				'<strong>Tips.</strong> The sea can be rough during the monsoon months, so check conditions before swimming. Fresh seafood is easy to find along the beach.',
			) ),
		array( 'nuwara-eliya', 'Nuwara Eliya: Sri Lanka\'s Little England', 'Mountains',
			'A cool hill town surrounded by tea plantations and misty mountains.',
			array(
				'Nuwara Eliya sits at around 1,900 metres above sea level and has a much cooler climate than the coast. Its colonial-era buildings and gardens give it the nickname "Little England".',
				'<strong>Tea plantations.</strong> Several estates near the town offer factory visits where visitors can see how tea is made and taste a fresh cup.',
				'<strong>Gregory Lake.</strong> A pleasant spot for a walk, a boat ride or a picnic.',
				'<strong>Horton Plains.</strong> A national park about an hour away, famous for its walking trail to World\'s End. Start early, because the clouds often cover the view by mid-morning.',
			) ),
		array( 'kandy', 'Kandy: Temples, Lake and Culture', 'Culture',
			'The cultural heart of Sri Lanka, home to the Temple of the Tooth.',
			array(
				'Kandy is a city in the central hills and the last capital of the ancient Sri Lankan kings. It is a UNESCO World Heritage Site and an important centre for Buddhist culture.',
				'<strong>Temple of the Tooth.</strong> The Sri Dalada Maligawa holds a relic of the Buddha\'s tooth. Visitors should dress modestly, with shoulders and knees covered.',
				'<strong>Kandy Lake.</strong> A peaceful lake in the middle of the city, with a path that is pleasant for an evening walk.',
				'<strong>Peradeniya Botanical Gardens.</strong> A large garden a short ride from the city, known for its orchids and giant trees.',
			) ),
	);
}

/* Put the Travel Diary menu in Astra's Primary location if nothing is assigned yet. */
function td_assign_menu() {
	$menu = wp_get_nav_menu_object( 'Travel Diary Menu' );
	if ( ! $menu ) {
		return;
	}
	$locs = get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locs['primary'] ) ) {
		$locs['primary'] = $menu->term_id;
		set_theme_mod( 'nav_menu_locations', $locs );
	}
}
add_action( 'after_switch_theme', 'td_assign_menu' );
add_action( 'admin_init', 'td_assign_menu' );

function td_setup() {
	// Categories.
	$cat_ids = array();
	foreach ( array( 'Beaches', 'Mountains', 'Culture' ) as $c ) {
		$t = term_exists( $c, 'category' );
		if ( ! $t ) {
			$t = wp_insert_term( $c, 'category' );
		}
		if ( ! is_wp_error( $t ) ) {
			$cat_ids[ $c ] = (int) ( is_array( $t ) ? $t['term_id'] : $t );
		}
	}

	// Posts with featured images.
	foreach ( td_posts_data() as $p ) {
		list( $slug, $title, $cat, $excerpt, $paras ) = $p;
		if ( get_page_by_path( $slug, OBJECT, 'post' ) ) {
			continue;
		}
		$content = '';
		foreach ( $paras as $para ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . $para . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		$id = wp_insert_post( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
			'post_category'=> isset( $cat_ids[ $cat ] ) ? array( $cat_ids[ $cat ] ) : array(),
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$img = td_sideload( $slug, $title );
			if ( $img ) {
				set_post_thumbnail( $id, $img );
			}
		}
	}

	// Pages.
	$ids = array(
		'home'    => td_make_page( 'Home', 'home', td_page_html( 'home' ) ),
		'about'   => td_make_page( 'About', 'about', td_page_html( 'about' ) ),
		'contact' => td_make_page( 'Contact', 'contact', td_page_html( 'contact' ) ),
	);

	update_option( 'blogname', 'Travel Diary' );
	update_option( 'blogdescription', 'Short guides to places in Sri Lanka' );
	if ( $ids['home'] ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}

	// Menu.
	$name = 'Travel Diary Menu';
	if ( ! wp_get_nav_menu_object( $name ) ) {
		$menu_id = wp_create_nav_menu( $name );
		if ( ! is_wp_error( $menu_id ) ) {
			$pos = 1;
			$add_page = function ( $label, $pid ) use ( $menu_id, &$pos ) {
				if ( $pid ) {
					wp_update_nav_menu_item( $menu_id, 0, array(
						'menu-item-title' => $label, 'menu-item-object' => 'page', 'menu-item-object-id' => $pid,
						'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-position' => $pos++,
					) );
				}
			};
			$add_page( 'Home', $ids['home'] );
			foreach ( $cat_ids as $label => $tid ) {
				wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title' => $label, 'menu-item-object' => 'category', 'menu-item-object-id' => $tid,
					'menu-item-type' => 'taxonomy', 'menu-item-status' => 'publish', 'menu-item-position' => $pos++,
				) );
			}
			$add_page( 'About', $ids['about'] );
			$add_page( 'Contact', $ids['contact'] );
		}
	}
	td_assign_menu();

	// CSS (once).
	$css_file = plugin_dir_path( __FILE__ ) . 'css/travel-diary.css';
	if ( file_exists( $css_file ) ) {
		$current = wp_get_custom_css();
		if ( false === strpos( $current, '/* TRAVEL DIARY CSS */' ) ) {
			wp_update_custom_css_post( $current . "\n" . file_get_contents( $css_file ) );
		}
	}
	flush_rewrite_rules();
	td_apply_v2();
}

/* ---------- Extra styling and page content (applied once per version, also on older installs) ---------- */
add_action( 'admin_init', 'td_apply_v2' );

function td_img_url( $slug ) {
	$id = td_attachment_id( $slug );
	return $id ? (string) wp_get_attachment_url( $id ) : '';
}

function td_cat_url( $slug ) {
	$c = get_category_by_slug( $slug );
	return $c ? get_category_link( $c->term_id ) : home_url( '/' );
}

function td_page_html( $key ) {
	$contact_url = home_url( '/contact/' );
	if ( 'home' === $key ) {
		$tile = function ( $slug, $title, $text ) {
			return '<a class="td-tile td-tile--' . esc_attr( $slug ) . '" href="' . esc_url( td_cat_url( $slug ) ) . '"><div><h3>' . esc_html( $title ) . '</h3><span>' . esc_html( $text ) . '</span></div></a>';
		};
		return '<div class="td-hero"><h1>Travel Diary</h1><p>Short guides to the places worth visiting in Sri Lanka.</p><div class="td-btns"><a class="td-btn" href="#td-latest">Read the latest</a><a class="td-btn td-btn-ghost" href="#td-explore">Browse destinations</a></div></div>'
			. '<div class="td-stats"><div class="td-stat"><strong>6</strong><span>Destination guides</span></div><div class="td-stat"><strong>3</strong><span>Categories</span></div><div class="td-stat"><strong>Free</strong><span>To read</span></div></div>'
			. '<div class="td-section" id="td-explore"><h2>Explore by category</h2><div class="td-tiles">'
			. $tile( 'beaches', 'Beaches', 'Sunsets, surf and the south coast' )
			. $tile( 'mountains', 'Mountains', 'Tea country and cool hill towns' )
			. $tile( 'culture', 'Culture', 'Temples, forts and ancient cities' )
			. '</div></div>'
			. '<div class="td-section" id="td-latest"><h2>Latest stories</h2>[td_latest count="6"]</div>'
			. '<div class="td-section td-band"><h2>Why read these guides</h2><div class="td-features">'
			. '<div class="td-feature"><div class="td-ico">&#128337;</div><h3>Short and clear</h3><p>Each guide can be read in a few minutes.</p></div>'
			. '<div class="td-feature"><div class="td-ico">&#128506;</div><h3>Practical tips</h3><p>What to see, how to get there and when to go.</p></div>'
			. '<div class="td-feature"><div class="td-ico">&#127796;</div><h3>Across the island</h3><p>Beaches, mountains and cultural sites in one place.</p></div>'
			. '</div></div>'
			. '<div class="td-section"><div class="td-cta"><h2>Have a question or a tip?</h2><p>Write to us and we will get back to you.</p><a class="td-btn" href="' . esc_url( $contact_url ) . '">Contact</a></div></div>';
	}
	if ( 'about' === $key ) {
		return '<div class="td-banner td-banner--about"><h1>About Travel Diary</h1><p>Short, practical guides to places in Sri Lanka.</p></div>'
			. '<div class="td-section"><div class="td-split"><div><h2>About this blog</h2><p>Travel Diary is a small blog with short guides to places in Sri Lanka: the mountains, the beaches and the cultural sites.</p><p>Each guide covers what to see, how to get there and a few practical tips, so planning a trip is a little easier.</p><a class="td-btn" href="' . esc_url( td_cat_url( 'culture' ) ) . '">Start reading</a></div><img src="' . esc_url( td_img_url( 'ella' ) ) . '" alt="Hills near Ella"></div></div>'
			. '<div class="td-section"><h2>How each guide works</h2><div class="td-steps">'
			. '<div class="td-step"><h3>What to see</h3><p>The main sights and the best viewpoints, kept short.</p></div>'
			. '<div class="td-step"><h3>Getting there</h3><p>The easiest way to reach the place and how long it takes.</p></div>'
			. '<div class="td-step"><h3>Good to know</h3><p>Best time to visit and simple tips for the trip.</p></div>'
			. '</div></div>'
			. '<div class="td-section"><h2>Where we cover</h2><div class="td-features">'
			. '<div class="td-feature"><div class="td-ico">&#127958;</div><h3>Beaches</h3><p>Mirissa and Galle on the south coast.</p></div>'
			. '<div class="td-feature"><div class="td-ico">&#9968;</div><h3>Mountains</h3><p>Ella and Nuwara Eliya in the hill country.</p></div>'
			. '<div class="td-feature"><div class="td-ico">&#127984;</div><h3>Culture</h3><p>Sigiriya and Kandy, two World Heritage Sites.</p></div>'
			. '</div></div>'
			. '<div class="td-section"><div class="td-cta"><h2>Want to suggest a place?</h2><p>Tell us where we should write about next.</p><a class="td-btn" href="' . esc_url( $contact_url ) . '">Get in touch</a></div></div>';
	}
	return '<div class="td-banner td-banner--contact"><h1>Get in touch</h1><p>Questions about a destination, or a place you would like us to write about? Send a message and we will reply as soon as we can.</p></div>'
		. '<div class="td-section"><div class="td-contact-grid"><div>'
		. '<div class="td-info"><h3>Ask a question</h3><p>Planning a trip and need a pointer? Ask about any place we cover.</p></div>'
		. '<div class="td-info"><h3>Suggest a destination</h3><p>Know a place that deserves a guide? Tell us about it.</p></div>'
		. '<div class="td-info"><h3>Reply time</h3><p>We read every message and aim to reply within a few days.</p></div>'
		. '</div><div class="td-formbox">[td_contact]</div></div></div>';
}

function td_apply_v2() {
	// CSS: each version is added once (checked by its marker comment).
	$current = wp_get_custom_css();
	$add     = '';
	$base    = plugin_dir_path( __FILE__ ) . 'css/';
	foreach ( array( 'v2' => '/* TRAVEL DIARY CSS v2 */', 'v3' => '/* TRAVEL DIARY CSS v3 */', 'v4' => '/* TRAVEL DIARY CSS v4 */', 'v5' => '/* TRAVEL DIARY CSS v5 */', 'v6' => '/* TRAVEL DIARY CSS v6 */', 'v7' => '/* TRAVEL DIARY CSS v7 */', 'v8' => '/* TRAVEL DIARY CSS v8 */' ) as $ver => $marker ) {
		$file = $base . 'travel-diary-' . $ver . '.css';
		if ( false === strpos( $current, $marker ) && file_exists( $file ) ) {
			$add .= "\n" . file_get_contents( $file );
		}
	}
	// Background photos (hero, category tiles, page banners), added once when the images exist.
	if ( false === strpos( $current . $add, '/* TRAVEL DIARY IMAGES */' ) ) {
		$u = array();
		foreach ( array( 'ella', 'mirissa', 'nuwara-eliya', 'kandy', 'sigiriya', 'galle' ) as $sl ) {
			$u[ $sl ] = td_img_url( $sl );
		}
		if ( ! in_array( '', $u, true ) ) {
			$bg = function ( $sel, $sl, $overlay = '' ) use ( $u ) {
				return $sel . "{background-image:" . $overlay . "url('" . esc_url_raw( $u[ $sl ] ) . "');}\n";
			};
			$ov  = 'linear-gradient(rgba(18,74,80,.55),rgba(18,74,80,.55)),';
			$add .= "\n/* TRAVEL DIARY IMAGES */\n"
				. $bg( '.td-hero', 'ella', $ov )
				. $bg( '.td-tile--beaches', 'mirissa' )
				. $bg( '.td-tile--mountains', 'nuwara-eliya' )
				. $bg( '.td-tile--culture', 'kandy' )
				. $bg( '.td-banner--about', 'sigiriya' )
				. $bg( '.td-banner--contact', 'galle' );
		}
	}
	// Photo behind the call-to-action band, added once.
	if ( false === strpos( $current . $add, '/* TRAVEL DIARY IMAGES 2 */' ) ) {
		$cta = td_img_url( 'nuwara-eliya' );
		if ( $cta ) {
			$add .= "\n/* TRAVEL DIARY IMAGES 2 */\n.td-cta{background-image:url('" . esc_url_raw( $cta ) . "');}\n";
		}
	}
	if ( '' !== $add ) {
		wp_update_custom_css_post( $current . $add );
	}

	// Astra options, once.
	if ( ! get_option( 'td_v2_done' ) ) {
		$opts = get_option( 'astra-settings', array() );
		if ( is_array( $opts ) ) {
			$opts['single-post-sidebar-layout']  = 'no-sidebar';
			$opts['archive-post-sidebar-layout'] = 'no-sidebar';
			$opts['footer-copyright-editor']     = 'Copyright [copyright] [current_year] [site_title]. All rights reserved.';
			update_option( 'astra-settings', $opts );
		}
		update_option( 'td_v2_done', 1 );
	}

	// Version 5 options, once: comments off, scroll-to-top button.
	if ( ! get_option( 'td_v5_done' ) ) {
		update_option( 'default_comment_status', 'closed' );
		$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => -1, 'fields' => 'ids' ) );
		foreach ( $posts as $pid ) {
			wp_update_post( array( 'ID' => $pid, 'comment_status' => 'closed' ) );
		}
		$opts = get_option( 'astra-settings', array() );
		if ( is_array( $opts ) ) {
			$opts['scroll-to-top-enable'] = 1;
			update_option( 'astra-settings', $opts );
		}
		update_option( 'td_v5_done', 1 );
	}

	// Page content (Home, About, Contact), once per version. Only runs when the posts exist.
	if ( '4' !== get_option( 'td_pages_version' ) && get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1, 'fields' => 'ids' ) ) ) {
		foreach ( array( 'home', 'about', 'contact' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				wp_update_post( array( 'ID' => $page->ID, 'post_content' => "<!-- wp:html -->\n" . td_page_html( $slug ) . "\n<!-- /wp:html -->" ) );
				update_post_meta( $page->ID, 'site-post-title', 'disabled' );
			}
		}
		update_option( 'td_pages_version', '4' );
	}
}

/* Single post: featured image, reading time, related stories and a back button. */
add_filter( 'the_content', function ( $content ) {
	static $busy = false;
	if ( $busy || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$busy = true;
	$mins = max( 1, (int) ceil( str_word_count( wp_strip_all_tags( $content ) ) / 200 ) );
	$cats = get_the_category();
	$meta = ( $cats ? esc_html( $cats[0]->name ) . ' &middot; ' : '' ) . $mins . ' min read';
	$top  = '';
	if ( has_post_thumbnail() ) {
		$top = '<figure class="td-feature-img">' . get_the_post_thumbnail( null, 'large' ) . '</figure>';
	}
	$related = '';
	$current = get_the_ID();
	$args    = array( 'post_type' => 'post', 'posts_per_page' => 3, 'post__not_in' => array( $current ), 'ignore_sticky_posts' => true );
	if ( $cats ) {
		$args['cat'] = $cats[0]->term_id;
	}
	$q = new WP_Query( $args );
	if ( $q->have_posts() ) {
		$related = '<div class="td-related"><h2>More stories</h2>' . td_cards_html( $q ) . '</div>';
	}
	wp_reset_postdata();
	$busy = false;
	return $top . '<p class="td-meta">' . $meta . '</p>' . $content . $related . '<p class="td-back"><a class="td-btn" href="' . esc_url( home_url( '/' ) ) . '">&larr; Back to all stories</a></p>';
} );
