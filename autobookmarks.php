<?php
/*
 * Plugin Name: WordPress Dashboard Autobookmarks
 * Plugin URI: https://github.com/soulseekah/autobookmarks
 * Description: This plugin automatically manages a list of most accessed dashboard pages for quick jumping.
 * Author: Gennady Kovshenin
 * Version: 0.1-alpha
 * Author URI: https://codeseekah.com
 */

if ( !defined( 'ABSPATH' ) ) exit; /** Y U NO LOAD CORE? */

/** Start recording */
add_action( 'init', function() {
	if ( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
		return;

	define( 'DOING_AUTOBOOKMARKS_BUFFERING', true );

	/** Let's try to catch the title of the page */
	ob_start();
} );

/** End recording */
add_action( 'admin_head', function() {

	if ( !defined( 'DOING_AUTOBOOKMARKS_BUFFERING' ) )
		return;

	$header = ob_get_clean();
	echo $header; // Show it now

	$user_id = wp_get_current_user()->ID;

	$blacklist = array( '_wpnonce', 'wp_http_referer' );
	$screen = remove_query_arg( $blacklist );


	/** Regular expressions for HTML parsing? Don't kill me :) */
	preg_match( '#<title>(.*)</title>#', $header, $matches );
	$title = count( $matches ) > 1 ? $matches[1] : $screen;

	$autobookmarks = get_user_meta( $user_id, 'autobookmarks', true ) ? : array();
	$autobookmarks[$screen] = empty( $autobookmarks[$screen] ) ? array( 'title' => $title, 'count' => 0 ) : $autobookmarks[$screen];

	$autobookmarks[$screen]['title'] = $title;
	$autobookmarks[$screen]['count']++;

	update_user_meta( $user_id, 'autobookmarks', $autobookmarks );
} );

/** Replay */
add_action( 'admin_bar_menu', function( $admin_bar ) {

	$admin_bar->add_node( array(
		'id' => 'autobookmarks',
		'title' => 'Autobookmarks',
		'parent' => false,
	) );

	$user_id = wp_get_current_user()->ID;
	$autobookmarks = get_user_meta( $user_id, 'autobookmarks', true ) ? : array();

	uasort( $autobookmarks, function( $a, $b ) {
		return $b['count'] - $a['count'];
	} );

	foreach ( $autobookmarks as $href => $bookmark ) {
		$admin_bar->add_node( array(
			'id' => $href,
			'href' => $href,
			'title' => $bookmark['title'] . ' (' . $bookmark['count'] . ')',
			'parent' => 'autobookmarks',
		) );
	}
} );
