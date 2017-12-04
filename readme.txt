=== Content Attachments ===
Contributors: themecraft
Requires at least: 4.4.0
Tested up to: 4.9.1
Stable tag: trunk
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wrap non-image post attachments in handy tags that allow easy styling with CSS.

== Description ==
Wraps post attachments that are not images in handy tags that allow easy styling with CSS. It also adds a class to the attachment containing the mime type.

Use add_filter('content-attachments_template', $html) to customized the HTML markup to render the attachment. Defaults to
```
<a class="{{class}}" type="{{mime-type}}" href="{{url}}">
	<span class="content-attachment-text">{{text}}</span>
	<span><i class="content-attachment-icon"></i></span>
</a>
```

For this plugin to work properly, "wpautop" (i.e. automatic wrapping in <p> tags of lines written in WYSIWYG editors) must be enabled on WYSIWYG content editors of both WordPress and ACF.
This is the default behavior.

Main development repository at https://github.com/themecraftstudio/wordpress-content-attachments

== Installation ==
As with any other plugin hosted on wordpress.org

== Changelog ==
0.0.2 Initial release
