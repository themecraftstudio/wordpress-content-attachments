<?php
/*
 * Plugin Name: Content Attachments
 * Plugin URI:  https://wordpress.org/plugins/content-attachments/
 * Description: Wrap non-image post attachments in handy tags that allow easy styling with CSS.
 * Version:     0.2.2
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

// TODO move to admin settings
define('CA_TEMPLATE', <<<HTML
	<!-- START filtered content attachment -->
	<a class="{{class}}" type="{{mime-type}}" href="{{url}}">
		<span class="content-attachment-text">{{text}}</span>
		<span><i class="content-attachment-icon"></i></span>
	</a>
	<!-- END filtered content attachment -->
HTML
);

add_filter('media_send_to_editor', function ($html, $send_id, $attachment) {
	// Bail if DOM extension is not loaded
	if (!extension_loaded('dom'))
		return $html;

	$id = $attachment['id'];

	// Skip image and video|audio attachments with embedded player
	if (wp_attachment_is('image', $id) ||
	        ((wp_attachment_is('video') || wp_attachment_is('audio')) && $html[0] === '['))
		return $html;

	$mimeType = get_post_mime_type($id);

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

	return $html;
}, 10, 3);

/**
 * Customize DOM for post attachments
 *
 * @param $contentHtml
 *
 * @return string
 */
function content_attachments_filter_content($contentHtml) {
	// Bail if DOM extension is not loaded
	if (!extension_loaded('dom') || empty($contentHtml))
		return $contentHtml;

	$content = new FragmentDOMDocument();
	$content->loadHTML($contentHtml);

	$query = new DOMXPath($content);
	$anchors = $query->query('//a[contains(concat(" ", @class, " "), " content-attachment ")]');
	foreach ( $anchors as $anchor ) {
		/** @var $anchor DOMElement */

		// Skip attachment prefixed or followed by text
		if (($anchor->previousSibling && $anchor->previousSibling->nodeType == XML_TEXT_NODE) ||
		    ($anchor->nextSibling && $anchor->nextSibling->nodeType == XML_TEXT_NODE))
			continue;

		$text = '';
		foreach ($anchor->childNodes as $child_node)
			/** @var $child_node DOMNode */
			if ($child_node->nodeType == XML_TEXT_NODE)
				$text = $child_node->nodeValue;

		$mimeType = $anchor->getAttribute('type'); // empty string if attribute is not found
		$url = $anchor->getAttribute('href'); // empty string if attribute is not found

		// Skip attachments that do not resolve to a post id (e.g. "Link to Attachment Page")
		if (0 === attachment_url_to_postid(urldecode($url)))
			continue;

		// Parse HTML template
		$templateHtml = apply_filters('content-attachments_template', CA_TEMPLATE);
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
	}

	$contentHtml = $content->saveHTML();

	return $contentHtml;
}
add_filter( 'the_content', 'content_attachments_filter_content', 10 );
add_filter( 'acf_the_content', 'content_attachments_filter_content', 11 );

class ContentAttachments
{
	/**
	 * Returns attachment ids for each attachment linking to its media file.
	 *
	 * @param string|WP_Post|int $contentHtml
	 * @return array of post ids
	 */
	public static function getContentAttachments($contentHtml)
	{
		if (is_integer($contentHtml))
			$contentHtml = get_post($contentHtml);
		if ($contentHtml instanceof WP_Post)
			$contentHtml = $contentHtml->post_content;

		$content = new FragmentDOMDocument();
		$content->loadHTML($contentHtml);

		$query = new DOMXPath($content);
		$anchors = $query->query('//a[contains(concat(" ", @class, " "), " content-attachment ")]');

		$attachments = [];
		foreach ($anchors as $anchor) {
			/** @var DOMElement $anchor */
			$url = $anchor->getAttribute('href');
			//$type = $anchor->getAttribute('type'); // empty string if attribute is not found

			// evaluates to 0 when the anchor does not link to the media file directly (e.g. attachment page)
			$id = attachment_url_to_postid(urldecode($url));
			if ($id)
				$attachments[] = $id;
		}

		return $attachments;
	}
}
