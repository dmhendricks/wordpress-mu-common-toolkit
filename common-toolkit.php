<?php
/**
 * Plugin Name:     Common Toolkit
 * Plugin URI:      https://github.com/dmhendricks/wordpress-mu-common-toolkit/
 * Description:     A must use (MU) plugin for WordPress that contains helper functions and snippets.
 * Version:         0.8.0
 * Author:          Daniel M. Hendricks
 * Author URI:      https://www.danhendricks.com/
 */
namespace MU_Plugins;

class CommonToolkit {

    private static $instance;
    private static $version = '0.8.0';
    private static $cache = [ 'key' => 'config_registry', 'group' => 'common_toolkit' ];
    protected static $config = [];
    protected static $environment = [];
    
    final public static function init() {

        if ( !isset( self::$instance ) && !( self::$instance instanceof CommonToolkit ) ) {

            self::$instance = new CommonToolkit;

            // Define version constant
            if ( !defined( __CLASS__ . '\VERSION' ) ) define( __CLASS__ . '\VERSION', self::$version );

            // Get configuration registry
            if( defined( 'CTK_CONFIG' ) ) {
                if( is_array( CTK_CONFIG ) ) {
                    self::$config['common_toolkit'] = CTK_CONFIG;
                } else if( is_string( CTK_CONFIG ) ) {
                    self::$config = self::get_cache_object( $cache[ 'key' ], function() {
                        return @json_decode( file_get_contents( realpath( ABSPATH . CTK_CONFIG ) ), true ) ?: [];
                    });
                }
            }

            // Set defaults
            self::$config['common_toolkit'] = self::set_default_atts( [
                'environment' => 'production',
                'environment_constant' => 'WP_ENV',
                'environment_production' => 'production',
                'admin_bar_color' => null,
                'disable_emojis' => false,
                'disable_search' => false,
                'disable_updates' => false,
                'disable_xmlrpc' => false,
                'feed_links' => true,
                'heartbeat' => null,
                'meta_generator' => true,
                'script_attributes' => false,
                'shortcodes' => false,
                'windows_live_writer' => true
            ], self::$config['common_toolkit'] );

            // Define environment
            switch( true ) {
                case defined( self::get_config( 'common_toolkit/environment_constant' ) ):
                    self::$config['common_toolkit']['environment'] = constant( self::get_config( 'common_toolkit/environment_constant' ) );
                    putenv( sprintf( '%s=%s', self::get_config( 'common_toolkit/environment_constant' ), self::get_config( 'common_toolkit/environment' ) ) );
                    break;
                case !empty( self::get_config( 'common_toolkit/environment' ) ):
                    putenv( sprintf( '%s=%s', self::get_config( 'common_toolkit/environment_constant' ) ?: 'WP_ENV', self::get_config( 'common_toolkit/environment' ) ) );
                    break;
                default:
                    putenv( 'WP_ENV=production' );
            }

            // Set variables for environment filter
            self::$environment = [
                'environment' => self::get_config( 'common_toolkit/environment' ),
                'is_production' => defined( self::get_config( 'common_toolkit/environment_constant' ) ) ? self::get_config( 'common_toolkit/environment_production' ) == getenv( self::get_config( 'common_toolkit/environment_constant' ) ) : true
            ];

            // Remove WordPress core, plugin and/or theme update notices
            if( $disable_updates = self::get_config( 'common_toolkit/disable_updates' ) ) {
                if( $disable_updates === true || in_array( 'core', (array) $disable_updates ) )
                    add_filter( 'pre_site_transient_update_core', array( self::$instance, 'disable_updates' ) );
                if( $disable_updates === true || in_array( 'plugin', (array) $disable_updates ) )
                    add_filter( 'pre_site_transient_update_plugins', array( self::$instance, 'disable_updates' ) );
                if( $disable_updates === true || in_array( 'theme', (array) $disable_updates ) )
                    add_filter( 'pre_site_transient_update_themes', array( self::$instance, 'disable_updates' ) );
            }
            
            // Modify or disable WordPress heartbeat
            if( self::get_config( 'common_toolkit/heartbeat' ) === false ) { // Disable heartbeat
                add_action( 'init', function() { wp_deregister_script( 'heartbeat' ); }, 1 );
            } else if( intval( self::get_config( 'common_toolkit/heartbeat' ) ) ) { // Modify heartbeat
                add_action( 'heartbeat_settings', array( self::$instance, 'modify_heartbeat' ) );
            }

            // Disable emoji support
            if( self::get_config( 'common_toolkit/disable_emojis' ) ) add_action( 'init', array( self::$instance, 'disable_emojis' ) );

            // Disable search
            if( self::get_config( 'common_toolkit/disable_search' ) ) {
                add_action( 'parse_query', array( self::$instance, 'disable_search' ) );
                add_action( 'get_search_form', '__return_null' );
            }

            // Change admin bar color
            if( self::get_config( 'common_toolkit/admin_bar_color' ) ) {
                add_action( 'wp_head', array( self::$instance, 'change_admin_bar_color' ) );
                add_action( 'admin_head', array( self::$instance, 'change_admin_bar_color' ) );
            }

            // Add custom shortcodes
            if( self::get_config( 'common_toolkit/shortcodes' ) ) {
                if( !shortcode_exists( 'get_datetime' ) ) add_shortcode( 'get_datetime', array( self::$instance, 'shortcode_get_datetime' ) );
            }

            // Disable XML-RPC & RSD
            if( self::get_config( 'common_toolkit/disable_xmlrpc' ) ) {
                add_filter( 'xmlrpc_enabled', '__return_false' );
                remove_action( 'wp_head', 'rsd_link' );
            }

            // Remove Windows Live Writer tag
            if( !self::get_config( 'common_toolkit/windows_live_writer' ) ) remove_action( 'wp_head', 'wlwmanifest_link' );


            // Remove or modify meta generator tags in page head and RSS feeds
            if( self::get_config( 'common_toolkit/meta_generator' ) === false ) {
                remove_action( 'wp_head', 'wp_generator' );
            }
            add_filter( 'the_generator', array( self::$instance, 'modify_meta_generator_tags' ), 10, 2 );

            // Remove RSS feed links
            if( !self::get_config( 'common_toolkit/feed_links' ) ) {
                remove_action( 'wp_head', 'feed_links', 2 );
                remove_action( 'wp_head', 'feed_links_extra', 3 );
            }

            // Defer/Async Scripts
            if( self::get_config( 'common_toolkit/script_attributes' ) ) {
                add_filter( 'script_loader_tag', array( self::$instance, 'defer_async_scripts' ), 10, 3 );
            }

            // Add filter to retrieve configuration values
            add_filter( 'ctk_config', array( self::$instance, 'ctk_config_filter' ) );

            // Add filter to retrieve environment
            add_filter( 'ctk_environment', array( self::$instance, 'ctk_environment_filter' ) );

            // Add action hook during init phase
            add_action( 'init', function() {
                do_action( 'common_toolkit_loaded' );
            });

        }

        return self::$instance;

    }

