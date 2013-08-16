<?php
/*
 * Signin Footer
 */


			echo '</div>';
			
			if ( !empty( $input_id ) ) : ?>

				<script type="text/javascript">
				try{document.getElementById('<?php echo $input_id; ?>').focus();}catch(e){}
				if(typeof wpOnload=='function')wpOnload();
				</script>

			<?php endif; ?>

			<?php do_action('login_footer'); ?>
		
			<div class="clear"></div>
		
		</body>
	</html>