<?php
/**
 * WordPress Signout Page
 *
 * Handles signing out and redirection
 *
 * @package WordPress
 */	

	set_current_screen( 'signout' );
	
	check_admin_referer( 'log-out' );

	do_action( 'wp_signout_page' );

	wp_logout();
	
	$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'signin' . bsign_get_query_string( $_SERVER['REQUEST_URI'] );
	wp_safe_redirect( $redirect_to );
	exit();
