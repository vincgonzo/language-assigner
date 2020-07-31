<?php
/**
* Plugin Name: Language Management
* Description: Plugin to manage multisite Language.
* Version: 1.1
* Author: @VincentCastanie
**/
 

if ( ! defined( 'ABSPATH' ) ) {
        die( '-1' );
}
 
// Plugin constants
define( 'LANG_MANAGE_VERSION', '1.1' );
define( 'LANG_MANAGE_FOLDER', 'bnpp-language-management' );

/**
* Holds the filesystem directory path (with trailing slash) for Top 10
* @since 2.2.0
* @var string Plugin Root File
*/
if ( ! defined( 'LANG_MANAGE_FILE' ) ) {
              define( 'LANG_MANAGE_FILE', __FILE__ );
}

define( 'LANG_MANAGE_URL', plugin_dir_url( __FILE__ ) );
define( 'LANG_MANAGE_DIR', plugin_dir_path( __FILE__ ) );

// Function for easy load files
function _language_management_load_files( $dir, $files, $prefix = '' ) {
    foreach ( $files as $file ) {
        if ( is_file( $dir . $prefix . $file . '.php' ) ) {
            require_once( $dir . $prefix . $file . '.php' );
        }
    }
}

// Plugin client classes
_language_management_load_files( LANG_MANAGE_DIR . 'classes/', array( 'plugin' ) );
add_action( 'plugins_loaded', '_loaded_language_management_plugin' );

function _loaded_language_management_plugin() {
        new LANG_MANAGEMENT_Plugin();
}