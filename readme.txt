=== Mastodon Embed Improved ===
Contributors: usability.idealist
Tags: mastodon, social networks, social, opensocial, twitter, embed, shortcode, status, toot
Requires at least: 4.5
Tested up to: 5.0-nightly
Stable tag: 2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin to easily embed Mastodon statuses (so-called "toot").

== Description ==

A plugin to embed Mastodon statuses. Complete rewrite of [Mastodon embed](https://github.com/DavidLibeau/mastodon-tools) by David Libeau.

Currently implemented features:

* complete rewrite of mastodon-embed (originally by David Libeau) as class
* multiple embeds
* working caching
* proper shortcode initialization
* backward compatiblity for mastodon-embed
* directly embed toots instead of relying on the iframe method
* fallback: if direct import fails, try using the iframe method; add the attribute `use_iframe` (alias: `iframe`) to the shortcode and set it to `1` (eg. `[mastodon use_iframe="1"]http://my.mastodon.instance/@mastodon_user/12345[/mastodon]`)
* former fallback: to "direct" embeds if embed via iframe is forbidden (eg. when testing on localhost); use shortcode attribute `no_iframe` and set it to `1` (eg. `[mastodon no_iframe="1"]http://my.mastodon.instance/@mastodon_user/12345[/mastodon]`)
* Reverse-engineered CSS file (including LESS base) and override option (filter: mastodon_content_style)
* Uses different shortcode ('mastodon' instead of 'mastodon') if the original mastodon-embed is active as well
* Uses simple_html_dom class instead of XPath
* Optional manual cache refresh option via shortcode attribute
* improved debugging (WP_DEBUG + extended constants)
* Force URL scheme attribute ('force_scheme') to further improve SSL only vs. unencrypted http-only sites (ie. fun with SSL enforcement in WP ;))
* Center on a specific toot in a conversation (disable with 'no_center'); requires the direct embedding feature being enabled (parameter 'no_iframe')

= Work in progress =

* Settings screen in the admin interface
* Custom config file for advanced users (just drop it into the uploads folder, and tadaa! instant settings)

= Future plans = 

* Auto-Embed: An oEmbed-like embedding option (ie. just drop the URL into your post and its being automagickally turned into a proper Mastodon status embed)
* Optionally use the regular Mastodon API to retrieve toots (requires a working mastodon user account)
* Shortcode insertion via a nice user interface in the editor
* Properly implemented shortcode asset loading via a separate class / plugin

= Third-party libraries =

* Includes the [simple_html_dom](http://sourceforge.net/projects/simplehtmldom/) DOM Parser class (Revision 210), which is licensed under The MIT License (aka Expat License)

= Website =

http://f2w.de/mastodon-embed


= Please Vote and Enjoy =
Your votes really make a difference! Thanks.


== Installation ==

1. Upload 'mastodon-embed' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit an existing post or page - or create a new one
4. Insert toot / status URL and surround it with the shortcode '[mastodon]' (eg. `[mastodon]http://my.mastodon.instance/@mastodon_user/12345[/mastodon]`)
5. Read the documentation for better customization :)

== Frequently Asked Questions ==

= Shortcode documentation =

Regular shortcode: `[mastodon]http://my.mastodon.instance/@mastodon_user/12345[/mastodon]`
Shortcode using direct embed method: `[mastodon no_iframe="1"]http://my.mastodon.instance/@mastodon_user/12345[/mastodon]``

All available shortcode attributes:

* url - backward compatiblity
* container_class - change the class of the container div. Defaults to 'mastodon-embed'.
* width - Width in pixels (without the "px" unit!) of the iframe embed. Defaults to 700.
* height - Height of the iframe embed. Defaults to 200.
* css - Custom CSS for the iframe; Defaults to: `overflow: hidden`.
* cache_timeout - Defaults to 24 * 60 * 60 = 1 day. After this duration, the Mastodon status URL will be refreshed.
* use_iframe - Enable iframe embed method.
* iframe - Alias for 'use_iframe'.
* no_iframe - Kept for backward compatiblity to version <= 2.4.3. Original function: Disable iframe embed and use the direct content embedding instead. Automatically will load the custom CSS file, too.
* disable_iframe - Alias for 'no_iframe'.
* disable_font_awesome - Disable loading of Font Awesome when using the direct content embed (see above attribute), eg. if your theme is already including Font Awesome or you want to use different font icons (which have to be compatible to Font Awesome though).
* no_fa' - Alias
* flush - set this to 1 to refresh the embed cache; update post after this, give its frontend view a spin, and then remove it afterwards ;)
* force_scheme - set this to either 'http' or 'https' to enforce using this URL scheme (ie. protocol); primary use is to improve the SSL behaviour in WP
* no_center - Disables the "centering" function, which focuses on a single specific toot
* enable_debug - Explicitely enables the debug mode

= Q. The embedding does not work =
A. First test if there are any shortcode-interferring plugins. That could also be the original mastodon-embed. Aside of that, there was a mistake in the documentation before version 2.2.3, incorrectly stating the shortcode tag is 'mastodon_embed', while in reality it's **mastodon**.

= Q. "Center"ing on a specific toot does not work =
A. This only works with directly embedding the toot, not with the iframe method. Probably, the latter is never going to work, thanks to XSS / same-site policies in modern browsers.

= Q. I have a question =
A. Chances are, someone else has asked it. Either check out the support forum at WP or take a look at the official issue tracker:
http://github.com/ginsterbusch/mastodon-embed/issues

== Changelog ==

= 2.5 =

* "direct embed" function is the default behaviour

= 2.4.3 =

* Replaced 'center' parameter with 'no_center', thus reversing its functionality (kinda); ie. center function is now enabled by default, but still only works in conjunction with the 'no_iframe' attribute set to '1'

= 2.4.2 = 

* Added internal implementation of empty() for usage with shortcode attributes
* Updated the documentation to clarify that the newly introduced 'center' function works only in combination with directly embedded toots (parameter 'no_iframe')

= 2.4 =

* Added function to automatically pick out a single status from a conversation; enabled by default
* Fixed missing 'enable_debug' parameter

= 2.3 =

* Fixed weird SSL embed behaviour (for now)
* Added the shortcode attribute 'force_scheme' to improve SSL usage

= 2.2.3 =

* Improved WP_DEBUG behaviour - if WP_DEBUG_LOG is enabled, or WP_DEBUG_DISPLAY is set to false, the debugging data will not be displayed, EVEN IF the current user has the 'manage_options' capability (ie. administrator level).

= 2.2 =

* Fix: Corrected the cache_timeout (originally a constant was used, but I removed it from default usage and forgot to set the default attribute to something sensible); now set to 1 day (in seconds)
* Added the shortcode attribute 'flush' to enable manual cache refreshing

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

