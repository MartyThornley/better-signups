better-signups
==============

## Better Signups

A proof of concept to show a different approach to the signup, login and logout process.

## Required Temporary Hooks

This requires a couple Temporary Hooks, small hacks to core, to work.

In wp-login.php add after line 16 or so.. 

after:
require( dirname(__FILE__) . '/wp-load.php' );

add:
do_action( 'hijack_login' );


In wp-signup.php, add to line 3 or so, before any executed code:

after:
require( dirname(__FILE__) . '/wp-load.php' );

add:
do_action( 'hijack_signup' ); 


