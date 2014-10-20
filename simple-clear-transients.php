<?php
/*
Plugin Name: Simple Clear Transients
Plugin URI: http://plugins.findingsimple.com
Description: Add a clear transients button to the admin bar to help with development
Version: 1.0
Author: Finding Simple
Author URI: http://findingsimple.com
License: GPL2
*/
/*
Copyright 2012  Finding Simple  (email : plugins@findingsimple.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'Simple_Clear_Transients' ) ) :

/**
 * So that themes and other plugins can customise the text domain, the Simple_Quotes
 * should not be initialized until after the plugins_loaded and after_setup_theme hooks.
 * However, it also needs to run early on the init hook.
 *
 * @package Simple Clear Transients
 * @since 1.0
 */
function initialize_clear_transients(){
	Simple_Clear_Transients::init();
}
add_action( 'init', 'initialize_clear_transients', -1 );

/**
 * Plugin Main Class.
 *
 * @package Simple Clear Transients
 * @since 1.0
 */
class Simple_Clear_Transients {

	static $text_domain;

	static $post_type_name;

	static $admin_screen_id;
	
	/**
	 * Initialise
	 */
	public static function init() {
		global $wp_version;

		self::$text_domain = apply_filters( 'simple_ct_text_domain', 'Simple_CT' );

		self::$admin_screen_id = apply_filters( 'simple_ct_admin_screen_id', 'simple_ct' );
		
		add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'simple_ct_admin_bar_render' ) );	
			
		add_action( 'wp_head', array( __CLASS__, 'simple_ct_enqueue_styles_and_scripts' ) );
		
		add_action( 'admin_head', array( __CLASS__, 'simple_ct_enqueue_styles_and_scripts' ) );
		
		//only needed for logged in users so don't use "wp_ajax_nopriv_"	
		add_action( 'wp_ajax_simple-ct-ajax', array( __CLASS__, 'simple_ct_ajax_submit' ) );
		
	}

	/**
	 * Delete transients
	 *
	 * @since 1.0
	 */	
	public static function delete_db_transients() {

    	global $wpdb, $_wp_using_ext_object_cache;

		if( $_wp_using_ext_object_cache )
			return;
	
		$transients = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout%';" );
	
		foreach( $transients as $transient ) {
	
			$key = str_replace( '_transient_timeout_' , '' , $transient );
			
			delete_transient( $key );
			
		}
    
	}

	/**
	 * Delete sitewide transients
	 *
	 * @since 1.0
	 */	
	public static function delete_db_site_transients() {

    	global $wpdb, $_wp_using_ext_object_cache;

		if( $_wp_using_ext_object_cache )
			return;
	
		$transients = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout%';" );
	
		foreach( $transients as $transient ) {
	
			$key = str_replace( '_site_transient_timeout_' , '' , $transient );
			
			delete_site_transient( $key );
						
		}
    
	}
	
	/**
	 * Add item to admin bar
	 *
	 * @since 1.0
	 */
	public static function simple_ct_admin_bar_render() {
	
		global $wp_admin_bar;
				
		$onclick = "smplct();";
		
		$wp_admin_bar->add_menu( array(
			'id' => 'simple-ct-admin-bar',
			'parent' => false, 
			'title' => __('Clear Transients'), 
			'href' => '#', 
			'meta' => array(
				'onclick' => $onclick
			) 
		));
	}
	
	/**
	 * Enqueues the necessary scripts and styles for the plugin
	 *
	 * @since 1.0
	 */
	public static function simple_ct_enqueue_styles_and_scripts() {
		
		if ( is_admin_bar_showing() ) {
		
			wp_register_script( 'simple-clear-transient-request', self::get_url( '/js/ajax.js' , __FILE__ ) , array( 'jquery', 'json2' ) );
			wp_enqueue_script( 'simple-clear-transient-request' );
			
			wp_localize_script( 'simple-clear-transient-request', 'SimpleAjax', array( 
				'ajaxurl' => admin_url( 'admin-ajax.php' ), 
				'wpnonce' => wp_create_nonce( 'simple-clear-transient-nonce' )
				)
			);
		
		}
	
	}
	
	/**
	 * Handle the ajax request
	 *
	 * @since 1.0
	 */	 
	public static function simple_ct_ajax_submit() {
	
		$nonce = $_REQUEST['nonce'];
			 
		// check nonce
		if ( ! wp_verify_nonce( $nonce, 'simple-clear-transient-nonce' ) )
			die ( 'Busted!');
	 
		// check permissions
		if ( current_user_can( 'manage_options' ) ) {
	 
			self::delete_db_site_transients();
			self::delete_db_transients();
			
			//set response to true
			$response = json_encode( array( 'success' => true ) );
	 
			// output response
			header( "Content-Type: application/json" );

			echo $response;
			
		}
	 
		exit;
		
	}	
	
	/**
	 * Helper function to get the URL of a given file. 
	 * 
	 * As this plugin may be used as both a stand-alone plugin and as a submodule of 
	 * a theme, the standard WP API functions, like plugins_url() can not be used. 
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function get_url( $file ) {

		// Get the path of this file after the WP content directory
		$post_content_path = substr( dirname( str_replace('\\','/',__FILE__) ), strpos( __FILE__, basename( WP_CONTENT_DIR ) ) + strlen( basename( WP_CONTENT_DIR ) ) );
		
		// Return a content URL for this path & the specified file
		return content_url( $post_content_path . $file );
		
	}	

}

endif;