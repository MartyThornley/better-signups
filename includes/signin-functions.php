<?php 

/*
 * This should process all posted info and figure out what form to load.
 * Most of it is still buried in do_login_form();
 */
function process_login_form() {

	$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';
	$errors = new WP_Error();
	
	if ( isset( $_GET['key'] ) )
		$action = 'resetpass';
	
	$signin_actions = array( 'postpass', 'logout', 'loggedout' , 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login' );
	
	// validate action so as to default to the login screen
	if ( !in_array( $action , $signin_actions , true ) && false === has_filter( 'login_form_' . $action ) )
		$action = 'login';
	
	//debug
	if ( defined ( 'BSIGN_DEBUG' ) )
		_debug_echo( '$action : ' . $action , __FILE__ , __LINE__ );	
	
	$http_post = ( 'POST' == $_SERVER['REQUEST_METHOD'] );
	

	if ( $_GET['checkemail'] == 'registered' )
		$action = 'login';

	switch( $action ) {
		
		case 'postpass' :
		
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher = new PasswordHash( 8, true );
		
			// 10 days
			setcookie( 'wp-postpass_' . COOKIEHASH, $hasher->HashPassword( wp_unslash( $_POST['post_password'] ) ), time() + 10 * DAY_IN_SECONDS, COOKIEPATH );
		
			wp_safe_redirect( wp_get_referer() );
			exit();
		
		break;
		
		case 'logout' :
		
			check_admin_referer( 'log-out' );
			wp_logout();
		
			$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'signin?loggedout=true';
			wp_safe_redirect( $redirect_to );
			exit();
		
		break;
		
		// this should be needed anymore at all
		// all signups should go through /signup
		case 'register' :
				
			if ( is_multisite() ) {
				wp_redirect( apply_filters( 'wp_signup_location', network_site_url( 'signup?action=register' ) ) );
				exit;
			}
		
			if ( !get_option('users_can_register') ) {
				wp_redirect( site_url( 'signup?registration=disabled' ) );
				exit();
			}
		
		break;
		
		case 'lostpassword' :
	}
	
	return $action;	
}

/**
 * Determines what we are trying to do and outputs the login/logout/password reset forms.
 *
 */
function do_login_form( $action = '' ) {

	//debug
	if ( defined ( 'BSIGN_DEBUG' ) )
		_debug_echo( '$action : ' . $action  , __FILE__ , __LINE__ );
	
	switch( $action ) {
		
		case 'retrievepassword' :
		
			if ( $http_post ) {
				$errors = retrieve_password();
				if ( !is_wp_error( $errors ) ) {
					$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'signup?checkemail=confirm';
					wp_safe_redirect( $redirect_to );
					exit();
				}
			}
		
			if ( isset( $_GET['error'] ) && 'invalidkey' == $_GET['error'] ) $errors->add( 'invalidkey', __( 'Sorry, that key does not appear to be valid.' ) );
			$redirect_to = apply_filters( 'lostpassword_redirect', !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '' );
			
			?>

				<?php do_action( 'lost_password' ); ?>
				
				<?php login_header(__( 'Lost Password' ), '<p class="message">' . __( 'Please enter your username or email address. You will receive a link to create a new password via email.' ) . '</p>', $errors ); ?>
			
				<?php $user_login = isset( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : ''; ?>
				
				<form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url( site_url( 'signin?action=lostpassword', 'login_post' ) ); ?>" method="post">
					<p>
						<label for="user_login" ><?php _e('Username or E-mail:') ?><br />
						<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr($user_login); ?>" size="20" /></label>
					</p>
					
					<?php do_action('lostpassword_form'); ?>
					
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Get New Password'); ?>" /></p>
				</form>

				<?php wp_signin_nav(); ?>

				<?php login_footer( 'user_login' ); ?>
			
			<?php
		break;
		
		case 'resetpass' :
		
		case 'rp' :
		
			$user = check_password_reset_key( $_GET['key'], $_GET['login'] );
		
			if ( is_wp_error($user) ) {
				wp_redirect( site_url( 'signin?action=lostpassword&error=invalidkey' ) );
				exit;
			}
		
			$errors = new WP_Error();
		
			if ( isset( $_POST['pass1'] ) && $_POST['pass1'] != $_POST['pass2'] )
				$errors->add( 'password_reset_mismatch', __( 'The passwords do not match.' ) );
		
			do_action( 'validate_password_reset', $errors, $user );
		
			if ( ( ! $errors->get_error_code() ) && isset( $_POST['pass1'] ) && !empty( $_POST['pass1'] ) ) {
				reset_password($user, $_POST['pass1']);
				login_header( __( 'Password Reset' ), '<p class="message reset-pass">' . __( 'Your password has been reset.' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in' ) . '</a></p>' );
				login_footer();
				exit;
			}
		
			wp_enqueue_script('utils');
			wp_enqueue_script('user-profile');
		
			?>
			
			<?php /*** Start Form ***/ ?>
			
				<?php login_header( __('Reset Password'), '<p class="message reset-pass">' . __('Enter your new password below.') . '</p>', $errors ); ?>
				
				<form name="resetpassform" id="resetpassform" action="<?php echo esc_url( site_url( 'signin?action=resetpass&key=' . urlencode( $_GET['key'] ) . '&login=' . urlencode( $_GET['login'] ), 'login_post' ) ); ?>" method="post" autocomplete="off">
					<input type="hidden" id="user_login" value="<?php echo esc_attr( $_GET['login'] ); ?>" autocomplete="off" />
				
					<p>
						<label for="pass1"><?php _e('New password') ?><br />
						<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" /></label>
					</p>
					<p>
						<label for="pass2"><?php _e('Confirm new password') ?><br />
						<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" /></label>
					</p>
				
					<div id="pass-strength-result" class="hide-if-no-js"><?php _e('Strength indicator'); ?></div>
					<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>
				
					<br class="clear" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Reset Password'); ?>" /></p>
				</form>
				
				<?php wp_signin_nav(); ?>
								
				<?php login_footer( 'user_pass' ); ?>
				
			<?php /*** End Form ***/ ?>
			
			<?php
		break;
		
		// this should go through /signup now
		case 'register' :

			$user_login = '';
			$user_email = '';
			if ( $http_post ) {
				$user_login = $_POST['user_login'];
				$user_email = $_POST['user_email'];
				$errors = register_new_user( $user_login, $user_email );
				if ( !is_wp_error( $errors ) ) {
					$redirect_to = !empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : 'signup?checkemail=registered';
					wp_safe_redirect( $redirect_to );
					exit();
				}
			}
					
			$redirect_to = apply_filters( 'registration_redirect', !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '' );
			?>
			
			<?php /*** Start Form ***/ ?>
			
				<?php login_header( __('Registration Form'), '<p class="message register">' . __('Register For This Site') . '</p>', $errors ); ?>
				
				<form name="registerform" id="registerform" action="<?php echo esc_url( site_url('signup?action='.$action , 'login_post') ); ?>" method="post">
					<p>
						<label for="user_login"><?php _e('Username') ?><br />
						<input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr(wp_unslash($user_login)); ?>" size="20" /></label>
					</p>
					<p>
						<label for="user_email"><?php _e('E-mail') ?><br />
						<input type="text" name="user_email" id="user_email" class="input" value="<?php echo esc_attr(wp_unslash($user_email)); ?>" size="25" /></label>
					</p>
					
					<?php do_action('register_form'); ?>
					
					<p id="reg_passmail"><?php _e('A password will be e-mailed to you.') ?></p>
					<br class="clear" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
					<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Register'); ?>" /></p>
				</form>
				
				<?php wp_signin_nav(); ?>

				<?php login_footer( 'user_login' ); ?>
				
			<?php /*** End Form ***/ ?>
			
			<?php
		break;
		
		case 'login' :
		
		default:
		
			$secure_cookie = '';
			$customize_login = isset( $_REQUEST['customize-login'] );
			if ( $customize_login )
				wp_enqueue_script( 'customize-base' );
		
			// If the user wants ssl but the session is not ssl, force a secure cookie.
			if ( !empty( $_POST['log'] ) && !force_ssl_admin() ) {
				$user_name = sanitize_user($_POST['log']);
				if ( $user = get_user_by( 'login', $user_name ) ) {
					if ( get_user_option( 'use_ssl', $user->ID ) ) {
						$secure_cookie = true;
						force_ssl_admin( true );
					}
				}
			}
		
			if ( isset( $_REQUEST['redirect_to'] ) ) {
				$redirect_to = $_REQUEST['redirect_to'];
				// Redirect to https if user wants ssl
				if ( $secure_cookie && false !== strpos( $redirect_to, 'wp-admin' ) )
					$redirect_to = preg_replace( '|^http://|', 'https://', $redirect_to );
			} else {
				$redirect_to = admin_url();
			}
		
			$reauth = empty( $_REQUEST['reauth'] ) ? false : true;
		
			// If the user was redirected to a secure login form from a non-secure admin page, and secure login is required but secure admin is not, then don't use a secure
			// cookie and redirect back to the referring non-secure admin page. This allows logins to always be POSTed over SSL while allowing the user to choose visiting
			// the admin via http or https.
			if ( !$secure_cookie && is_ssl() && force_ssl_login() && !force_ssl_admin() && ( 0 !== strpos($redirect_to, 'https') ) && ( 0 === strpos($redirect_to, 'http') ) )
				$secure_cookie = false;
		
			$user = wp_signon('', $secure_cookie);
			
			$redirect_to = apply_filters( 'login_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user );
			
			if ( !is_wp_error( $user ) && !$reauth ) {
			
				if ( $interim_login ) {
				
					$message = '<p class="message">' . __('You have logged in successfully.') . '</p>';
					$interim_login = 'success';
					?>
	
					<?php /*** Start Form ***/ ?>
	
						<?php login_header( '', $message ); ?>
						
						<?php do_action( 'login_footer' ); ?>
									
						<?php if ( $customize_login ) : ?>
							<script type="text/javascript">setTimeout( function(){ new wp.customize.Messenger({ url: '<?php echo wp_customize_url(); ?>', channel: 'login' }).send('login') }, 1000 );</script>
						<?php endif; ?>
		
					<?php /*** End Form ***/ ?>
					
					</body></html>
					
					<?php		
					exit;
				}
		
				if ( ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) ) {
				
					// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
					if ( is_multisite() && !get_active_blog_for_user( $user->ID ) && !is_super_admin( $user->ID ) )
						$redirect_to = user_admin_url();
						
					elseif ( is_multisite() && !$user->has_cap( 'read' ) )
						$redirect_to = get_dashboard_url( $user->ID );
						
					elseif ( !$user->has_cap( 'edit_posts' ) )
						$redirect_to = admin_url( 'profile.php' );
				}
				
				wp_safe_redirect( $redirect_to );
				
				exit();
				
			}
			
			$errors = $user;
			// Clear errors if loggedout is set.
			if ( !empty( $_GET['loggedout'] ) || $reauth )
				$errors = new WP_Error();
		
			// If cookies are disabled we can't log in even with a valid user+pass
			if ( isset( $_POST['testcookie'] ) && empty( $_COOKIE[TEST_COOKIE] ) )
				$errors->add( 'test_cookie', __("<strong>ERROR</strong>: Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to use WordPress.") );
		
			if ( $interim_login ) {
				if ( ! $errors->get_error_code() )
					$errors->add( 'expired', __('Session expired. Please log in again. You will not move away from this page.'), 'message' );
			} else {
				// Some parts of this script use the main login form to display a message
				if ( isset( $_GET['loggedout'] ) && true == $_GET['loggedout'] )
					$errors->add( 'loggedout', __('You are now logged out.'), 'message' );
					
				elseif	( isset($_GET['registration']) && 'disabled' == $_GET['registration'] )
					$errors->add( 'registerdisabled', __('User registration is currently not allowed.') );
					
				elseif	( isset($_GET['checkemail']) && 'confirm' == $_GET['checkemail'] )
					$errors->add( 'confirm', __('Check your e-mail for the confirmation link.'), 'message' );
					
				elseif	( isset($_GET['checkemail']) && 'newpass' == $_GET['checkemail'] )
					$errors->add( 'newpass', __('Check your e-mail for your new password.'), 'message' );
					
				elseif	( isset($_GET['checkemail']) && 'registered' == $_GET['checkemail'] )
					$errors->add( 'registered', __('Registration complete. Please check your e-mail.'), 'message' );
					
				elseif ( strpos( $redirect_to, 'about.php?updated' ) )
					$errors->add( 'updated', __( '<strong>You have successfully updated WordPress!</strong> Please log back in to experience the awesomeness.' ), 'message' );
			}
		
			$errors = apply_filters( 'wp_login_errors', $errors, $redirect_to );
			
			// Clear any stale cookies.
			if ( $reauth )
				wp_clear_auth_cookie();
			?>
			
			<?php /*** Start Form ***/ ?>
			
				<?php login_header( __('Log In') , '', $errors ); ?>
				
					<?php wp_signin_form( 'signin' ); ?>
			
					<?php wp_signin_nav(); ?>
		
					<script type="text/javascript">
						function wp_attempt_focus() {
							setTimeout( function(){ try{
							<?php if ( $user_login || $interim_login ) { ?>
							d = document.getElementById('user_pass');
							d.value = '';
							<?php } else { ?>
							d = document.getElementById('user_login');
							<?php if ( 'invalid_username' == $errors->get_error_code() ) { ?>
							if( d.value != '' )
							d.value = '';
							<?php
							}
							}?>
							d.focus();
							d.select();
							} catch(e){}
							}, 200);
							}
							
							<?php if ( !$error ) { ?>
							wp_attempt_focus();
							<?php } ?>
							if(typeof wpOnload=='function')wpOnload();
							<?php if ( $interim_login ) { ?>
							(function(){
							try {
								var i, links = document.getElementsByTagName('a');
								for ( i in links ) {
									if ( links[i].href )
										links[i].target = '_blank';
								}
							} catch(e){}
							}());
							
						<?php } ?>
					</script>
		
			<?php login_footer(); ?>
			
			<?php /*** End Form ***/ ?>
			
			<?php
		break;
		
	} // end action switch
}

/* 
 * New function to put all login and out forms into one
 */
function wp_signin_form( $form='' ) {
	
	switch ( $form ) {
	
		case 'signin' :
			 
			if ( isset( $_POST['log'] ) )
				$user_login = ( 'incorrect_password' == $errors->get_error_code() || 'empty_password' == $errors->get_error_code() ) ? esc_attr( wp_unslash( $_POST['log'] ) ) : '';
		
			$rememberme = ! empty( $_POST['rememberme'] );
		
			// not exactly sure how this is used but keeping it for now
			$interim_login = isset( $_REQUEST['interim-login'] ); 
		
			?>
		
			<form name="signform" id="signform" action="<?php echo esc_url( site_url( 'signin', 'login_post' ) ); ?>" method="post">
			
				<p>
					<label for="user_login"><?php _e('Username') ?><br />
					<input type="text" name="log" id="user_login" class="input" value="<?php echo esc_attr( $user_login ); ?>" size="20" /></label>
				</p>
				<p>
					<label for="user_pass"><?php _e('Password') ?><br />
					<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
				</p>
				
				<?php do_action('login_form'); ?>
				
				<p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever" <?php checked( $rememberme ); ?> /> <?php esc_attr_e('Remember Me'); ?></label></p>
				
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" />
				</p>
				
				<!-- Hidden Fields -->
				
				<input type="hidden" name="testcookie" value="1" />
	
				<?php if ( $interim_login ) { ?>
				
					<input type="hidden" name="interim-login" value="1" />
					
				<?php } else { ?>
				
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />
			
				<?php } ?>
				
				<?php if ( $customize_login ) { ?>
				
					<input type="hidden" name="customize-login" value="1" />
				
				<?php }; ?>
					
				
			</form>
			<?php
			
		break;
	} // end switch
}

/*
 * Link suseed under login form
 */
function wp_signin_nav() {
	global $action, $interim_login;
	
	echo $action;
	
	echo '<p id="nav">';

		?>
		
		<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log in' ); ?></a> |
		
		<?php
		
		//login
		if ( ! $interim_login ) { ?>
				
			<?php if ( ! isset( $_GET['checkemail'] ) || ! in_array( $_GET['checkemail'], array( 'confirm', 'newpass' ) ) ) : ?>
				
				<!-- probably do not need this for register page -->
				<?php if ( get_option( 'users_can_register' ) ) : ?>
				
					<?php echo apply_filters( 'register', sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register' ) ) ); ?> |
	
				<?php endif; ?>
				
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found' ); ?>"><?php _e( 'Lost your password?' ); ?></a>
				
			<?php endif; ?>
					
		<?php }
	
	
		// Don't allow interim logins to navigate away from the page.
			if ( ! $interim_login ): ?>
				<p id="backtoblog"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php esc_attr_e( 'Are you lost?' ); ?>"><?php printf( __( '&larr; Back to %s' ), get_bloginfo( 'title', 'display' ) ); ?></a></p>
			<?php endif; ?>
		
		<?php
	
	//end
	echo '</p>';
}

/*
 * Should get proper login header
 * Does not do anything yet
 */
function get_login_header( $args ) {
	
	global $error, $interim_login, $current_site, $action, $wp_error, $shake_error_codes;
	
	$defaults = array(
		'action'	=> '',
		'title'		=> '',
		'message'	=> '',
		'wp_error'	=> '',
	);
	
	$args = wp_parse_args( $args , $defaults );
	
	add_action( 'login_head', 'wp_no_robots' );

	if ( empty($wp_error) )
		$wp_error = new WP_Error();

	// Shake it!
	$shake_error_codes = array( 'empty_password', 'empty_email', 'invalid_email', 'invalidcombo', 'empty_username', 'invalid_username', 'incorrect_password' );
	$shake_error_codes = apply_filters( 'shake_error_codes', $shake_error_codes );
	
	if ( $shake_error_codes && $wp_error->get_error_code() && in_array( $wp_error->get_error_code(), $shake_error_codes ) )
		add_action( 'login_head', 'wp_shake_js', 12 );
				
	login_header( $title , $message , $wp_error );
	
}

function signin_header_link() {
	if ( is_multisite() ) {
		$signin_header_link   = network_home_url();
	} else {
		$signin_header_link   = __( 'http://wordpress.org/' );
	}

	$signin_header_link   = apply_filters( 'login_headerurl', $signin_header_link );
	$signin_header_link   = apply_filters( 'signin_header_link', $signin_header_link );

	return $signin_header_link;

}

function signin_header_title() {
	if ( is_multisite() ) {
		$signin_header_title = $current_site->site_name;
	} else {
		$signin_header_title = __( 'Powered by WordPress' );
	}

	$signin_header_title = apply_filters( 'login_headertitle', $signin_header_title );
	$signin_header_title = apply_filters( 'signin_header_link', $signin_header_title );
	
	return $signin_header_title;
}

function signin_classes() {
	global $action;
	
	// not exactly sure how this is used but keeping it for now
	$interim_login = isset( $_REQUEST['interim-login'] );	
	
	$classes = array( 'login-action-' . $action, 'wp-core-ui' );
	
	if ( wp_is_mobile() )
		$classes[] = 'mobile';
	if ( is_rtl() )
		$classes[] = 'rtl';
	if ( $interim_login ) {
		$classes[] = 'interim-login';
		?>
		<style type="text/css">html{background-color: transparent;}</style>
		<?php

		if ( 'success' ===  $interim_login )
			$classes[] = 'interim-login-success';
	}

	$classes = apply_filters( 'login_body_class', $classes, $action );	
	
	$classes = esc_attr( implode( ' ', $classes ) );
	
	return $classes;
}

/*
 * Determines and echoes messages
 */
function signin_messages() {
	global $message, $action;
	
	$message = apply_filters('login_message', $message);

	do_action( 'signin_messages' , $message, $action );
	
	if ( !empty( $message ) )
		echo $message . "\n";
		

}

/*
 * Determines and echoes errors
 */
function signin_errors() {
	
	$wp_error = $GLOBALS['singin_errors'];

	if ( ! empty( $wp_error ) && $wp_error->get_error_code() ) {
		$errors = '';
		$messages = '';
		foreach ( $wp_error->get_error_codes() as $code ) {
			$severity = $wp_error->get_error_data($code);
			foreach ( $wp_error->get_error_messages($code) as $error ) {
				if ( 'message' == $severity )
					$messages .= '	' . $error . "<br />\n";
				else
					$errors .= '	' . $error . "<br />\n";
			}
		}
		if ( !empty($errors) )
			echo '<div id="login_error">' . apply_filters('login_errors', $errors) . "</div>\n";
		if ( !empty($messages) )
			echo '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
	}
}

/*
 * Finds a template file
 * First looks in child theme, then theme, then plugin
 */
function get_signin_template( $template ) {
	global $wp_error;
	$template = $template .'.php';

	if ( file_exists( trailingslashit( get_stylesheet_directory() ) . $template ) )
		include( trailingslashit( get_stylesheet_directory() ) . $template );
	elseif ( file_exists( trailingslashit( get_template_directory() ) . $template ) )
		include( trailingslashit( get_template_directory() ) . $template );
	else
		include( BSIGN_DIR . '/templates/'. $template );
}

/**
 * Outputs the header for the login page.
 *
 * @uses do_action() Calls the 'login_head' for outputting HTML in the Log In
 *		header.
 * @uses apply_filters() Calls 'login_headerurl' for the top login link.
 * @uses apply_filters() Calls 'login_headertitle' for the top login title.
 * @uses apply_filters() Calls 'login_message' on the message to display in the
 *		header.
 * @uses $error The error global, which is checked for displaying errors.
 *
 * @param string $title Optional. WordPress Log In Page title to display in
 *		<title/> element.
 * @param string $message Optional. Message to display in header.
 * @param WP_Error $wp_error Optional. WordPress Error Object
 */
function login_header( $title = 'Log In', $message = '', $wp_error = '' ) {
	global $error, $interim_login, $current_site, $action, $shake_error_codes;
	$GLOBALS['singin_errors'] = $wp_error;
	get_signin_template( 'signin-header' );
}

/**
 * Outputs the footer for the login page.
 *
 * @param string $input_id Which input to auto-focus
 */
function login_footer( $input_id = '' ) {
	global $interim_login;
	
	get_signin_template( 'signin-footer' );

}

function wp_shake_js() {
	if ( wp_is_mobile() )
		return;
?>
<script type="text/javascript">
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
function s(id,pos){g(id).left=pos+'px';}
function g(id){return document.getElementById(id).style;}
function shake(id,a,d){c=a.shift();s(id,c);if(a.length>0){setTimeout(function(){shake(id,a,d);},d);}else{try{g(id).position='static';wp_attempt_focus();}catch(e){}}}
addLoadEvent(function(){ var p=new Array(15,30,15,0,-15,-30,-15,0);p=p.concat(p.concat(p));var i=document.forms[0].id;g(i).position='relative';shake(i,p,20);});
</script>
<?php
}

/**
 * Handles sending password retrieval email to user.
 *
 * @uses $wpdb WordPress Database object
 *
 * @return bool|WP_Error True: when finish. WP_Error on error
 */
function retrieve_password() {
	global $wpdb, $current_site;

	$errors = new WP_Error();

	if ( empty( $_POST['user_login'] ) ) {
		$errors->add('empty_username', __('<strong>ERROR</strong>: Enter a username or e-mail address.'));
	} else if ( strpos( $_POST['user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
		if ( empty( $user_data ) )
			$errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.'));
	} else {
		$login = trim($_POST['user_login']);
		$user_data = get_user_by('login', $login);
	}

	do_action('lostpassword_post');

	if ( $errors->get_error_code() )
		return $errors;

	if ( !$user_data ) {
		$errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or e-mail.'));
		return $errors;
	}

	// redefining user_login ensures we return the right case in the email
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;

	do_action('retreive_password', $user_login);  // Misspelled and deprecated
	do_action('retrieve_password', $user_login);

	$allow = apply_filters('allow_password_reset', true, $user_data->ID);

	if ( ! $allow )
		return new WP_Error('no_password_reset', __('Password reset is not allowed for this user'));
	else if ( is_wp_error($allow) )
		return $allow;

	$key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
	if ( empty($key) ) {
		// Generate something random for a key...
		$key = wp_generate_password(20, false);
		do_action('retrieve_password_key', $user_login, $key);
		// Now insert the new md5 key into the db
		$wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
	}
	$message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
	$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
	$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
	$message .= '<' . network_site_url("signin?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

	if ( is_multisite() )
		$blogname = $GLOBALS['current_site']->site_name;
	else
		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$title = sprintf( __('[%s] Password Reset'), $blogname );

	$title = apply_filters('retrieve_password_title', $title);
	$message = apply_filters('retrieve_password_message', $message, $key);

	if ( $message && !wp_mail($user_email, $title, $message) )
		wp_die( __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );

	return true;
}

/**
 * Retrieves a user row based on password reset key and login
 *
 * @uses $wpdb WordPress Database object
 *
 * @param string $key Hash to validate sending user's password
 * @param string $login The user login
 * @return object|WP_Error User's database row on success, error object for invalid keys
 */
function check_password_reset_key($key, $login) {
	global $wpdb;

	$key = preg_replace('/[^a-z0-9]/i', '', $key);

	if ( empty( $key ) || !is_string( $key ) )
		return new WP_Error('invalid_key', __('Invalid key'));

	if ( empty($login) || !is_string($login) )
		return new WP_Error('invalid_key', __('Invalid key'));

	$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));

	if ( empty( $user ) )
		return new WP_Error('invalid_key', __('Invalid key'));

	return $user;
}

/**
 * Handles resetting the user's password.
 *
 * @param object $user The user
 * @param string $new_pass New password for the user in plaintext
 */
function reset_password($user, $new_pass) {
	do_action('password_reset', $user, $new_pass);

	wp_set_password($new_pass, $user->ID);

	wp_password_change_notification($user);
}

/**
 * Handles registering a new user.
 *
 * @param string $user_login User's username for logging in
 * @param string $user_email User's email address to send password and add
 * @return int|WP_Error Either user's ID or error on failure.
 */
function register_new_user( $user_login, $user_email ) {
	$errors = new WP_Error();

	$sanitized_user_login = sanitize_user( $user_login );
	$user_email = apply_filters( 'user_registration_email', $user_email );

	// Check the username
	if ( $sanitized_user_login == '' ) {
		$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.' ) );
	} elseif ( ! validate_username( $user_login ) ) {
		$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
		$sanitized_user_login = '';
	} elseif ( username_exists( $sanitized_user_login ) ) {
		$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered. Please choose another one.' ) );
	}

	// Check the e-mail address
	if ( $user_email == '' ) {
		$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.' ) );
	} elseif ( ! is_email( $user_email ) ) {
		$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.' ) );
		$user_email = '';
	} elseif ( email_exists( $user_email ) ) {
		$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.' ) );
	}

	do_action( 'register_post', $sanitized_user_login, $user_email, $errors );

	$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email );

	if ( $errors->get_error_code() )
		return $errors;

	$user_pass = wp_generate_password( 12, false);
	$user_id = wp_create_user( $sanitized_user_login, $user_pass, $user_email );
	if ( ! $user_id ) {
		$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you&hellip; please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
		return $errors;
	}

	update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.

	wp_new_user_notification( $user_id, $user_pass );

	return $user_id;
}