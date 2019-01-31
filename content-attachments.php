<?php
/*
 * Plugin Name: Content Attachments
 * Plugin URI:  https://wordpress.org/plugins/content-attachments/
 * Description: Wrap non-image post attachments in handy tags that allow easy styling with CSS.
 * Version:     1.0.0
 * Author:      Themecraft Studio
 * Author URI:  https://themecraft.studio/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: content-attachments
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/themecraftstudio/wordpress-content-attachments

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

if (class_exists('ContentAttachments'))
	die(_('Cannot load Content Attachments by Themecraft Studio as a class with the name ContentAttachments is already defined'));

require_once __DIR__ .'/includes/ContentAttachments.php';

ContentAttachments::init();
