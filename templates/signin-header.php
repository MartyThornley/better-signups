<?php
/*
 * Signin Page Header
 */
?>

<?php $wp_error = $GLOBALS['singin_errors']; ?>
<?php $title = $GLOBALS['singin_title']; ?>
<?php $message = $GLOBALS['singin_message']; ?>

<!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
	<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php bloginfo('name'); ?> &rsaquo; <?php echo $title; ?></title>
	<?php

	wp_admin_css( 'wp-admin', true );
	wp_admin_css( 'colors-fresh', true );
	
	$signin_header_link = signin_header_link();
	//$signin_header_title = signin_header_title();

	if ( wp_is_mobile() ) { ?>
		<meta name="viewport" content="width=320, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" /><?php
	}
	
	// Remove all stored post data on logging out.
	// This could be added by add_action('login_head'...) like wp_shake_js()
	// but maybe better if it's not removable by plugins
	if ( ! empty( $wp_error ) && 'loggedout' == $wp_error->get_error_code() ) {
		?>
		<script>if("sessionStorage" in window){try{for(var key in sessionStorage){if(key.indexOf("wp-autosave-")!=-1){sessionStorage.removeItem(key)}}}catch(e){}};</script>
		<?php
	}

	do_action( 'login_enqueue_scripts' );
	do_action( 'login_head' );
		
	?>
	</head>
	
	<body class="login <?php echo signin_classes(); ?>">
		
		<div id="login" class="test">
			<h1><a href="<?php echo esc_url( $signin_header_link ); ?>" title="<?php echo esc_attr( $signin_header_title ); ?>"><?php bloginfo( 'name' ); ?></a></h1>

			<?php unset( $signin_header_link, $signin_header_title ); ?>
			
			<?php signin_messages(); ?>
			
			<?php signin_errors(); ?>
		