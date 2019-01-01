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
    private static $env;
    
    public static function init() {

        if ( !isset( self::$instance ) && !( self::$instance instanceof CommonToolkit ) ) {

            self::$instance = new CommonToolkit;

            // Define version constant
            if ( !defined( __CLASS__ . '\VERSION' ) ) define( __CLASS__ . '\VERSION', self::$version );

            // Set environment
            if( defined( 'WP_ENV' ) && WP_ENV ) {
                self::$env = strtolower( WP_ENV );
            } else {
                self::$env = 'production';
            }

            // Define environment variable
            if( !getenv( 'WP_ENV' ) ) putenv( 'WP_ENV=' . self::$env );

            // Disable emoji support
            if( defined( 'CTK_DISABLE_EMOJI' ) && CTK_DISABLE_EMOJI ) add_action( 'init', array( __CLASS__, 'disable_emojicons' ) );

            // Change admin bar color
            if( defined( 'CTK_ADMIN_BAR_COLOR' ) && CTK_ADMIN_BAR_COLOR ) {
                add_action( 'wp_head', array( __CLASS__, 'change_admin_bar_color' ) );
                add_action( 'admin_head', array( __CLASS__, 'change_admin_bar_color' ) );
            }  

            // Defer/Async Scripts
            if( !defined( 'CTK_DISABLE_SCRIPT_ATTRIBUTES' ) || !CTK_DISABLE_SCRIPT_ATTRIBUTES ) {
                add_filter( 'script_loader_tag', array( __CLASS__, 'defer_async_scripts' ), 10, 3 );
            }
            
        }

        return self::$instance;

    }

    /*
     * Remove Emoji code in page header.
     *    Usage: define( 'CTK_DISABLE_EMOJI', true );
     * 
     * @since 1.0.0
     */
    public function disable_emojicons() {

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
     *    Usage: define( 'CTK_ADMIN_BAR_COLOR', '#336699' );
     * 
     * @since 1.0.0
     */
    public function change_admin_bar_color() {

        printf( '<style type="text/css">#wpadminbar { background: %s; ?> !important; }</style>', CTK_ADMIN_BAR_COLOR );

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

}

CommonToolkit::init();