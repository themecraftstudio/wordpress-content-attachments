<?php


class FragmentDOMDocument extends DOMDocument
{
	function loadHTML($source, $options = 0)
	{
		parent::loadHTML(sprintf('<?xml encoding="utf-8" ?><div id="content-attachments-body">%s</div>', $source), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		// Remove PI
		foreach ($this->childNodes as $node) {
			/** @var DOMNode $node */
			if ($node->nodeType === XML_PI_NODE) {
				$this->removeChild($node);
			}
		}
		$this->encoding = 'UTF-8';
	}

	function saveHTML(DOMNode $node = null)
	{
		if ($node)
			return parent::saveHTML($node);

		// Unwraps body nodes
		$body = $this->getElementById('content-attachments-body');

		$html = '';
		foreach ($body->childNodes as $node) {
			/** @var DOMNode $node */
			$html = sprintf("%s\n%s", $html, $this->saveHTML($node));
		}

		return $html;
	}

	function getChildNodes()
	{
		return $this->childNodes->item(0)->childNodes;
	}
}

