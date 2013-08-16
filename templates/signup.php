<?php
	
	add_action( 'wp_head', 'wp_no_robots' );
	
	require( './wp-blog-header.php' );
	
	if ( is_array( get_site_option( 'illegal_names' )) && isset( $_GET[ 'new' ] ) && in_array( $_GET[ 'new' ], get_site_option( 'illegal_names' ) ) == true ) {
		wp_redirect( network_home_url() );
		die();
	}
	
	add_action( 'wp_head', 'do_signup_header' );
	
	if ( !is_multisite() ) {
		$action = 'register';
		bsign_signin_redirect();
		die();
	}
	
	if ( !is_main_site() ) {
		wp_redirect( network_site_url( 'signup' ) );
		die();
	}
	
	// Fix for page title
	$wp_query->is_404 = false;
	
	add_action( 'wp_head', 'wpmu_signup_stylesheet' );
	
	get_header();
	
	do_action( 'before_signup_form' );
	
	?>
	<div id="content" class="widecolumn">
		<div class="mu_register">
		
	<?php
	// Main
	
	$active_signup = get_site_option( 'registration' );
	
	if ( !$active_signup )
		$active_signup = 'all';
	
	$active_signup = apply_filters( 'wpmu_active_signup', $active_signup ); // return "all", "none", "blog" or "user"
	
	// Make the signup type translatable.
	$i18n_signup['all'] = _x( 'all', 'Multisite active signup type' );
	$i18n_signup['none'] = _x( 'none', 'Multisite active signup type' );
	$i18n_signup['blog'] = _x( 'blog', 'Multisite active signup type' );
	$i18n_signup['user'] = _x( 'user', 'Multisite active signup type' );
	
	if ( is_super_admin() )
		echo '<div class="mu_alert">' . sprintf( __( 'Greetings Site Administrator! You are currently allowing &#8220;%s&#8221; registrations. To change or disable registration go to your <a href="%s">Options page</a>.' ), $i18n_signup[$active_signup], esc_url( network_admin_url( 'settings.php' ) ) ) . '</div>';
	
	$newblogname = isset( $_GET['new'] ) ? strtolower( preg_replace( '/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'] ) ) : null;
	
	$current_user = wp_get_current_user();
	
	// Registration disabled
	if ( $active_signup == 'none' ) {
	
		_e( 'Registration has been disabled.' );
	
	// Can signup for blog, user not signed in
	} elseif ( $active_signup == 'blog' && !is_user_logged_in() ) {
	
		$login_url = site_url( 'wp-login.php?redirect_to=' . urlencode( network_site_url( 'wp-signup.php' ) ) );
		echo sprintf( __( 'You must first <a href="%s">log in</a>, and then you can create a new site.' ), $login_url );
	
	} else {
	
		$stage = isset( $_POST['stage'] ) ?  $_POST['stage'] : 'default';
		switch ( $stage ) {
		
			case 'validate-user-signup' :
				if ( $active_signup == 'all' || $_POST[ 'signup_for' ] == 'blog' && $active_signup == 'blog' || $_POST[ 'signup_for' ] == 'user' && $active_signup == 'user' )
					validate_user_signup();
				else
					_e( 'User registration has been disabled.' );
			break;
		
			case 'validate-blog-signup':
				if ( $active_signup == 'all' || $active_signup == 'blog' )
					validate_blog_signup();
				else
					_e( 'Site registration has been disabled.' );
			break;
		
			case 'gimmeanotherblog':
				validate_another_blog_signup();
			break;
		
			case 'default':
		
			default :
			
				$user_email = isset( $_POST[ 'user_email' ] ) ? $_POST[ 'user_email' ] : '';
				
				do_action( 'preprocess_signup_form' ); // populate the form from invites, elsewhere?
				
				if ( is_user_logged_in() && ( $active_signup == 'all' || $active_signup == 'blog' ) )
				
					signup_another_blog( $newblogname );
				
				elseif ( is_user_logged_in() == false && ( $active_signup == 'all' || $active_signup == 'user' ) )
				
					signup_user( $newblogname, $user_email );
				
				elseif ( is_user_logged_in() == false && ( $active_signup == 'blog' ) )
				
					_e( 'Sorry, new registrations are not allowed at this time.' );
				
				else
					_e( 'You are logged in already. No need to register again!' );
	
				if ( $newblogname ) {
				
					$newblog = get_blogaddress_by_name( $newblogname );
	
					if ( $active_signup == 'blog' || $active_signup == 'all' )
					
						printf( '<p><em>' . __( 'The site you were looking for, <strong>%s</strong>, does not exist, but you can create it now!' ) . '</em></p>', $newblog );
					
					else
					
						printf( '<p><em>' . __( 'The site you were looking for, <strong>%s</strong>, does not exist.' ) . '</em></p>', $newblog );
				}
				break;
		}
	}
	?>
	
		</div>
	</div>
	
	<?php do_action( 'after_signup_form' ); ?>
	
	<?php get_footer(); ?>
