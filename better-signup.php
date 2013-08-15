<?php
/**
 * @package Better_Signups
 * @version .1
 */
/*
Plugin Name: Better Signups
Plugin URI: http://martythornley.com
Description: An attempt tp improve the signin and registration process.
Author: Marty Thornley
Version: .1
Author URI: http://martythornley.com
*/
	
	/* CONFIG */
	
	//define ( 'USE_STANDARD_WP_URLS' , true );
	//define ( 'BSIGN_DEBUG' , true );
	
	if ( !defined ( 'BSIGN_DIR' ) ) { define ( 'BSIGN_DIR', dirname(__FILE__) ); };
	
	$pluginURL = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ) , "" , plugin_basename(__FILE__) );
	
	if ( !defined ( 'BSIGN_URL' ) ) { define ( 'BSIGN_URL' , $pluginURL ); };

	add_action( 'init' , 'bsign_init' );
	
	/*
	 * Init actions
	 * Parses url to figure out where we should redirect
	 */
	function bsign_init() {
	
	    if ( !empty( $_SERVER['REQUEST_URI'] ) ) {
			$url = trim( $_SERVER['REQUEST_URI'] , '/' );
	        $urlvars = explode( '/' , $url );
	    }
	
		$last = array_pop( $urlvars );
		
		if ( !defined ( 'USE_STANDARD_WP_URLS' ) ) {	

			if ( strpos( $_SERVER['REQUEST_URI'] , 'wp-signup.php' ) != false )
				wp_redirect( home_url( 'signup' ) );
	
			if ( strpos( $_SERVER['REQUEST_URI'] , 'action=logout' ) != false )
				wp_redirect( home_url( 'signout' ) );
	
			if ( strpos( $_SERVER['REQUEST_URI'] , 'wp-login.php' ) != false )
				wp_redirect( home_url( 'signin' ) );
		
			switch ( $last ) {
				
				case 'signup' :
					add_action( 'template_redirect', 'bsign_signup_redirect' );
				break;
	
				case 'signin' :
					add_action( 'template_redirect', 'bsign_signin_redirect' );
				break;
	
				case 'signout' :
					add_action( 'template_redirect', 'bsign_signout_redirect' );
				break;			
				
			}
		}
	}

	/*
	 * Redirect to our signup page
	 */	
	function bsign_signup_redirect() {
		include( trailingslashit( BSIGN_DIR ) . 'templates/signup.php' );
		exit;
	}

	/*
	 * Redirect to our signin page
	 */
	function bsign_signin_redirect() {
		include( trailingslashit( BSIGN_DIR ) . 'templates/signin.php' );
		exit;
	}

	/*
	 * Redirect to our signout page
	 */
	function bsign_signout_redirect() {
		include( trailingslashit( BSIGN_DIR ) . 'templates/signout.php' );
		exit;
	}
