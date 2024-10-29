<?php
/**
 * Plugin Name: Activation Add-On for GamiPress
 * Plugin URI: https://wordpress.org/plugins/activation-add-on-for-gamipress
 * Description: This GamiPress add-on adds a global switch in the Backend where the awarding of badges can be enabled and disabled.
 * Tags: buddypress, gamipress
 * Author: konnektiv
 * Version: 1.0.0
 * Author URI: https://konnektiv.de/
 * License: GNU AGPLv3
 * Text Domain: gamipress-activation-addon
 */

/*
 * Copyright © 2016 Konnektiv
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/agpl-3.0.html>;.
*/

class GamiPress_Activation_Addon {

	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( 'gamipress-activation-addon/' );

		// Load translations
		load_plugin_textdomain( 'gamipress-activation-addon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Run our activation
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// If GamiPress is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Files to include for GamiPress integration.
	 *
	 * @since  1.0.0
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/actions-filters.php' );
		}
	}

	/**
	 * Enqueue custom scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
	}

	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// If GamiPress is available, run our activation functions
		if ( $this->meets_requirements() ) {
			$this->includes();
		}

	}

	/**
	 * Check if GamiPress is available
	 *
	 * @since  1.0.0
	 * @return bool True if GamiPress is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('GamiPress') ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'GamiPress Activation Add-On requires GamiPress and has been <a href="%s">deactivated</a>. Please install and activate GamiPress and then reactivate this plugin.', 'gamipress-activation-addon' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

}
new GamiPress_Activation_Addon();
