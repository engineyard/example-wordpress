<?php
/**
 * @package PDO_For_Wordpress
 * @version $Id$
 * @author	Justin Adie, rathercurious.net
 */

/**
 * this function overrides the built in wordpress  variant
 *
 * @param object $blog_title
 * @param object $user_name
 * @param object $user_email
 * @param object $public
 * @param object $deprecated [optional]
 * @return
 */
function wp_install( $blog_title, $user_name, $user_email, $public, $deprecated = '', $user_password = '' ) {
	global $wp_rewrite, $wpdb;

	if ( !empty( $deprecated ) )
		_deprecated_argument( __FUNCTION__, '2.6' );

	//wp_check_mysql_version();
	wp_cache_flush();
	/**** changes start here ***/
	switch (DB_TYPE):
		case 'sqlite':
			require PDODIR . '/driver_sqlite/schema.php';
			installdb();
			break;
		case 'mysql':
			make_db_current_silent();
			break;
	endswitch;
	/**** changes end ***/
	populate_options();
	populate_roles();

	update_option('blogname', $blog_title);
	update_option('admin_email', $user_email);
	update_option('blog_public', $public);

	$guessurl = wp_guess_url();

	update_option('siteurl', $guessurl);

	// If not a public blog, don't ping.
	if ( ! $public )
		update_option('default_pingback_flag', 0);

	// Create default user.  If the user already exists, the user tables are
	// being shared among blogs.  Just set the role in that case.
	$user_id = username_exists($user_name);
	$user_password = trim($user_password);
	$email_password = false;
	if ( !$user_id && empty($user_password) ) {
		$user_password = wp_generate_password( 12, false );
		$message = __('<strong><em>Note that password</em></strong> carefully! It is a <em>random</em> password that was generated just for you.');
		$user_id = wp_create_user($user_name, $user_password, $user_email);
		update_user_option($user_id, 'default_password_nag', true, true);
		$email_password = true;
	} else if ( !$user_id ) {
		// Password has been provided
		$message = '<em>'.__('Your chosen password.').'</em>';
		$user_id = wp_create_user($user_name, $user_password, $user_email);
	} else {
		$message =  __('User already exists. Password inherited.');
	}

	$user = new WP_User($user_id);
	$user->set_role('administrator');

	wp_install_defaults($user_id);

	$wp_rewrite->flush_rules();

	wp_new_blog_notification($blog_title, $guessurl, $user_id, ($email_password ? $user_password : __('The password you chose during the install.') ) );

	wp_cache_flush();

	return array('url' => $guessurl, 'user_id' => $user_id, 'password' => $user_password, 'password_message' => $message);
}
?>