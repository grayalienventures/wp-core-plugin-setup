<h2>
	<?php

	use WPCorePlugin\WPCorePlugin;

	use function WPCorePlugin\parse_user_agent;

	esc_html_e('JWT Authentication API Tokens', WPCorePlugin::BASE_SLUG); ?></h2>
<table class="table widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Token UUID', WPCorePlugin::BASE_SLUG); ?></th>
			<th><?php esc_html_e('Last used', WPCorePlugin::BASE_SLUG); ?></th>
			<th><?php esc_html_e('By IP', WPCorePlugin::BASE_SLUG); ?></th>
			<th><?php esc_html_e('Browser', WPCorePlugin::BASE_SLUG); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php if (!empty($tokens)) : ?>
			<?php
			$current_url = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$current_url = remove_query_arg(array('revoked', 'removed', 'jwtupdated', 'revoke_token', 'jwt_nonce'), $current_url);
			$revoke_url_all = wp_nonce_url(
				add_query_arg(
					array(
						'revoke_all_tokens' => 1,
					),
					$current_url
				),
				'jwt-ui-nonce',
				'jwt_nonce'
			);
			?>
			<?php foreach ($tokens as $token) : ?>
				<?php
				$ua_info    = parse_user_agent($token['ua']);
				$revoke_url = wp_nonce_url(
					add_query_arg(
						array(
							'revoke_token' => $token['uuid'],
						),
						$current_url
					),
					'jwt-ui-nonce',
					'jwt_nonce'
				);




				$platform = $ua_info['browser'];
				// error_log("current_url:".$current_url);
				// error_log($revoke_url);
				?>
				<tr>
					<td><?php echo esc_html($token['uuid']); ?></td>
					<td><?php echo esc_html(date_i18n('Y-m-d H:i:s', $token['last_used'])); ?></td>
					<td><?php echo esc_html($token['ip']); ?> <a href="<?php echo esc_url(sprintf('https://ipinfo.io/%s', $token['ip'])); ?>" target="_blank" title="Look up IP location" class="button-link"><?php esc_html_e('Lookup', WPCorePlugin::BASE_SLUG); ?></a></td>
					<td><?php echo sprintf(__('<strong>Platform</strong> %1$s. <strong>%s:</strong> %2$s. <strong>Browser version:</strong> %3$s', WPCorePlugin::BASE_SLUG), esc_html($platform), esc_html($ua_info['browser']), esc_html($ua_info['version'])); // phpcs:ignore 
						?></td>
					<td>
						<div class="d-flex flex-row p-2">

							<a href="<?php echo esc_url($revoke_url); ?>" title="<?php esc_html_e('Revokes this token from being used any further.', WPCorePlugin::BASE_SLUG); ?>" class="button-secondary"><?php esc_html_e('Revoke', WPCorePlugin::BASE_SLUG); ?></a>
						</div>

					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<td colspan="6" valign="right">
					<a href="<?php echo esc_url($revoke_url_all); ?>" class="button-secondary" title="<?php esc_html_e('Doing this will require the user to login again on all devices.', WPCorePlugin::BASE_SLUG); ?>"><?php esc_html_e('Revoke all tokens', WPCorePlugin::BASE_SLUG); ?></a>
					<!-- <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('remove_expired_tokens', '1', $current_url)), 'intp-jwt-ui-nonce', 'jwt_nonce'); ?>" class="button-secondary" title="<?php esc_html_e('Doing this will not affect logged in devices for this user.', WPCorePlugin::BASE_SLUG); ?>"><?php esc_html_e('Remove all expired tokens', WPCorePlugin::BASE_SLUG); ?></a> -->
				</td>
			</tr>
		<?php else : ?>
			<tr>
				<td colspan="6"><?php esc_html_e('No tokens generated.', WPCorePlugin::BASE_SLUG); ?></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>