<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://cliengo.com/?utm_source=wordpress_plugin&utm_medium=wordpress
 * @since             0.0.1
 * @package           Cliengo
 *
 * @wordpress-plugin
 * Plugin Name:       Cliengo - Chatbot
 * Plugin URI:        http://cliengo.com/?utm_source=wordpress_plugin&utm_medium=wordpress
 * Description:       Cliengo helps you escalate your business process by automating the different points of contact with your customers and providing 24/7 responses. Multi-language: English/Spanish/Portuguese
 * Version:           3.0.4
 * Author:            Cliengo
 * Author URI:        http://cliengo.com/?utm_source=wordpress_plugin&utm_medium=wordpress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cliengo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( !defined('ABSPATH') ) {
    exit("Do not access this file directly.");
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CLIENGO_VERSION', '3.0.4' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cliengo-activator.php
 */
function activate_cliengo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cliengo-activator.php';
	$initializer = new Cliengo_Activator();
	$initializer->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cliengo-deactivator.php
 */
function deactivate_cliengo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cliengo-deactivator.php';
	Cliengo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cliengo' );
register_deactivation_hook( __FILE__, 'deactivate_cliengo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cliengo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_cliengo() {

	$plugin = new Cliengo();
	$plugin->run();

}
run_cliengo();
