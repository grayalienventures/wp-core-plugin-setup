<form action='options.php' method='post'>
	<h1>JWT Authentication</h1>
	<?php
	settings_fields( 'jwt_authentication' );
	do_settings_sections( 'jwt_authentication' );
	submit_button();
	?>
	<h2><?php esc_html_e( 'Getting started', 'jwt-authentication' ); ?></h2>
	<p>
		<?php // Translators: %s is a link to wiki. ?>
		
	</p>
</form>
