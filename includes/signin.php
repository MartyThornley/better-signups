<?php
/**
 * WordPress User Page
 *
 * Handles authentication, registering, resetting passwords, forgot password,
 * and other user handling.
 *
 * @package WordPress
 */
	set_current_screen( 'signin' );
	
	// Redirect to https login if forced to use SSL
	if ( force_ssl_admin() && ! is_ssl() ) {
		if ( 0 === strpos($_SERVER['REQUEST_URI'], 'http') ) {
			wp_redirect( set_url_scheme( $_SERVER['REQUEST_URI'], 'https' ) );
			exit();
		} else {
			wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			exit();
		}
	}

	nocache_headers();
	
	header('Content-Type: '.get_bloginfo( 'html_type' ).'; charset='.get_bloginfo( 'charset' ) );
	
	if ( defined( 'RELOCATE' ) && RELOCATE ) { // Move flag is set
		if ( isset( $_SERVER['PATH_INFO'] ) && ( $_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF'] ) )
			$_SERVER['PHP_SELF'] = str_replace( $_SERVER['PATH_INFO'], '', $_SERVER['PHP_SELF'] );
	
		$url = dirname( set_url_scheme( 'http://' .  $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] ) );
		if ( $url != get_option( 'siteurl' ) )
			update_option( 'siteurl', $url );
	}
	
	//Set a cookie now to see if they are supported by the browser.
	setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN );
	if ( SITECOOKIEPATH != COOKIEPATH )
		setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN );
	
	// allow plugins to override the default actions, and to add extra actions if they want
	do_action( 'login_init' );

	$action = process_login_form();
	
	do_action( 'login_form_' . $action );
	
	do_login_form( $action );
