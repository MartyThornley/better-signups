<?php

	do_action( 'wp_signout_page' );

	check_admin_referer( 'log-out' );
	wp_logout();
	
	$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'signin';
	wp_safe_redirect( $redirect_to );
	exit();
