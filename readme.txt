=== Mastodon Embed Improved ===
Contributors: usability.idealist
Tags: mastodon, social networks, social, opensocial, twitter, embed, shortcode, status
Requires at least: 4.5
Tested up to: 4.8
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Includes simple_html_dom DOM Parser (Revision 210), which is licensed under The MIT License (http://sourceforge.net/projects/simplehtmldom/)

== Description ==

A plugin to embed Mastodon statuses. Complete rewrite of [Mastodon embed](https://github.com/DavidLibeau/mastodon-tools) by David Libeau. Tested up to WP 4.8-nightly

Currently implemented features:

* complete rewrite as class of mastodon-embed (originally by David Libeau)
* multiple embeds
* working caching
* proper shortcode initialization
* backward compatiblity for mastodon-embed
* fallback to "direct" embeds if embed via iframe is forbidden (eg. when testing on localhost); use shortcode attribute `no_iframe` and set it to `1` (eg. `[mastodon_embed no_iframe="1"]http://my.mastodon.instance/@mastodon_user/12345[/mastodon_embed]`)
* Reverse-engineered CSS file (including LESS base) and override option (filter: mastodon_embed_content_style)
* Uses different shortcode ('mastodon_embed' instead of 'mastodon') if the original mastodon-embed is active as well
* Uses simple_html_dom class instead of XPath

= Future plans = 

* Shortcode insertion via a nice user interface in the editor
* Maybe a settings page or a custom config file
* Properly implemented shortcode asset loading via a separate class / plugin

= Website =

http://f2w.de/mastodon-embed


= Please Vote and Enjoy =
Your votes really make a difference! Thanks.


== Installation ==

1. Upload 'mastodon-embed-improved' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit an existing post or create a new one
4. Insert toot / status URL and surround it with the shortcode '[mastodon_embed]' (eg. `[mastodon_embed]http://my.mastodon.instance/@mastodon_user/12345[/mastodon_embed]`)
5. Read the documentation for better customization :)

== Frequently Asked Questions ==

= Shortcode documentation =

Regular shortcode: `[mastodon_embed]http://my.mastodon.instance/@mastodon_user/12345[/mastodon_embed]`
Shortcode using direct embed method: `[mastodon_embed no_iframe="1"]http://my.mastodon.instance/@mastodon_user/12345[/mastodon_embed]``

All available shortcode attributes:

* url - backward compatiblity
* container_class - change the class of the container div. Defaults to 'mastodon-embed'.
* width - Width in pixels (without the "px" unit!) of the iframe embed. Defaults to 700.
* height - Height of the iframe embed. Defaults to 200.
* css - Custom CSS for the iframe; Defaults to: `overflow: hidden`.
* cache_timeout - Defaults to 24 * 60 * 60 = 1 day. After this duration, the Mastodon status URL will be refreshed.
* no_iframe - Disable iframe embed and use the direct content embedding instead. Automatically will load the custom CSS file, too.
* disable_font_awesome - Disable loading of Font Awesome when using the direct content embed (see above attribute), eg. if your theme is already including Font Awesome or you want to use different font icons (which have to be compatible to Font Awesome though).
* no_fa' - Alias

= Q. I have a question =
A. Chances are, someone else has asked it. Either check out the support forum at WP or take a look at the official issue tracker:
http://github.com/ginsterbusch/mastodon-embed-improved/issues

== Changelog ==

= 2.1 =

* Added "direct embed" option as a fallback for development and testing purposes

= 2.0 =

* Rewrite as class
* Extensive testing
* See Feature List for all the rest ...

= 1.9 =

* Initial improvement attempt

== Upgrade Notice ==

None yet.

