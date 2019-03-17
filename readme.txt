=== Content Attachments ===
Contributors: themecraft
Requires at least: 4.4.0
Tested up to: 5.1.1
Requires PHP: 7.0.0
Stable tag: trunk
License: GPL2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wrap non-image post attachments in handy tags that allow easy styling with CSS.


== Description ==
Wraps post attachments that are not images in handy tags that allow easy styling with CSS. It also adds a class to the attachment containing the mime type.

Use `add_filter('content-attachments/template/paragraph', $html)` to customized the HTML markup to render the attachment. Defaults to:

```
<a class="{{classes}}" type="{{mime-type}}" href="{{url}}" {{extra_attrs}}>
	<span class="content-attachment-text">{{text}}</span>
	<span><i class="content-attachment-icon"></i></span>
</a>
```

For this plugin to work properly, "wpautop" (i.e. automatic wrapping in `p` tags of lines written in WYSIWYG editors) must be enabled on WYSIWYG content editors of both WordPress and ACF. This is the default behavior.

Please report issues or ask questions on (GitHub)[https://github.com/themecraftstudio/wordpress-content-attachments]


== Frequently Asked Questions ==

= Where should I report issues / get help? =

**GitHub** Please report problems directly on the plugin's [main development repository](https://github.com/themecraftstudio/wordpress-content-attachments)


== Changelog ==
= 1.0.0 =
*Release Date - 31 January 2019*

* New - {{extra_attrs}} placeholder to include all other attributes present in the anchor
* Deprecated - {{class}} in favor of {{classes}}

= 0.3.1 =
*Release Date - 31 August 2018*

* Fix - load HTML content as UTF-8

= 0.3.0 =

* New - filter 'content-attachments/template/paragraph'
* Deprecated - filter 'content-attachments_template'

= 0.0.1 =
*Release Date - 21 April 2017*

* Initial release
