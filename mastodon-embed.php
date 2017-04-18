<?php
/**
 * Plugin Name: Mastodon Embed Improved
 * Plugin URI: http://f2w.de/mastodon-embed
 * Description: A plugin to embed Mastodon statuses. Complete rewrite of <a href="https://github.com/DavidLibeau/mastodon-tools">Mastodon embed</a> by David Libeau. Tested up to WP 4.8-nightly
 * Version: 2.1
 * Author: Fabian Wolf
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
 */

if( !class_exists( 'simple_html_dom' ) ) {
	require_once( plugin_dir_path(__FILE__) . 'simple_html_dom.php' );
}

class __mastodon_embed_plugin {
	public 	$pluginName = 'Mastodon Embed Improved',
			$pluginPrefix = 'mastodon_embed_';
	
	public $shortcode_name = 'mastodon';
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
		if( ( defined( 'WP_DEBUG' ) && WP_DEBUG != false) || current_user_can( 'manage_options' ) != false ) {
			$return = true;
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
			'cache_timeout' => 0, // in seconds
			'no_iframe' => 0, // workaround for localhost / testing purposes
			'disable_font_awesome' => 0,
			'no_fa' => 0,
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
			
			$cache_response = get_transient( $transient_name . $url_hash );
			
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
						
						$return = '<iframe src="' . $embed_url . '" style="'.$css.'" frameborder="0" width="' . $width . '" height="' . $height . '" scrolling="no"></iframe>';
						
					} else { // use direct embed
						$embed_content = $dom->find( '.activity-stream', 0 );
						
						if( !empty( $embed_content ) ) {
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
									$image->src =  esc_url( '//' .$host_url . $image->src );
								}
							}
						}
						
						$return = $embed_content->outertext;
						
						//$debug .= "\n" . print_r( array( 'embed_content' => $embed_content->outertext ), true ) . "\n";
					}
					
					
					break;
			}
		}
		
		if( $this->is_debug() != false ) {
			$debug = "\n\nDebug:\n" . $debug . print_r( array( 
				'content' => $content,
				'url' => $url,
				'attributes' => $atts,
				'response_code' => $httpCode,
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
	
		 
		if( !empty( $return ) && empty( $error ) ) {
			$strWrap = '<div class="' . $container_class . '">%s</div>';
			
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
}

// init plugin
add_action( 'plugins_loaded', array( '__mastodon_embed_plugin', 'get_instance' ) );
