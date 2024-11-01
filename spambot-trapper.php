<?php
defined( 'ABSPATH' ) || exit;
/*
Plugin Name: Spambot Trapper
Plugin URI: http://peter.cassetta.info/spambot-trapper/
Description: Spambot Trapper stops most spambot-posted comments from ever reaching your spam queue by changing comment form field names and adding invisible "trap" fields. Spambot Trapper is an easy and lightweight way to stop those evil spambots dead in their tracks.
Author: Peter Cassetta
Version: 1.4.2
Author URI: http://peter.cassetta.info

Copyright (C) 2013 Peter Cassetta

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see http://www.gnu.org/licenses/
or write to the Free Software Foundation, Inc., 51 Franklin Street,
Fifth Floor, Boston, MA 02110-1301, USA.
*/

//register_activation_hook( __FILE__, 'sbtr_activate' );
//register_deactivation_hook( __FILE__, 'sbtr_deactivate' );

$sbtr_name_field = get_option('sbtr_name_field'); //The name of the real name field.
$sbtr_email_field = get_option('sbtr_email_field'); //The name of the real email field.
$sbtr_url_field = get_option('sbtr_url_field'); //The name of the real website field.

function sbtr_comment_form_modified_fields ($fields) {
	//Replace original fields with modified ones.
	
	if (is_user_logged_in())
	{
		return $fields;
	}
	
	global $sbtr_name_field;
	global $sbtr_email_field;
	global $sbtr_url_field;
	
	//Set needed variables.
	$commenter = wp_get_current_commenter();
	
	//$fields['email'] = str_replace('type="text"','type="email"', $fields['email']);
	//$fields['url'] = str_replace('type="text"','type="url"', $fields['url']);
	
	$a = $fields['author'];
	
	$author_originals = array('id="author"', 'for="author"', 'name="author"');
	$author_replacements = array("id=\"$sbtr_name_field\"", "for=\"$sbtr_name_field\"", "name=\"$sbtr_name_field\"");
	
	$author_internal = substr($a, strpos($a, '>')+1, strrpos($a, '<') - strpos($a, '>') - 1 );
	$author_internal_edited = '<span style="display:none;">' . str_replace('value="'.$commenter['comment_author'].'"', 'value=""', $author_internal) . '</span>' . str_replace($author_originals, $author_replacements, $author_internal);
	
	$fields['author'] = str_replace( $author_internal , $author_internal_edited , $a );
	
	
	$e = $fields['email'];
	
	$email_originals = array('id="email"', 'for="email"', 'name="email"');
	$email_replacements = array("id=\"$sbtr_email_field\"", "for=\"$sbtr_email_field\"", "name=\"$sbtr_email_field\"");
	
	$email_internal = substr($e, strpos($e, '>')+1, strrpos($e, '<') - strpos($e, '>') - 1 );
	$email_internal_edited = '<span style="display:none">' . str_replace('value="'.$commenter['comment_author_email'].'"', 'value=""', $email_internal) . '</span>' . str_replace($email_originals, $email_replacements, $email_internal);
	
	$fields['email'] = str_replace( $email_internal , $email_internal_edited , $e );
	
	
	$u = $fields['url'];
	
	$url_originals = array('id="url"', 'for="url"', 'name="url"');
	$url_replacements = array("id=\"$sbtr_url_field\"", "for=\"$sbtr_url_field\"", "name=\"$sbtr_url_field\"");
	
	$url_internal = substr($u, strpos($u, '>')+1, strrpos($u, '<') - strpos($u, '>') - 1 );
	$url_internal_edited = '<span style="display:none">' . str_replace('value="'.$commenter['comment_author_url'].'"', 'value=""', $url_internal) . '</span>' . str_replace($url_originals, $url_replacements, $url_internal);
	
	$fields['url'] = str_replace( $url_internal , $url_internal_edited , $u );
	
	
	return $fields;
}

function sbtr_comment_form_defaults ($defaults) {
	$req = get_option( 'require_name_email' );
	$required_text = sprintf( ' ' . __('Required fields are marked %s'), '<span class="required">*</span>' );
	$defaults['comment_notes_before'] = '<p class="comment-notes">' . __( 'Your email address will not be published.' ) . ( $req ? $required_text : '' ) . '</p>';
	return $defaults;
}

function sbtr_comment_terminator ($post_ID) {
	//Terminate comments before they reach the database
	//if they have not been posted by a real user.
	global $sbtr_name_field;
	global $sbtr_url_field;
	global $sbtr_email_field;
	if (is_user_logged_in() || ( isset($_POST[$sbtr_name_field]) && empty($_POST['author']) && isset($_POST[$sbtr_email_field]) && empty($_POST['email']) && isset($_POST[$sbtr_url_field]) && empty($_POST['url']) ) )
	{
		return $post_ID;
	}
	else
	{
		wp_die( __('<strong>ERROR</strong>: you have posted your comment with invalid fields!') );
	}
}

