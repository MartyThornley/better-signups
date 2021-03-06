<?php
/**
 * WordPress Signin Page
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

	/*** Determine $action ***/
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';
		
		if ( isset( $_GET['key'] ) )
			$action = 'resetpass';
		
		$signin_actions = array( 'signup' , 'signin' , 'signout' , 'postpass', 'register', 'login' , 'logout', 'loggedout' , 'lostpassword', 'retrievepassword', 'resetpass', 'rp' );
		// nice place for a filter here
		
		// validate action so as to default to the login screen
		if ( !in_array( $action , $signin_actions , true ) && false === has_filter( 'login_form_' . $action ) )
			$action = 'login';
		
		if ( $_GET['checkemail'] == 'registered' )
			$action = 'login';
	
	/*** allow plugins to jump in ***/
		do_action( 'login_init' );
	
	/*** process posted info based on $action ***/
		$action = process_login_form( $action );
		
	/*** allow plugins to jump in again ***/
		do_action( 'login_form_' . $action );
	
	/*** display form based on processed info and $action ***/
		do_login_form( $action );