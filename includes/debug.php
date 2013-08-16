<?php

	function _debug_echo( $stuff='' , $file='' , $line='' ) {
		echo $stuff . ' - ' . $file . ' line: ' . $line . '</br />';
	}
	
	function _debug_print( $stuff ) {
			print '<pre>'; print_r( $stuff ); print '</pre>';
	}