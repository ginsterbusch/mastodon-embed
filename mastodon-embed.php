<?php
/**
 * Plugin Name: Mastodon Embed Improved
 * Plugin URI: http://f2w.de/mastodon-embed
 * Description: A plugin to embed Mastodon statuses. Complete rewrite of <a href="https://github.com/DavidLibeau/mastodon-tools">Mastodon embed</a> by David Libeau. Tested up to WP 4.8-nightly
 * Version: 2.4.3
 * Author: Fabian Wolf
 * Author URI: http://usability-idealist.de
 * License: GNU GPL v2 or later
 *
 * Features:
 * - class instead function
 * - multiple embeds
 * - working caching
 * - proper shortcode initialization
 * - backward compatiblity for mastodon-embed
 * - fallback to "direct" embeds if embed via iframe is forbidden (eg. when testing on localhost)
 * - direct embed with reverse-engineered CSS file (including LESS base) and override option (filter: mastodon_embed_content_style)
 * - uses different shortcode ('mastodon_embed' instead of 'mastodon') if the original mastodon-embed is active as well
 * - uses simple_html_dom instead of XPath
 * - cache refresh via attribute ('flush')
 * - improved debugging (WP_DEBUG + extended constants)
 * - alias for no_iframe attribute: disable_iframe
 * - force URL scheme attribute ('force_scheme') to further improve SSL only vs. unencrypted http-only sites (ie. fun with SSL enforcement in WP ;))
 * - automatically picks out specific single toot; to display the complete conversation, set 'no_center' to 1; also, only works with "direct" toot embedding (ie. parameter "no_iframe" set to 1)
 */

if( !class_exists( 'simple_html_dom' ) ) {
	require_once( plugin_dir_path(__FILE__) . 'simple_html_dom.php' );
}

class __mastodon_embed_plugin {
	public 	$pluginName = 'Mastodon Embed Improved',
			$pluginPrefix = 'mastodon_embed_';
	
	public 	$shortcode_name = 'mastodon',
			$is_compatiblity_mode = false;
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance() {

		NULL === self::$instance and self::$instance = new self( true );

		return self::$instance;
	}
	
	function __construct( $plugin_init = false ) {
		if( !empty( $plugin_init ) ) {
			
			// check if the original mastodon is active and change the shortcode name accordingly
			if( function_exists( 'mastodon_embed_callback' ) ) {
				$this->is_compatiblity_mode = true;
				$this->shortcode_name = 'mastodon_embed';
			}
			
			
			$this->init_assets();
			
			add_shortcode( $this->shortcode_name, array( $this, 'shortcode' ) );
		}
	}
	
	
	function init_assets() {
		/**
		 * @hook mastodon_embed_content_style		URL to the direct embed content CSS.
		 * @hook mastodon:embed_font_awesome_url	Font Awesome CDN URL. Replace with your own or set to null to disable it from being loaded at all.
		 */
		
		$embed_content_style = apply_filters( $this->pluginPrefix . 'content_style', plugin_dir_url(__FILE__) . 'embed-content.css' );
		$embed_content_fa_url = apply_filters( $this->pluginPrefix . 'font_awesome_url', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
		
		if( !empty( $embed_content_fa_url ) ) {
			wp_register_style( $this->pluginPrefix . 'font-awesome', $embed_content_fa_url );
		}
		
		wp_register_style( $this->pluginPrefix . 'content', $embed_content_style );
		
	}
	
	
	/**
	 * Debug data
	 * NOTE: Only output debug information if either the debug mode is enabled OR the user has admin privileges!
	 */
	function is_debug() {
		$return = false;
		if( ( defined( 'WP_DEBUG' ) && WP_DEBUG != false ) || current_user_can( 'manage_options' ) != false ) {
			$return = true;
		}
		
		/**
		 * Enhanced mode
		 * NOTE: Looks a bit awkward, but this stuff is reallly complicated to handle .. o.O
		 */
		if( defined( 'WP_DEBUG' ) && WP_DEBUG != false ) {
			if( ( defined('WP_DEBUG_LOG' ) && WP_DEBUG_LOG != false ) || ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY == false ) ) {
				$return = false;
			}
		}
		
		return $return;
	}
	