function sbtr_comment_author_name ($comment_author) {
	//Set correct name for ham comments.
	global $sbtr_name_field;
	if (!is_user_logged_in() && isset($_POST[$sbtr_name_field]))
	{
		if ( get_option( 'require_name_email' ) && ( '' == $_POST[$sbtr_name_field] ) )
			wp_die( __('<strong>ERROR</strong>: please fill the required fields (name, email).') );
		$comment_author = $_POST[$sbtr_name_field];
	}
	return $comment_author;
}

function sbtr_comment_author_email ($comment_author_email) {
	//Set correct email for ham comments.
	global $sbtr_email_field;
	if (!is_user_logged_in() && isset($_POST[$sbtr_email_field]))
	{
		if ( get_option( 'require_name_email' ) && ( 6 > strlen($_POST[$sbtr_email_field]) ) )
			wp_die( __('<strong>ERROR</strong>: please fill the required fields (name, email).') );
		if ( !is_email($_POST[$sbtr_email_field]) )
			wp_die( __('<strong>ERROR</strong>: please enter a valid email address.') );
		$comment_author_email = $_POST[$sbtr_email_field];
	}
	return $comment_author_email;
}

function sbtr_comment_author_url ($comment_author_url) {
	//Set correct website for ham comments.
	global $sbtr_url_field;
	if (!is_user_logged_in() && isset($_POST[$sbtr_url_field]))
		$comment_author_url = $_POST[$sbtr_url_field];
	return $comment_author_url;
}

function sbtr_require_name_email ( $require_name_email ) {
	if (strpos( $_SERVER['REQUEST_URI'] , 'wp-comments-post.php' ) === false)
		return $require_name_email;
	return 0;
}

add_filter( 'option_require_name_email', 'sbtr_require_name_email' );

add_filter('comment_form_default_fields', 'sbtr_comment_form_modified_fields');
add_filter('comment_form_defaults', 'sbtr_comment_form_defaults');

add_filter('pre_comment_on_post', 'sbtr_comment_terminator');

add_filter('pre_comment_author_name', 'sbtr_comment_author_name');
add_filter('pre_comment_author_email', 'sbtr_comment_author_email');
add_filter('pre_comment_author_url', 'sbtr_comment_author_url');

//Plugin Settings
function sbtr_settings_init () {
	add_settings_section('sbtr_settings', 'Spambot Trapper Settings', 'sbtr_settings_section', 'discussion');
	
	add_settings_field('sbtr_name_field', 'Values of real <code>name</code> attributes for comment fields (should not be empty!)', 'sbtr_settings_name_field', 'discussion', 'sbtr_settings');
	add_settings_field('sbtr_email_field', '', 'sbtr_settings_email_field', 'discussion', 'sbtr_settings');
	add_settings_field('sbtr_url_field', '', 'sbtr_settings_url_field', 'discussion', 'sbtr_settings');
	register_setting('discussion', 'sbtr_name_field');
	register_setting('discussion', 'sbtr_email_field');
	register_setting('discussion', 'sbtr_url_field');
}
add_filter('admin_init', 'sbtr_settings_init' );


 
function sbtr_settings_section () {
	echo '<p>Settings for the Spambot Trapper plugin.</p>';
}

function sbtr_settings_name_field () {
	echo '<input name="sbtr_name_field" id="sbtr_name_field" type="text" value="' . get_option('sbtr_name_field') . '" /> Name';
}
function sbtr_settings_email_field () {
	echo '<input name="sbtr_email_field" id="sbtr_email_field" type="text" value="' . get_option('sbtr_email_field') . '" /> Email';
}
function sbtr_settings_url_field () {
	echo '<input name="sbtr_url_field" id="sbtr_url_field" type="text" value="' . get_option('sbtr_url_field') . '" /> Website';
}

function sbtr_generated_string ($length = 15) {
	//Generates a random string of length $length.
	return substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", $length)), 0, $length);
}

function sbtr_on_activate () {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "activate-plugin_{$plugin}" );
	
	//Check if the real field name options have been set.
	if ( get_option( 'sbtr_name_field' ) === false )
		update_option( 'sbtr_name_field', sbtr_generated_string() );
	
	if ( get_option( 'sbtr_email_field' ) === false )
		update_option( 'sbtr_email_field', sbtr_generated_string() );
	
	if ( get_option( 'sbtr_url_field' ) === false )
		update_option( 'sbtr_url_field', sbtr_generated_string() );
}

function sbtr_on_deactivate () {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	check_admin_referer( "deactivate-plugin_{$plugin}" );
	
}

function sbtr_on_uninstall () {
	if ( ! current_user_can( 'activate_plugins' ) )
		return;
	check_admin_referer( 'bulk-plugins' );

	//Remove options
	delete_option( 'sbtr_name_field' );
	delete_option( 'sbtr_email_field' );
	delete_option( 'sbtr_url_field' );
}

register_activation_hook(   __FILE__, 'sbtr_on_activate' );
register_deactivation_hook( __FILE__, 'sbtr_on_deactivate' );
register_uninstall_hook(    __FILE__, 'sbtr_on_uninstall' );
?>
