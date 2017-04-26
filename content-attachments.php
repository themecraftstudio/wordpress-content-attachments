<?php
/*
 * Plugin Name: Content Attachments
 * Plugin URI:  https://wordpress.org/plugins/content-attachments/
 * Description: Wrap non-image post attachments in handy tags that allow easy styling with CSS.
 * Version:     0.0.2
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

require_once __DIR__ .'/includes/fragment-dom-document.class.php';

// IMPORTANT: the template gets wrapped with a <div> element
define('CA_TEMPLATE', <<<HTML
	<!--<span>BEFORE</span>-->
	<a class="{{class}}" type="{{mime-type}}" href="{{url}}">
		<span class="content-attachment-text">{{text}}</span>
		<span><i class="content-attachment-icon"></i></span>
	</a>
	<!--<span>AFTER</span>-->
HTML
);

add_filter('media_send_to_editor', function ($html, $send_id, $attachment) {
	$mimeType = get_post_mime_type($attachment['id']);

	if (extension_loaded('dom')) {
		$doc = new FragmentDOMDocument();
		$doc->loadHTML($html);

		$query = new DOMXPath($doc);
		$anchors = $query->query('//a[@href]');
		foreach ( $anchors as $anchor ) {
			/** @var $anchor DOMElement */
			$classes = explode(' ', $anchor->getAttribute('class'));
			$classes[] = 'content-attachment';

			$anchor->setAttribute('class', trim(implode(' ', $classes)));

			// Adds MIME type if detected.
			if ($mimeType)
				$anchor->setAttribute('type', $mimeType);
		}

		$html = $doc->saveHTML();
	}

	return $html;
}, 10, 3);

/**
 * Customize DOM for post attachments
 */
add_filter( 'the_content', function ($contentHtml) {
	$post = get_post();

	if (extension_loaded('dom') && !(empty($contentHtml))) {
		$content = new FragmentDOMDocument();
		$content->loadHTML($contentHtml);

		$query = new DOMXPath($content);
		$anchors = $query->query('//a[contains(concat(" ", @class, " "), " content-attachment ")]');
		foreach ( $anchors as $anchor ) {
			/** @var $anchor DOMElement */

			$prev = $anchor->previousSibling;
			$next = $anchor->nextSibling;

			// Skip attachment prefixed or followed by text
			if (($anchor->previousSibling && $anchor->previousSibling->nodeType == XML_TEXT_NODE) ||
				($anchor->nextSibling && $anchor->nextSibling->nodeType == XML_TEXT_NODE))
				continue;

			try {
				$text = '';
				foreach ($anchor->childNodes as $child_node)
					/** @var $child_node DOMNode */
					if ($child_node->nodeType == XML_TEXT_NODE)
						$text = $child_node->nodeValue;

				$mimeType = $anchor->getAttribute('type'); // empty string of attribute is not found
				$url = $anchor->getAttribute('href'); // empty string of attribute is not found

				// Parse HTML template
				$templateHtml = CA_TEMPLATE;
				$templateHtml = str_replace('{{class}}', $anchor->getAttribute('class'), $templateHtml);
				$templateHtml = str_replace('{{mime-type}}', $mimeType, $templateHtml);
				$templateHtml = str_replace('{{url}}', $url, $templateHtml);
				$templateHtml = str_replace('{{text}}', $text, $templateHtml);

				$template = new FragmentDOMDocument();
				$template->loadHTML($templateHtml);

				// Import the template
				foreach ($template->getChildNodes() as $child) {
					/** @var DOMNode $child */
					$node = $content->importNode($child, true);
					$anchor->parentNode->insertBefore($node, $anchor);
				}

				$anchor->parentNode->removeChild($anchor);
			} catch (\Exception $e) {
				echo sprintf('<pre>%s</pre>', var_export($e, true));
			}
		}

		$contentHtml = $content->saveHTML();
	}

	return $contentHtml;
});