    /**
     * Get configuration variable.
     *    Example usage: echo apply_filter( 'ctk_config', 'common_toolkit/meta_generator' );
     * 
     * @param string $key Configuration variable path to retrieve
     * @param mixed $default The default value to return if $key is not found
     * @since 0.7.0
     * @see https://github.com/dmhendricks/wordpress-toolkit/blob/master/core/ConfigRegistry.php
     */
    public static function get_config( $key = null, $default = null ) {

        // If key not specified, return entire registry
        if ( !$key ) {
            return self::$config;
        }

        // Else return $key value or null if doesn't exist
        $value = self::$config;
        foreach( explode('/', $key ) as $k ) {
            if ( !isset( $value[$k] ) ) {
                return $default;
            }
            $value = &$value[$k];
        }
        return $value;

    }

    /**
     * Filter to retrieve configuration values
     *    Usage: echo apply_filters( 'ctk_config', 'disable_xmlrpc' ); // Echos value of 'disable_xmlrpc'
     *           var_dump( apply_filters( 'ctk_config', null ) ); // Displays all config variables
     *
     * @since 0.8.0
     */
    public static function ctk_config_filter( $key = null ) {

        switch( true ) {
            case !$key:
                return self::get_config();
            case self::get_config( $key ) !== null:
                return self::get_config( $key );
            default:
                return null;
        }
        
    }

    /**
     * Retrieve current environment information
     *    Usage: echo apply_filters( 'ctk_environment', null ); // Echos current environment string
     *           var_dump( apply_filters( 'ctk_environment', 'is_production' ) ); // Returns true if in production mode
     *
     * @since 0.8.0
     */
    public static function ctk_environment_filter( $key = null ) {

        if( empty( $key ) ) {
            return self::$environment['environment'];
        } else if( isset( self::$environment[$key] ) ) {
            return self::$environment[$key];
        }

        return null;
        
    }

    /**
     * Remove Emoji code in page header.
     *    Usage: define( 'CTK_CONFIG', [ 'disable_emojis' => true ] );
     * 
     * @since 0.7.0
     */
    public function disable_emojis() {

        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

        add_filter( 'tiny_mce_plugins', function( $plugins) {
            return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
        });

    }

