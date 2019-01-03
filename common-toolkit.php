<?php
/**
 * Plugin Name:     Common Toolkit
 * Description:     A must use (MU) plugin for WordPress that contains helper functions and snippets.
 * Version:         1.0.0
 * Author:          Daniel M. Hendricks
 * Author URI:      https://www.danhendricks.com/
 * Original:        https://github.com/dmhendricks/wordpress-mu-common-toolkit/
 */

namespace MU_Plugins;

class CommonToolkit {

    private static $instance;
    private static $version = '1.0.0';
    protected static $config;
    
    public static function init() {

        if ( !isset( self::$instance ) && !( self::$instance instanceof CommonToolkit ) ) {

            self::$instance = new CommonToolkit;

            // Define version constant
            if ( !defined( __CLASS__ . '\VERSION' ) ) define( __CLASS__ . '\VERSION', self::$version );

            // Set configuration
            self::$config = self::set_default_atts( [
                'environment' => defined( 'WP_ENV' ) ? WP_ENV : 'production',
                'disable_emojis' => false,
                'admin_bar_color' => null,
                'script_attributes' => false,
                'shortcodes' => false,
                'disable_xmlrpc' => false,
                'meta_generator' => true,
                'windows_live_writer' => false,
                'feed_links' => true
            ], defined( 'CTK_CONFIG' ) && is_array( CTK_CONFIG ) ? CTK_CONFIG : [] );
            
            // Define environment variable
            putenv( 'WP_ENV=' . self::get_config( 'environment' ) );

            // Disable emoji support
            if( self::get_config( 'disable_emojis' ) ) add_action( 'init', array( __CLASS__, 'disable_emojis' ) );

            // Change admin bar color
            if( self::get_config( 'admin_bar_color' ) ) {
                add_action( 'wp_head', array( __CLASS__, 'change_admin_bar_color' ) );
                add_action( 'admin_head', array( __CLASS__, 'change_admin_bar_color' ) );
            }

            // Add custom shortcodes
            if( self::get_config( 'shortcodes' ) ) {
                if( !shortcode_exists( 'get_datetime' ) ) add_shortcode( 'get_datetime', array( __CLASS__, 'shortcode_get_datetime' ) );
            }

            // Disable XML-RPC & RSD
            if( self::get_config( 'disable_xmlrpc' ) ) {
                add_filter( 'xmlrpc_enabled', '__return_false' );
                remove_action( 'wp_head', 'rsd_link' );
            }

            // Remove Windows Live Writer tag
            if( !self::get_config( 'windows_live_writer' ) ) remove_action( 'wp_head', 'wlwmanifest_link' );


            // Remove or modify meta generator tags in page head and RSS feeds
            if( self::get_config( 'meta_generator' ) === false ) {
                remove_action( 'wp_head', 'wp_generator' );
            }
            add_filter( 'the_generator', array( __CLASS__, 'modify_meta_generator_tags' ), 10, 2 );

            // Remove RSS feed links
            if( !self::get_config( 'feed_links' ) ) {
                remove_action( 'wp_head', 'feed_links', 2 );
                remove_action( 'wp_head', 'feed_links_extra', 3 );
            }

            // Defer/Async Scripts
            if( !self::get_config( 'script_attributes' ) ) {
                add_filter( 'script_loader_tag', array( __CLASS__, 'defer_async_scripts' ), 10, 3 );
            }

        }

        return self::$instance;

    }

    /*
     * Get configuration variable.
     *    Usage: echo \MU_Plugins\CommonToolkit::get_config( 'environment' );
     * 
     * @since 1.0.0
     */
    public static function get_config( $key = null ) {

        switch( true ) {
            case !$key:
                return self::$config;
            case isset( self::$config[$key] ):
                return self::$config[$key];
            default:
                return null;
        }
        
    }

    /*
     * Remove Emoji code in page header.
     *    Usage: define( 'CTK_CONFIG', [ 'disable_emojis' => true ] );
     * 
     * @since 1.0.0
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

    /*
     * Set a different admin bar color color. Useful for differentiating among environnments.
     *    Usage: define( 'CTK_CONFIG', [ 'admin_bar_color' => '#336699' ] );
     * 
     * @since 1.0.0
     */
    public function change_admin_bar_color() {

        printf( '<style type="text/css">#wpadminbar { background: %s; ?> !important; }</style>', CTK_CONFIG['admin_bar_color'] );

    }

    /*
     * Quick defer or async loading of scripts via wp_enqueue_script(). Supports other custom attributes.
     *    Usage: wp_enqueue_script( 'script-handle-async-example', get_template_directory_uri() . '/assets/js/script.js#async' );
     *           wp_enqueue_script( 'script-handle-defer-example', get_template_directory_uri() . '/assets/js/script.js#defer' );
     *           wp_enqueue_script( 'script-custom-attributes', get_template_directory_uri() . '/assets/js/script.js?custom_attribute[]=custom-element|amp-ad' );
     * 
     * @since 1.0.0
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
        $dom->removeChild( $dom->firstChild );
        $dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild );

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

    /*
     * Build URL from array created with parse_url()
     *    Usage: $parse_uri = parse_url( 'https://example.com/?hello=world#hash );
     *           $uri = \MU_Plugins\CommonToolkit::build_url( $parse_uri );
     * 
     * @since 1.0.0
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
     * @since 1.0.0
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

    /*
     * Remove or modify meta generator tag.
     *
     * @since 1.0.0
     */
    public function modify_meta_generator_tags( $current, $type ) {

        $meta_generator = self::get_config( 'meta_generator' );
        switch( true ) {
            case $meta_generator === true:
                return $current;
            case is_string( $meta_generator ):
                return $meta_generator;
            default:
                return '';
        }

    }

    /*
     * Output a formatted date in WordPress configured timezone. Defaults to current year.
     *     Usage: Copyright &copy;[get_datetime] Your Company
     *            Current time: [get_datetime format="g:i A"]
     *
     * @since 1.0.0
     * @see https://php.net/date
     */
    public function shortcode_get_datetime( $atts ) {

        $atts = shortcode_atts( [
            'format' => 'Y'
        ], $atts, 'get_datetime' );
      
        return current_time( $atts['format'] );
    }
    
}

CommonToolkit::init();