<?php

class ContentAttachments
{
	const TEMPLATE_PARAGRAPH = <<<HTML
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
		add_filter( 'the_content', [$this, 'filterTheContent'], 11 );
		add_filter( 'acf_the_content', [$this, 'filterTheContent'], 11 );
	}

	/**
	 * Instantiates the singleton
	 */
	public static function init()
	{
		if (!static::$instance)
			static::$instance = new static();
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

		$content = new DOMDocument();
		$content->loadHTML($contentHtml, LIBXML_HTML_NODEFDTD);

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

		$content = new DOMDocument();
		$content->loadHTML($contentHtml, LIBXML_HTML_NODEFDTD);

		$query = new DOMXPath($content);
		$anchors = $query->query('//a[contains(concat(" ", @class, " "), " content-attachment ")]');
		foreach ( $anchors as $anchor ) {
			/** @var $anchor DOMElement */
			$url = $anchor->getAttribute('href'); // empty string if attribute is not found
			$mimeType = $anchor->getAttribute('type'); // empty string if attribute is not found

			// Debug
			$parent = $anchor->parentNode;
			$prevNode = $anchor->previousSibling;
			$nextNode = $anchor->nextSibling;
			$siblings = [];
			foreach ($anchor->parentNode->childNodes as $node)
				$siblings[] = $node;


			// Skip attachments that do not resolve to a post id (e.g. "Link to Attachment Page")
			if (0 === attachment_url_to_postid(urldecode($url)))
				continue;

			/*
			 * <p><a><a/></p>
			 */
			if ($parent->childNodes->length === 1 && in_array($parent->tagName, ['p']))
			{
				// Replace with single line template

				/* TODO support generic DOM tree inside <a> vs text node only. */
				$text = '';
				foreach ($anchor->childNodes as $child_node)
					/** @var $child_node DOMNode */
					if ($child_node->nodeType == XML_TEXT_NODE)
						$text = $child_node->nodeValue;

				// Parse HTML template
				$templateHtml = apply_filters('content-attachments_template', static::TEMPLATE_PARAGRAPH);
				$templateHtml = apply_filters('content-attachments/template/paragraph', $templateHtml);
				$templateHtml = str_replace('{{class}}', $anchor->getAttribute('class'), $templateHtml);
				$templateHtml = str_replace('{{mime-type}}', $mimeType, $templateHtml);
				$templateHtml = str_replace('{{url}}', $url, $templateHtml);
				$templateHtml = str_replace('{{text}}', $text, $templateHtml);

				$template = $content->createDocumentFragment();
				$template->appendXML($templateHtml);
				$anchor->parentNode->insertBefore($template, $anchor);
				$anchor->parentNode->removeChild($anchor);
			}

		    // if list

			// else inline
		}

		$contentHtml = $content->saveHTML($content->firstChild->firstChild);
		$contentHtml = preg_replace('/<\/?body>/i', '', $contentHtml);

		return $contentHtml;
	}

	/**
	 * @param $html
	 * @param $send_id
	 * @param $attachment
	 *
	 * @return string
	 */
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

		$doc = new DOMDocument();
		$doc->loadHTML($html, LIBXML_HTML_NODEFDTD);

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

		$html = $doc->saveHTML($doc->firstChild->firstChild);
		$html = preg_replace('/<\/?body>/i', '', $html);

		return $html;
	}
}