    /**
     * Remove WordPress core, plugin and/or theme update notices
     *    Usage: define( 'CTK_CONFIG', [ 'disable_updates' => [ 'core', 'plugin', 'theme' ] ] );
     * 
     * @since 0.8.0
     */
    public function disable_updates() {

        global $wp_version;
        return (object) array( 'last_checked' => time(), 'version_checked' => $wp_version );
    
    }

    /**
     * Disables WordPress site search and return 404
     * 
     * @since 0.8.0
     */
    public function disable_search( $query, $error = true ) {

        if ( is_search() ) {

            $query->is_search = false;
            $query->query_vars['s'] = false;
            $query->query['s'] = false;
            
            if ( $error == true ) $query->is_404 = true;

        }

    }

    /**
     * Set a different admin bar color color. Useful for differentiating among environnments.
     *    Usage: define( 'CTK_CONFIG', [ 'admin_bar_color' => '#336699' ] );
     * 
     * @since 0.7.0
     */
    public function change_admin_bar_color() {

        printf( '<style type="text/css">#wpadminbar { background: %s; ?> !important; }</style>', CTK_CONFIG['admin_bar_color'] );

    }

    /**
     * Quick defer or async loading of scripts via wp_enqueue_script(). Supports other custom attributes.
     *    Usage: wp_enqueue_script( 'script-handle-async-example', get_template_directory_uri() . '/assets/js/script.js#async' );
     *           wp_enqueue_script( 'script-handle-defer-example', get_template_directory_uri() . '/assets/js/script.js#defer' );
     *           wp_enqueue_script( 'script-custom-attributes', get_template_directory_uri() . '/assets/js/script.js?custom_attribute[]=custom-element|amp-ad' );
     * 
     * @since 0.7.0
     * @see http://php.net/manual/en/domdocument.loadhtml.php
     * @see http://php.net/manual/en/domelement.setattribute.php
     */
    public function defer_async_scripts( $tag, $handle, $src ) {

        // Return if no modification needed
        if( !strpos( $src, '#async' ) && !strpos( $src, '#defer' ) && !strpos( $src, 'custom_attribute' ) ) return $tag;

        // Parse script src attribute
        $uri = html_entity_decode( urldecode( $src ) );
        $parsed_url = parse_url( $uri );

        // Create DOM element
        $dom = new \DomDocument();
        $dom->loadHTML( $tag );
        $link = $dom->getElementsByTagName( 'script' );

        // Verify element type
        if( !$link instanceof \DOMNodeList || !$link->length ) return $tag;

        // Remove extra DOM tags
        $dom = self::strip_extra_dom_elements( $dom );

        // Add async/defer attribute
        if( isset( $parsed_url['fragment'] ) && $parsed_url['fragment'] == 'defer' || $parsed_url['fragment'] == 'async' ) {
            $link->item(0)->setAttribute( $parsed_url['fragment'], $parsed_url['fragment'] );
            unset( $parsed_url['fragment'] );
        }

        // Custom attributes
        if( strpos( $parsed_url['query'], 'custom_attribute[' ) !== false ) {
            $query_parts = explode( '&', $parsed_url['query'] );
            $new_query = [];
            foreach( $query_parts as $pair ) {
                if( strpos( $pair, 'custom_attribute[' ) === false ) $new_query[] = $pair;
                $pair = explode( '|', substr( $pair, strpos( $pair, '=' )+1 ) );
                if( sizeof( $pair ) > 1 ) $link->item(0)->setAttribute( $pair[0], $pair[1] );
            }
            $parsed_url['query'] = implode( '&', $new_query );
        }

        // Replace script src attribute
        $link->item(0)->setAttribute( 'src', self::build_url( $parsed_url ) );

        // Return new tag element
        return $dom->saveHTML();
        
    }

    /**
     * Build URL from array created with parse_url()
     *    Usage: $parse_uri = parse_url( 'https://example.com/?hello=world#hash );
     *           $uri = \MU_Plugins\CommonToolkit::build_url( $parse_uri );
     * 
     * @since 0.7.0
     * @see https://stackoverflow.com/a/35207936
     */
    public static function build_url( array $parts ) {

        return ( isset( $parts['scheme'] ) ? "{$parts['scheme']}:" : '' ) . 
            ( ( isset( $parts['user'] ) || isset( $parts['host'] ) ) ? '//' : '' ) . 
            ( isset( $parts['user'] ) ? "{$parts['user']}" : '' ) . 
            ( isset($parts['pass'] ) ? ":{$parts['pass']}" : '' ) . 
            ( isset( $parts['user'] ) ? '@' : '' ) . 
            ( isset($parts['host']) ? "{$parts['host']}" : '' ) . 
            ( isset($parts['port']) ? ":{$parts['port']}" : '' ) . 
            ( isset($parts['path']) ? "{$parts['path']}" : '' ) . 
            ( isset($parts['query']) ? "?{$parts['query']}" : '' ) . 
            ( isset($parts['fragment']) ? "#{$parts['fragment']}" : '' );

    }

