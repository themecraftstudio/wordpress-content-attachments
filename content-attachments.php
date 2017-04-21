<?php
/*
 * Plugin Name: Content Attachments
 * Plugin URI:  https://wordpress.org/plugins/content-attachments/
 * Description: TODO
 * Version:     0.0.1
 * Author:      Themecraft Studio
 * Author URI:  https://themecraft.studio/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: content-attachments
 * Domain Path: /languages

 Content Attachments is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 Content Attachments is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Content Attachments. If not, see LICENSE.txt .
 */

require_once __DIR__ . '/vendor/autoload.php';


/**
 * Activation hook
 */
register_activation_hook( __FILE__, function () {

} );
/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function () {

} );
/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'contentattachments_uninstall');
function openidconnect_uninstall() {
	Settings::uninstall();
}

// Register admin settings
//Settings::register();
