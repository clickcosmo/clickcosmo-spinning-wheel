<?php
/**
 * File: wp-content/plugins/YOUR-PLUGIN/includes/admin/cc-plugin-support-contact.php
 *
 * Built-in ClickCOSMO plugin support contact form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register one shared support page under Tools.
 * This avoids duplicate support menu items when multiple ClickCOSMO plugins are active.
 */
if ( ! function_exists( 'cc_plugin_support_contact_register_menu' ) ) {
	add_action( 'admin_menu', 'cc_plugin_support_contact_register_menu', 99 );

	function cc_plugin_support_contact_register_menu() {
		add_management_page(
			'ClickCOSMO Support',
			'ClickCOSMO Support',
			'read',
			'cc-plugin-support-contact',
			'cc_plugin_support_contact_render_page'
		);
	}
}

if ( ! function_exists( 'cc_plugin_support_contact_get_plugins' ) ) {
	function cc_plugin_support_contact_get_plugins() {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		$found   = [];

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			if ( ! is_plugin_active( $plugin_file ) ) {
				continue;
			}

			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

			$headers = get_file_data(
				$plugin_path,
				[
					'author'  => 'Author',
					'support' => 'ClickCOSMO Support',
				],
				'plugin'
			);

			$author  = isset( $headers['author'] ) ? trim( wp_strip_all_tags( $headers['author'] ) ) : '';
			$support = isset( $headers['support'] ) ? strtolower( trim( $headers['support'] ) ) : '';

			if ( $author !== 'ClickCOSMO' || $support !== 'yes' ) {
				continue;
			}

			$found[ $plugin_file ] = [
				'name'    => isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : $plugin_file,
				'version' => isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '',
				'file'    => $plugin_file,
			];
		}

		return $found;
	}
}

if ( ! function_exists( 'cc_plugin_support_contact_render_page' ) ) {
	function cc_plugin_support_contact_render_page() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to contact support.', 'cc-plugin-support' ) );
		}

		$current_user  = wp_get_current_user();
		$site_name     = get_bloginfo( 'name' );
		$site_url      = home_url();
		$admin_email   = get_option( 'admin_email' );
		$timezone      = wp_timezone_string();
		$site_language = get_locale();
		$cc_plugins = cc_plugin_support_contact_get_plugins();

		$notice = '';

		if (
			isset( $_POST['cc_plugin_support_submit'] ) &&
			check_admin_referer( 'cc_plugin_support_send', 'cc_plugin_support_nonce' )
		) {
			$result = cc_plugin_support_contact_handle_submit( $current_user );

			if ( is_wp_error( $result ) ) {
				$notice = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} else {
				$notice = '<div class="notice notice-success"><p>Support request sent.</p></div>';
			}
		}

		?>
		<style>
			.cc-support-wrap {
				max-width: 760px;
				margin-top: 24px;
			}

			.cc-support-card {
				background: #fff;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				padding: 20px;
				box-shadow: 0 1px 2px rgba(0,0,0,.04);
			}

			.cc-support-card h1 {
				margin: 0 0 10px;
				font-size: 24px;
				line-height: 1.3;
			}

			.cc-support-card p {
				max-width: 680px;
			}

			.cc-support-field {
				margin: 18px 0;
			}

			.cc-support-field label {
				display: block;
				font-weight: 600;
				margin-bottom: 6px;
			}

			.cc-support-field select,
			.cc-support-field textarea,
			.cc-support-field input[type="file"] {
				width: 100%;
				max-width: 680px;
			}

			.cc-support-field textarea {
				min-height: 180px;
			}

			.cc-support-meta {
				margin-top: 18px;
				padding: 12px;
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				border-radius: 6px;
				font-size: 13px;
			}

			.cc-support-meta strong {
				display: inline-block;
				min-width: 110px;
			}
		</style>

		<div class="wrap cc-support-wrap">
			<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<div class="cc-support-card">
				<h1>ClickCOSMO Support</h1>
				<p>Send a support message directly to <a href="https://clickcosmo.com" target="_blank">ClickCOSMO</a> support.</p>

				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'cc_plugin_support_send', 'cc_plugin_support_nonce' ); ?>

					<input type="hidden" name="cc_support_site_name" value="<?php echo esc_attr( $site_name ); ?>">
					<input type="hidden" name="cc_support_site_url" value="<?php echo esc_url( $site_url ); ?>">
					<input type="hidden" name="cc_support_reply_to" value="<?php echo esc_attr( $current_user->user_email ); ?>">

					<div class="cc-support-field">
	<label for="cc_support_plugin">ClickCOSMO Plugin</label>
<select id="cc_support_plugin" name="cc_support_plugin" required>
	<option value="">Select one</option>

	<?php foreach ( $cc_plugins as $plugin_file => $plugin ) : ?>
		<option value="<?php echo esc_attr( $plugin_file ); ?>">
			<?php echo esc_html( $plugin['name'] . ( $plugin['version'] ? ' v' . $plugin['version'] : '' ) ); ?>
		</option>
	<?php endforeach; ?>

	<option value="general">Not plugin related / General support</option>