    /**
     * Combines arrays and fill in defaults as needed. Example usage:
     * 
     *    $person = [ 'name' => 'John', 'age' => 29 ];
     *    $human = \MU_Plugins\CommonToolkit::set_default_atts( [
     *       'name' => 'World',
     *       'human' => true,
     *       'location' => 'USA',
     *       'age' => null
     *    ], $person );
     *    print_r( $human ); // Result: [ 'name' => 'John', 'human' => true, 'location' => 'USA', 'age' => 29 ];
     *
     * @param array  $pairs     Entire list of supported attributes and their defaults.
     * @param array  $atts      User defined attributes in shortcode tag.
     * @return array Combined and filtered attribute list.
     * @since 0.7.0
     */
    public static function set_default_atts( $pairs, $atts ) {

        $atts = (array) $atts;
        $result = [];

        foreach ($pairs as $name => $default) {
            if ( array_key_exists($name, $atts) ) {
                $result[$name] = $atts[$name];
            } else {
                $result[$name] = $default;
            }
        }

        return $result;

    }

    /**
     * Modify the WordPress heartbeat
     *
     * @since 0.8.0
     * @see https://codex.wordpress.org/Function_Reference/wp_heartbeat_settings
     */
    public function modify_heartbeat( $settings ) {

        $heartbeat = intval( self::get_config( 'common_toolkit/heartbeat' ) );
        if( !$heartbeat ) return $settings;

        $settings['interval'] = $heartbeat;
        return $settings;

    }

    /**
     * Remove or modify meta generator tag.
     *
     * @since 0.7.0
     */
    public function modify_meta_generator_tags( $current, $type ) {

        $meta_generator = self::get_config( 'common_toolkit/meta_generator' );
        switch( true ) {
            case $meta_generator === true:
                return $current;
            case is_string( $meta_generator ):
                if( strpos( $current, '<generator>' ) !== false ) {
                    return sprintf( '<generator>%s</generator>', $meta_generator );
                } else {
                    return sprintf( '<meta name="generator" content="%s" />', $meta_generator );
                }
            default:
                return '';
        }

    }

    /**
     * Output a formatted date in WordPress configured timezone. Defaults to curreent
     * date/time in format configured in WP Admin.
     *     Usage: Current date/time: [get_datetime]
     *            Copyright &copy;[get_datetime format="Y"] Your Company
     *
     * @since 0.7.0
     * @see https://php.net/date
     */
    public function shortcode_get_datetime( $atts ) {

        $atts = shortcode_atts( [
            'format' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' )
        ], $atts, 'get_datetime' );
      
        return current_time( $atts['format'] );
    }

    /**
     * Remove extra HTML tags added by DomDocument
     *
     * @since 0.7.0
     */
    private function strip_extra_dom_elements( $element ) {

        $element->removeChild( $element->firstChild );
        $element->replaceChild( $element->firstChild->firstChild->firstChild, $element->firstChild );
        return $element;

    }

    /**
     * Get/set cache object
     *
     * @param string $key The name of the cache key to set/retrieve
     * @param function $callback The callback function that return the uncached value
     * @return mixed
     * @since 0.8.0
     */
    public function get_cache_object( $key, $callback ) {

        $cache_expire = defined( 'CTK_CACHE_EXPIRE' ) && is_int( CTK_CACHE_EXPIRE ) ? intval( CTK_CACHE_EXPIRE ) : false;
        if( !is_int( $cache_expire ) ) return $callback();

        $result = unserialize( wp_cache_get( $key , $cache[ 'group' ], false, $cache_hit ) );

        if( !$cache_hit ) {

            $result = $callback();
            wp_cache_set( $key, serialize( $result ), $cache[ 'group' ], $cache_expire );

        } else {

            if( is_string( $result ) && is_numeric( $result ) ) $result = intval( $result ) ? (int) $result : (float) $result;

        }

        return $result;

    }

    /**
     * Flush the config registry cache
     * 
     * @since 0.8.0
     */
    public function delete_config_cache() {

        wp_cache_delete( $cache[ 'group' ], $cache[ 'group' ] );
        
    }

    /**
     * Magic method to return config as JSON string.
     *
     * @since 0.7.0
     */
    public function __toString() {
        return json_encode( self::get_config() );
    }

}

CommonToolkit::init();