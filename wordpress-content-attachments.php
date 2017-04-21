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

add_filter('media_send_to_editor', function ($html, $send_id, $attachment) {
	$mimeType = get_post_mime_type($attachment['id']);

	if (extension_loaded('dom')) {
		$doc = new DOMDocument();
		$doc->loadHTML(sprintf('<?xml encoding="utf-8" ?><div>%s</div>', $html), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		// dirty fix for encoding
		foreach ($doc->childNodes as $item)
			if ($item->nodeType == XML_PI_NODE)
				$doc->removeChild($item);
		$doc->encoding = 'UTF-8';

		$query = new DOMXPath($doc);
		$anchors = $query->query('//a[@href]');
		foreach ( $anchors as $anchor ) {
			/** @var $anchor DOMElement */
			$anchor->setAttribute('class',
				sprintf('content-attachment content-attachment-%s', str_replace(['/', '.'], '-', $mimeType)));
		}

		$html = $doc->saveHTML();
	}

	return $html;
}, 10, 3);

/**
 * Customize DOM for post attachments
 */
add_filter( 'the_content', function ($content) {
	$post = get_post();

	if (extension_loaded('dom') && !(empty($content))) {
		$iconClasses = ['icon-download-arrow'];
		$buttonText = '&#xE2C4;';
//		$buttonClasses = ['button'];

		$doc = new DOMDocument();
		$doc->loadHTML(sprintf('<?xml encoding="utf-8" ?><div>%s</div>', $content), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		// dirty fix for encoding
		foreach ($doc->childNodes as $item)
			if ($item->nodeType == XML_PI_NODE)
				$doc->removeChild($item);
		$doc->encoding = 'UTF-8';

		$query = new DOMXPath($doc);
		$anchors = $query->query('//a[contains(concat(" ", @class, " "), " content-attachment ")]');
		foreach ( $anchors as $anchor ) {
			/** @var $anchor DOMElement */

			// Skip attachment prefixed or followed by text
			if (($anchor->previousSibling && $anchor->previousSibling->nodeType == XML_TEXT_NODE) ||
				($anchor->nextSibling && $anchor->nextSibling->nodeType == XML_TEXT_NODE))
				continue;

			try {
				$anchorText = '';
				foreach ($anchor->childNodes as $child_node) {
					/** @var $child_node DOMNode */
					if ($child_node->nodeType == XML_TEXT_NODE)
						$anchorText = $child_node->nodeValue;

					// Remove all children but the anchor text node
					$anchor->removeChild($child_node);
				}

				// Mime type icon
				$icon = $doc->createElement('i');
				$icon->setAttribute('class', sprintf('%s content-attachment-icon', implode(' ', $iconClasses)));

				// Text
				$anchorTextSpan = $doc->createElement('span', $anchorText);
				$anchorTextSpan->setAttribute('class', 'content-attachment-name');

				// Wrap icon with <span>
				$buttonSpan = $doc->createElement('span', $buttonText);
//				$icon->setAttribute('class', sprintf('%s content-attachment-button', implode(' ', $buttonClasses)));
				$buttonSpan->insertBefore($icon, $buttonSpan->firstChild);

				if ( $anchor->firstChild == null) {
					$anchor->appendChild($anchorTextSpan);
					$anchor->appendChild($buttonSpan);
				}
			} catch (\Exception $e) {
				echo sprintf('<pre>%s</pre>', var_export($e, true));
			}
		}

		$content = $doc->saveHTML();
	}

	return $content;
});