</select>
</div>
					
					<div class="cc-support-field">
						<label for="cc_support_subject_type">Subject</label>
						<select id="cc_support_subject_type" name="cc_support_subject_type" required>
							<option value="">Select one</option>
							<option value="Support Request">Support Request</option>
							<option value="Feature Request">Feature Request</option>
							<option value="Other">Other</option>
						</select>
					</div>

					<div class="cc-support-field">
						<label for="cc_support_message">Description</label>
						<textarea id="cc_support_message" name="cc_support_message" required></textarea>
					</div>

					<div class="cc-support-field">
						<label for="cc_support_attachment">Optional Attachment</label>
						<input id="cc_support_attachment" name="cc_support_attachment" type="file">
					</div>

					<div class="cc-support-meta">
						<div><strong>First Name:</strong> <?php echo esc_html( $current_user->first_name ); ?></div>
						<div><strong>Last Name:</strong> <?php echo esc_html( $current_user->last_name ); ?></div>
						<div><strong>Username:</strong> <?php echo esc_html( $current_user->user_login ); ?></div>
						<div><strong>Email:</strong> <?php echo esc_html( $current_user->user_email ); ?></div>
						<div><strong>Site:</strong> <?php echo esc_html( $site_name ); ?></div>
						<div><strong>Site URL:</strong> <?php echo esc_url( $site_url ); ?></div>
						<div><strong>Timezone:</strong> <?php echo esc_html( $timezone ); ?></div>
						<div><strong>Site Language:</strong> <?php echo esc_html( $site_language ); ?></div>
						<div><strong>Sender:</strong> <?php echo esc_html( $admin_email ); ?></div>
					</div>

					<p>
						<button type="submit" name="cc_plugin_support_submit" class="button button-primary">
							Send Message
						</button>
					</p>
				</form>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'cc_plugin_support_contact_handle_submit' ) ) {
	function cc_plugin_support_contact_handle_submit( WP_User $current_user ) {
		$allowed_subjects = [
			'Support Request',
			'Feature Request',
			'Other',
		];

		$subject_type = isset( $_POST['cc_support_subject_type'] )
			? sanitize_text_field( wp_unslash( $_POST['cc_support_subject_type'] ) )
			: '';

		$message = isset( $_POST['cc_support_message'] )
			? sanitize_textarea_field( wp_unslash( $_POST['cc_support_message'] ) )
			: '';

		$selected_plugin = isset( $_POST['cc_support_plugin'] )
	? sanitize_text_field( wp_unslash( $_POST['cc_support_plugin'] ) )
	: '';

$cc_plugins = cc_plugin_support_contact_get_plugins();

if ( $selected_plugin === '' ) {
	return new WP_Error( 'missing_plugin', 'Please select a plugin or general support.' );
}

if ( $selected_plugin === 'general' ) {
	$selected_plugin_name    = 'Not plugin related / General support';
	$selected_plugin_version = '';
	$selected_plugin_file    = '';
} elseif ( isset( $cc_plugins[ $selected_plugin ] ) ) {
	$selected_plugin_name    = $cc_plugins[ $selected_plugin ]['name'];
	$selected_plugin_version = $cc_plugins[ $selected_plugin ]['version'];
	$selected_plugin_file    = $cc_plugins[ $selected_plugin ]['file'];
} else {
	return new WP_Error( 'invalid_plugin', 'Please select a valid plugin.' );
}
		
		if ( ! in_array( $subject_type, $allowed_subjects, true ) ) {
			return new WP_Error( 'invalid_subject', 'Please select a valid subject.' );
		}

		if ( $message === '' ) {
			return new WP_Error( 'missing_message', 'Please enter a description.' );
		}

		$to            = 'support@clickcosmo.com';
		$site_name     = get_bloginfo( 'name' );
		$site_url      = home_url();
		$admin_email   = get_option( 'admin_email' );
		$timezone      = wp_timezone_string();
		$site_language = get_locale();

		$email_subject = '[' . $site_name . '] ' . $subject_type;

		$email_body =
			"New Message from Plugin Support\n\n" .
			"Subject Type: {$subject_type}\n\n" .
			"Message:\n{$message}\n\n" .
			"------------------------------\n" .
			"Plugin Meta\n" .
			"Selected Plugin: {$selected_plugin_name}\n" .
			"Plugin Version: {$selected_plugin_version}\n" .
			"Plugin File: {$selected_plugin_file}\n\n" .
			"------------------------------\n" .
			"User Meta\n" .
			"First Name: {$current_user->first_name}\n" .
			"Last Name: {$current_user->last_name}\n" .
			"Username: {$current_user->user_login}\n" .
			"Email: {$current_user->user_email}\n" .
			"User ID: {$current_user->ID}\n\n" .
			"------------------------------\n" .
			"Site Meta\n" .
			"Site Name: {$site_name}\n" .
			"Site URL: {$site_url}\n" .
			"Timezone: {$timezone}\n" .
			"Site Language: {$site_language}\n" .
			"Admin Email / Sender: {$admin_email}\n";

		$headers = [
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . $site_name . ' <' . $admin_email . '>',
			'Reply-To: ' . $current_user->display_name . ' <' . $current_user->user_email . '>',
		];

		$attachments = [];

		if (
			! empty( $_FILES['cc_support_attachment']['name'] ) &&
			isset( $_FILES['cc_support_attachment']['tmp_name'] )
		) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$uploaded = wp_handle_upload(
				$_FILES['cc_support_attachment'],
				[
					'test_form' => false,
				]
			);

			if ( isset( $uploaded['error'] ) ) {
				return new WP_Error( 'upload_failed', $uploaded['error'] );
			}

			if ( ! empty( $uploaded['file'] ) ) {
				$attachments[] = $uploaded['file'];
			}
		}

		$sent = wp_mail( $to, $email_subject, $email_body, $headers, $attachments );

		if ( ! $sent ) {
			return new WP_Error( 'mail_failed', 'Email could not be sent.' );
		}

		return true;
	}
}
