<?php

require_once __DIR__ .'/fragment-dom-document.class.php';

class ContentAttachments
{
	const CA_TEMPLATE = <<<HTML
		<!-- START filtered content attachment -->
		<a class="{{class}}" type="{{mime-type}}" href="{{url}}">
			<span class="content-attachment-text">{{text}}</span>
			<span><i class="content-attachment-icon"></i></span>
		</a>
		<!-- END filtered content attachment -->
HTML;

	protected static $instance;

	protected function __construct() {
		add_filter( 'media_send_to_editor', [$this, 'filterEditorMedia'], 10, 3 );
		add_filter( 'the_content', [$this, 'filterTheContent'], 10 );
		add_filter( 'acf_the_content', [$this, 'filterTheContent'], 11 );
	}

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

	/**
	 * Customize DOM for post attachments
	 *
	 * @param $contentHtml
	 *
	 * @return string
	 */
	function filterTheContent($contentHtml) {
		// Bail if DOM extension is not loaded
		if (!extension_loaded('dom') || empty($contentHtml))
			return $contentHtml;

		$content = new FragmentDOMDocument();
		$content->loadHTML($contentHtml);

		$query = new DOMXPath($content);
		$anchors = $query->query('//a[contains(concat(" ", @class, " "), " content-attachment ")]');
		foreach ( $anchors as $anchor ) {
			/** @var $anchor DOMElement */

			/*
			 * Skip attachment prefixed or followed by a TEXT node.
			 * This works as long as wpautop is executed before this hook on the content.
			 */
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
			$templateHtml = apply_filters('content-attachments_template', static::CA_TEMPLATE);
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



	public static function init()
	{
		if (!static::$instance)
			static::$instance = new static();
	}

	public function filterEditorMedia($html, $send_id, $attachment) {
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
	}
}


