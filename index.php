<?php
/**
 * Plugin Name: SitesForBernie Signup Email
 * Description: Provides a nicer email notification
 * Version: 1.0
 * Author: Atticus White
 * Author URI: http://atticuswhite.com
 */

add_action('init', 'test');
function test () {
  wp_new_user_notification(17, true);
}

function getUserEmailTemplate($user) {
  $loginUrl = wp_login_url();
  $siteUrl = str_replace('/wp-login.php', '', $loginUrl);
  $activateUrl = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
  $assetUrl = plugins_url('/template/images', __FILE__);

  $domain = str_replace('http://', '', $loginUrl);
  $domain = explode('/', $domain);
  $domain = $domain[0];

  ob_start();
  include(__DIR__ . '/template/new_user.php');
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}


function wp_new_user_notification( $user_id, $notify = '' ) {
	global $wpdb;
	$user = get_userdata( $user_id );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user->user_email) . "\r\n";


	// @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

	if ( 'admin' === $notify || empty( $notify ) ) {
		return;
	}

	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	/** This action is documented in wp-login.php */
	do_action( 'retrieve_password_key', $user->user_login, $key );

	// Now insert the key, hashed, into the DB.
	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	// $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

	$message = sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
	$message .= __('To set your password, visit the following address:') . "\r\n\r\n";
	$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . ">\r\n\r\n";

	$message .= wp_login_url() . "\r\n";


    echo getUserEmailTemplate($user);
    die();

	// wp_mail($user->user_email, sprintf(__('[%s] Your username and password info'), $blogname), $message);
}