	function shortcode( $atts = null, $content = null ) {
		$return = '';
		$transient_name = 'mastodon_status_'; 
		$debug = '';
		
		/**
		 * NOTE: Insecure - use default attributes + @function wp_parse_args or @function shortcode_atts (do essentially the same)
		 */

		$default_atts = array(
			'url' => '', // backward compatiblity (avoid having to increase the major version number)
			'container_class' => 'mastodon-embed',
			'width' => 700,
			'height' => 200,
			'css' => 'overflow: hidden',
			'cache_timeout' => 24 * 60 * 60, // in seconds; defaults to 1 day
			'no_iframe' => 0, // workaround for localhost / testing purposes
			'disable_iframe' => 0, // alias
			'disable_font_awesome' => 0,
			'force_scheme' => '',
			'no_fa' => 0,
			'flush' => 0, // intentionally flush the cache
			/*'center' => 1, // picks out only the main toot out of a conversation (entry-center class)*/
			'no_center' => 0, // disable "centering" function
			'enable_debug' => 0, // specific debug parameter
		);
		
		$atts = shortcode_atts( $default_atts, $atts );
		
		extract( $atts, EXTR_SKIP );
		
		/**
		 * NOTE: @function empty is much quicker and more reliable; aside of that, usage of an url attribute makes no sense. Just drop it in the content, done! 
		 */
	
		if( !empty( $content ) ) {
			$url = trim( strip_tags( $content ) );
		}
		
		/**
		 * NOTE: Sanitize URL first; if nothing is left .. dont do anything ;) Also: Backward compat. for original mastodon-embed.
		 */
		
		if( !empty( $url ) ) {
			$url = esc_url( $url );
		}
			 
		if( !empty( $url ) ) {
			$url_hash = md5( $url ); // we dont need no "safe" encryption, just a quick way to cache stuff!
			
			if( defined( '__MASTODON_EMBED_CACHETIME' ) && empty( $cache_timeout ) ) {
				$cache_timeout = intval( __MASTODON_EMBED_CACHETIME );
			}
			
			$args = array(
				'sslverify' => false,
			); 
			
			if( empty( $flush ) ) {
				$cache_response = get_transient( $transient_name . $url_hash );
			}
			
			if( empty( $cache_response ) ) {
				$response = wp_remote_get( $url, $args );
				
				/**
				 * TODO: Improve caching; maybe add the content to the cache if its a direct embed ...
				 */
				
				set_transient( $transient_name . $url_hash, $response, $cache_timeout ); //cache curl response (we only need one response to get atom url)
				
			} else {
				$response = $cache_response;
			}
			
			$http_code = wp_remote_retrieve_response_code($response);
			$body = wp_remote_retrieve_body( $response );
			
			if( !empty( $no_iframe ) || !empty( $disable_iframe ) ) {
				if( !empty( $disable_iframe ) ) {
					$no_iframe = true;
				}
			
			}
			
			/**
			 * NOTE: Switch case is so much easier .. not to mention a proper $return.
			 */
			
			switch( $http_code ) {
				case 404:
					$container_class .= ' resource-not-found';
					$return = '<strong>Nothing found.</strong>';
					break;
				case 301:
					$container_class .= ' resource-access-denied';
					$return = '<strong>Access denied.</strong>';
					break;
				default:
					/**
					 * NOTE: using Simple HTML DOM instead of XPath
					 */
					
					$dom = new simple_html_dom();
					$dom->load( $body );
					
				
					
					
					if( empty( $no_iframe ) ) { // use iframe
						$atom_url = $dom->find( "link[type='application/atom+xml']", 0);
						
						
						//$embedUrl = str_replace(".atom", "/embed", $atomUrl[0]->getAttribute("href"));
						$embed_url = str_replace('.atom', '/embed', $atom_url->href );
						
						if( !empty( $force_scheme ) && in_array( $force_scheme, array( 'http', 'https' ), true ) != false ) {
							
							$host_scheme = parse_url( $embed_url, PHP_URL_SCHEME );
							
							if( $host_scheme != $force_scheme ) {
								$embed_url = str_replace( $host_scheme, $force_scheme, $embed_url );
							}
						}
						
						
						$return = '<iframe src="' . $embed_url . '" style="'.$css.'" frameborder="0" width="' . $width . '" height="' . $height . '" scrolling="no"></iframe>';
						
					} else { // use direct embed
						$embed_content = $dom->find( '.activity-stream', 0 );
							
						if( !empty( $embed_content ) ) {
							//if( $this->is_empty_attr( $params['center'] ) == false ) {
							if( empty( $no_center ) ) {
								$centered_content = $embed_content->find( '.entry-center', 0 );
								
								if( !empty( $centered_content ) ) {	
									$_embed_content = $embed_content; // backup original
									
									// fetch complete class to wrap the centered content into (ie. avoids breaking the CSS)
									$strCenteredWrap = '<div class="' . $embed_content->class . '">%s</div>';
									
									$embed_content = $centered_content; // replace it with the "focused" toot
								}
							}
							
							wp_enqueue_style( $this->pluginPrefix . 'content' );
							
							if( empty( $disable_font_awesome ) && empty( $no_fa ) ) {
								wp_enqueue_style( $this->pluginPrefix . 'font-awesome' );
							}
						}
						
						
						// add remote url to image sources
						if( strpos( $embed_content->outertext, 'src=' ) !== false ) { // find images
							// get host url
							//$host_scheme = parse_url( $url, PHP_URL_SCHEME );
							$host_url = parse_url( $url, PHP_URL_HOST );
							
							if( !empty( $host_url ) ) {

								foreach( $embed_content->find( 'img' ) as $image ) {
									/**
									 * NOTE: Explicitely check for pre-existing URL scheme - to avoid display issues.
									 */
									
									if( strpos( $image->src, 'http://' ) === false && strpos( $image->src, 'https://' ) === false ) {
										$image->src = esc_url( '//' .$host_url . $image->src );
									}
									
								}
							}
						}
						
						$return = $embed_content->outertext;
						
						//$debug .= "\n" . print_r( array( 'embed_content' => $embed_content->outertext ), true ) . "\n";
					}
					
					
					break;
			}
		}
		
		if( $this->is_debug() != false || !empty( $enable_debug ) ) {
			$debug = "\n\nDebug:\n" . $debug . print_r( array( 
				'content' => $content,
				'url' => $url,
				'embed_url' => $embed_url,
				'attributes' => $atts,
				'response_code' => $http_code,
				'response_body' => $body,
			), true ) . "\n";
		}
		
		/**
		 * NOTE: Add notification if no content was found or something else crapped up
		 */
		if( empty( $return ) ) {
			$return = '<!-- mastodon-embed: something went wrong! ' . $debug . ' -->';
			$error = true;
		}
		
		/**
		 * NOTE: Wrap return code with container div
		 */
	 
		if( !empty( $return ) && empty( $error ) ) { // avoids adding the debug data twice!
			$strWrap = '<div class="' . $container_class . '">%s</div>';
			
			if( !empty( $strCenteredWrap ) ) {
				$strWrap = str_replace( '%s', $strCenteredWrap, $strWrap ); // wrap centered toot into original div tag to avoid breaking the supplied CSS
			}
			
			/*
			if( !empty( $no_iframe ) && ( !empty( $height) || !empty( $width ) ) {
				
			}*/
			
			$return = sprintf( $strWrap, $return );
			
			// append debug data (if available)
			if( !empty( $debug ) ) {
				$return .= "\n<!-- mastodon embed: $debug -->\n\n";
			}
		}
	
		return $return;
	}
	
	/**
	 * Own empty() implementation, because shortcode attributes might be interpreted as strings and thus conversion to integer might not work out properly
	 */
	 
	function is_empty_attr( $data, $empty_strings = array('null', 'false', '0' ) ) {
		$return = true;
		
		if( is_string( $data ) && $data != '' ) {
			if( in_array( strtolower( $data ), $empty_strings, true ) != false ) {
				$return = false;
			}
		}
		
		return $return;
	}
	
	/**
	 * NOTE: Added for possible future enhancements, including storing already parsed toots in a meta field (= temporary / cache)
	 */
	
	function get_post_id( $strict_mode = false ) {
		global $post;
		$return = 0;
		
		if( !empty( $strict_mode ) ) {
			$current_post = sanitize_post( $GLOBALS['wp_the_query']->get_queried_object() );
  
			if( !empty( $current_post) && !empty( $current_post->ID ) ) {
				$return = $current_post->ID;
			}
		} else {
		
			if( !empty( $post ) && isset( $post->ID ) ) {
				$return = $post->ID;
			}
		}
		return $return;
	}
	
}

// init plugin
add_action( 'plugins_loaded', array( '__mastodon_embed_plugin', 'get_instance' ) );
