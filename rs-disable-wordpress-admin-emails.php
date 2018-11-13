<?php
/*
Plugin Name: RS Disable WP Emails
Description: Disables the New User and password changed notifications which are sent to admins. If either cannot be overwritten, a warning will be displayed on the dashboard. If you don't see a warning, it's working!
Version:     1.0.0
Plugin URI:  http://radleysustaire.com/
Author:      Radley Sustaire
Author URI:  mailto:radleygh@gmail.com
License:     GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

// Disable the admin component of new user registration emails.
if ( !function_exists('wp_new_user_notification') ) :
	function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
		if ( $deprecated !== null ) {
			_deprecated_argument( __FUNCTION__, '4.3.1' );
		}
		
		if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
			return;
		}
		
		global $wpdb, $wp_hasher;
		$user = get_userdata( $user_id );
		
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		
		$key = wp_generate_password( 20, false );
		
		do_action( 'retrieve_password_key', $user->user_login, $key );
		
		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
		$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );
		
		$message = sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
		$message .= __( 'To set your password, visit the following address:' ) . "\r\n\r\n";
		$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . ">\r\n\r\n";
		
		$message .= wp_login_url() . "\r\n";
		
		wp_mail( $user->user_email, sprintf( __( '[%s] Your username and password info' ), $blogname ), $message );
	}
else:
	add_action( 'admin_notices', 'rs_cant_disable_email_new_user' );
endif;


// Disable password change notification
if ( !function_exists('wp_password_change_notification') ) :
	function wp_password_change_notification( $user ) {
		return;
	}
else:
	add_action( 'admin_notices', 'rs_cant_disable_email_password_changed' );
endif;

function rs_cant_disable_email_new_user() {
	?>
	<div class="notice notice-warning">
		<p><strong>RS Disable WP Emails:</strong> Can't disable the New User email notification sent to admins. The function wp_new_user_notification is defined elsewhere.</p>
		<pre><?php
			try {
				$reflFunc = new ReflectionFunction('wp_new_user_notification');
				print $reflFunc->getFileName() . ':' . $reflFunc->getStartLine();
			} catch( Exception $e ) { echo '(Error: The class "ReflectionFunction" does not exist, unable to identify function declaration file)'; }
		?></pre>
	</div>
	<?php
}

function rs_cant_disable_email_password_changed() {
	?>
	<div class="notice notice-warning">
		<p><strong>RS Disable WP Emails:</strong> Can't disable the Password Changed email notification sent to admins. The function wp_password_change_notification is defined here:</p>
		
		<pre><?php
			try {
				$reflFunc = new ReflectionFunction('wp_password_change_notification');
				print $reflFunc->getFileName() . ':' . $reflFunc->getStartLine();
			} catch( Exception $e ) { echo '(Error: The class "ReflectionFunction" does not exist, unable to identify function declaration file)'; }
		?></pre>
		
	</div>
	<?php
}