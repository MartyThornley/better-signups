<?php
/**
 * @package Better_Signups
 * @version .4
 */
/*
Plugin Name: Better Signups
Plugin URI: http://martythornley.com
Description: An attempt tp improve the signin and registration process.
Author: Marty Thornley
Version: .4
Author URI: http://martythornley.com
*/
	
	/** CONFIG **/
	
	define ( 'REDIRECTS_URLS' , true );

	define ( 'REDIRECT_SIGNUP_URLS' , true );

	define ( 'REDIRECT_LOGIN_URLS' , true );

	//define ( 'BSIGN_DEBUG' , true );
	
	/** End CONFIG **/
		
	if ( !defined ( 'BSIGN_DIR' ) ) { define ( 'BSIGN_DIR', dirname(__FILE__) ); };
	
	$pluginURL = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ) , "" , plugin_basename(__FILE__) );
	
	if ( !defined ( 'BSIGN_URL' ) ) { define ( 'BSIGN_URL' , $pluginURL ); };
	
	/** temporary debug functions */
	include( trailingslashit( BSIGN_DIR ) . 'includes/debug.php' );
	
	/** WordPress Administration Screen API */
	require_once(ABSPATH . 'wp-admin/includes/screen.php');
	
	add_action( 'init' , 'bsign_init' );
	
	/*
	 * Probably a better way to include files depending on where they end up
	 */
	if ( is_admin() ) {
		include( trailingslashit( BSIGN_DIR ) . 'includes/signin-functions.php' );
	}

	/*
	 * Filters the logout url
	 * Probably temporary if we end up redefining in core
	 */
	function bsign_logout_url( $logout_url ) {
		$var_string = bsign_get_query_string( $logout_url );
		$logout_url = site_url( 'signout' . $var_string );
		$logout_url = wp_nonce_url( $logout_url, 'log-out' );	
		return $logout_url;
	}
	
	/*
	 * Find and return the query string
	 * returns empty string if none is found
	 */
	function bsign_get_query_string( $url ){
		$var_string = '';
		if ( strpos( $url , '?' ) )
			$url_array = explode( '?' , $url);
		if ( isset( $url_array[1] ) )
			$var_string = '?'.$url_array[1];
		return $var_string;	
	}
	
	/*
	 * Init actions
	 * Parses url to figure out where we should redirect
	 */
	function bsign_init() {
		
		add_filter( 'logout_url' , 'bsign_logout_url' );
		
	    if ( !empty( $_SERVER['REQUEST_URI'] ) ) {
			if ( strpos( $_SERVER['REQUEST_URI'] , '?' ) ) {
				$url_array = explode( '?' , $_SERVER['REQUEST_URI']);
				$url = $url_array[0];
				$vars = $url_array[1];
			} else { 
				$url = $_SERVER['REQUEST_URI'];
			}
			
			$url = trim( $url , '/' );
	        $urlvars = explode( '/' , $url );
	    }

		$last = array_pop( $urlvars );
		
		if ( isset( $vars ) && strpos( $vars , 'logout' ) != false )
			$last = 'signout';		
		
		$action = isset( $url_array[1] ) ? '?'.$url_array[1] : '';	
		
		
		
		if ( defined ( 'REDIRECTS_URLS' ) ) {	

			if ( defined ( 'REDIRECT_SIGNUP_URLS' ) ) {	
				
				// skip core wp-signup.php
				add_action( 'hijack_signup' , 'bsign_exit' );
			
				if ( strpos( $_SERVER['REQUEST_URI'] , 'wp-signup.php' ) != false )
					wp_redirect( home_url( 'signup' . $action ) );
			
			}
			
			if ( defined ( 'REDIRECT_LOGIN_URLS' ) ) {	

				// skip core wp-login.php
				// seems to work with or without this??
				// add_action( 'hijack_login' , 'bsign_exit' );

				if ( $last == 'signout' )
					wp_redirect( home_url( 'signout' . bsign_get_query_string( $_SERVER['REQUEST_URI'] ) ) );
					
				elseif ( strpos( $_SERVER['REQUEST_URI'] , 'wp-login.php' ) != false || $last == 'signin' )
					wp_redirect( home_url( 'signin' . $action ) );
					
			}
			
			switch ( $last ) {
				
				case 'signup' :
					if ( defined ( 'REDIRECT_SIGNUP_URLS' ) )
						add_action( 'template_redirect', 'bsign_signup_redirect' );
				break;
	
				case 'signin' :
					
					if ( is_user_logged_in() ) {
						wp_redirect( admin_url() );
						exit;
					}
					if ( defined ( 'REDIRECT_LOGIN_URLS' ) )
						add_action( 'template_redirect', 'bsign_signin_redirect' );
				break;
	
				case 'signout' :
					if ( !is_user_logged_in() ) {
						wp_redirect( home_url( 'signin?loggedout=true' ) );
						exit;
					}
					if ( defined ( 'REDIRECT_LOGIN_URLS' ) )
						add_action( 'template_redirect', 'bsign_signout_redirect' );
				break;			

			}
		}
	}
	/*
	 * Simple funciton to exit
	 * replaces annomymous functions which did not seem to work in some cases
	 *
	 */
	function bsign_exit (){
		exit;
	}
	/*
	 * Redirect to our signup page
	 */	
	function bsign_signup_redirect() {
		
		global $action;
		
		include( trailingslashit( BSIGN_DIR ) . 'includes/signup-functions.php' );
		include( trailingslashit( BSIGN_DIR ) . 'includes/signup.php' );
		exit;
	}

	/*
	 * Redirect to our signin page
	 */
	function bsign_signin_redirect() {
		include( trailingslashit( BSIGN_DIR ) . 'includes/signin-functions.php' );
		include( trailingslashit( BSIGN_DIR ) . 'includes/signin.php' );
		exit;
	}

	/*
	 * Redirect to our signout page
	 */
	function bsign_signout_redirect() {
		include( trailingslashit( BSIGN_DIR ) . 'includes/signout.php' );
		exit;
	}
