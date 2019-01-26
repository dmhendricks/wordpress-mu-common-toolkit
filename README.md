[![Author](https://img.shields.io/badge/author-Daniel%20M.%20Hendricks-lightgrey.svg?colorB=9900cc&style=flat-square)](https://www.danhendricks.com/?utm_source=github.com&utm_medium=campaign&utm_content=button&utm_campaign=wordpress-mu-common-toolkit)
[![GitHub License](https://img.shields.io/badge/license-GPLv2-yellow.svg?style=flat-square)](https://raw.githubusercontent.com/dmhendricks/wordpress-mu-common-toolkit/master/LICENSE)
[![Get Flywheel](https://img.shields.io/badge/hosting-Flywheel-green.svg?style=flat-square&label=compatible&colorB=AE2A21)](https://share.getf.ly/e25g6k?utm_source=github.com&utm_medium=campaign&utm_content=button&utm_campaign=dmhendricks%2Fwordpress-mu-common-toolkit)
[![Twitter](https://img.shields.io/twitter/url/https/github.com/dmhendricks/wordpress-mu-common-toolkit.svg?style=social)](https://twitter.com/danielhendricks)

# WordPress Common Toolkit MU Plugin

A simple [MU plugin](https://codex.wordpress.org/Must_Use_Plugins) for WordPress that adds functionality that I use on web site projects, including a configuration registry.

## Installation

Simply copy the `common-toolkit.php` file to your `wp-content/mu-plugins` directory (create one if it does not exist).

## Requirements

- PHP ~5.6 (via JSON file) and PHP 7.x (via array or JSON file)
- WordPress 4.7 or higher

## Configuration

| **Variable**           | **Description**                                                                                                          | **Type**    | **Default**   |
|------------------------|--------------------------------------------------------------------------------------------------------------------------|-------------|---------------|
| `environment`          | Environment of current instance (ex: 'production', 'development', 'staging')                                             | string      | "production"  |
| `disable_emojis`       | Remove support for emojis                                                                                                | bool        | false         |
| `admin_bar_color`      | Change admin bar color in current environment                                                                            | string      | _null_        |
| `script_attributes`    | Enable support for [additional attributes](#add-attributes-to-enqueued-scripts) to script tags via wp_enqueue_script()   | bool        | flase         |
| `shortcodes`           | Enable custom [shortcodes](#shortcodes) created by this class                                                            | bool        | false         |
| `disable_xmlrpc`       | Disable XML-RPC                                                                                                          | bool        | false         |
| `meta_generator`       | Enable or change meta generator tags in page head and RSS feeds                                                          | bool/string | true          |
| `windows_live_writer`  | Enable [Windows Live Writer](https://is.gd/Q6KjEQ) support                                                               | bool        | true          |
| `feed_links`           | Include RSS feed links in page head                                                                                      | bool        | true          |

### Example

#### Via Configuration File (PHP 5.6 or higher)

This is the preferred method if you wish to avoid having a complex array in your `wp-config.php`:

```php
// Load configuration from a file in webroot. 
define( 'CTK_CONFIG', 'sample-config.json' );

// Load configuration from a file off of the parent directory of webroot
define( 'CTK_CONFIG', '../conf/sample-config.json' );
```

See [sample-config.json](https://github.com/dmhendricks/wordpress-mu-common-toolkit/blob/master/sample-config.json) for example.

#### Via Array (PHP 7 or higher)

Rather than using a JSON file for configuration, you can set `CTK_CONFIG` to an array of valyes in `wp-config.php`:

```php
define( 'CTK_CONFIG', [ 'disable_emojis' => true, 'admin_bar_color' => '#336699', 'script_attributes' => true, 'meta_generator' => 'Atari 2600' ] );
```

### Getting Configuration Values

You can use the `ctk_config` filter to retrieve values from the config registry (including custom). Using [sample-config.json](https://github.com/dmhendricks/wordpress-mu-common-toolkit/blob/master/sample-config.json) as an example:

```php
// Get meta generator value
$meta_generator = apply_filter( 'ctk_config', 'common_toolkit/meta_generator' );

// Get single custom variable
$ny_var = apply_filter( 'ctk_config', 'my_custom_variable' );

// Get an array of classic books
$classic_books = apply_filter( 'ctk_config', 'nested_example/books/classics' );

// Get entire config registry as associative array
$config = apply_filter( 'ctk_config', null );
```

You can add any variable you want to make available to your site's themes and plugins.

## Features

### WordPress Environment

Setting:

```php
define( 'CTK_CONFIG', [ 'environment' => 'staging' ] );
```

Getting:

```php
echo getenv( 'WP_ENV' ); // Result: staging
```

If not defined, "production" is returned.

### Add Attributes to Enqueued Scripts

Examples:

```php
wp_enqueue_script( 'script-async-example', get_template_directory_uri() . '/assets/js/script.js#async' );
wp_enqueue_script( 'script-defer-example', get_template_directory_uri() . '/assets/js/script.js#defer' );
wp_enqueue_script( 'script-custom-attributes', 'https://cdn.ampproject.org/v0/amp-audio-0.1.js?custom_attribute[]=custom-element|amp-audio#async' );
```

Result:

```html
<script async src="https://example.com/wp-content/themes/my-theme/assets/js/script.js?ver=5.0.0"></script>
<script defer src="https://example.com/wp-content/themes/my-theme/assets/js/script.js?ver=5.0.0"></script>
<script async custom-element="amp-audio" src="https://cdn.ampproject.org/v0/amp-audio-0.1.js?ver=5.0.0"></script>
```

### Change Admin Bar Color

Useful for distinguishing browser windows among different environments. Defined in `wp-config.php`:

```php
define( 'CTK_CONFIG', [ 'admin_bar_color' => '#336699' ] );
```

### Build URL Parsed With parsed_url()

This function reverses the result of [`parse_url()`](http://php.net/manual/en/function.parse-url.php):

```php
$parse_uri = parse_url( 'https://example.com/?hello=world#hash );
$parse_uri['fragment'] = 'newhash';
$uri = \MU_Plugins\CommonToolkit::build_url( $parse_uri );
```

## Shortcodes

### `[get_datetime]`

Returns a formatted date in WordPress configured timezone. Defaults to current date/time in MySQL format. Enabling:

```php
define( 'CTK_CONFIG', [ 'shortcodes' => true ] );
```

Usage:

```
Copyright &copy;[get_datetime format="Y"] Your Company
Current date/time: [get_datetime]
```

See PHP's [`date()`](https://php.net/date) function for formatting options.

## Future Plans

- ~~Add support for PHP 5.6~~
- ~~Add support for external config files & registry~~
- ~~Add filter for retrieving values from config~~
- Add check for new version releases
- Detect [Classic Editor](https://wordpress.org/plugins/classic-editor/)/[ClassicPress](https://www.classicpress.net/)
- Add ability to change favicons by environment
- Custom WP Admin footer
- Add function to test if WP Object Cache is enabled
- Add rewrite for custom image/script URLs/CDN support
- Hide login errors
- Disable update notifications: core, plugins, themes or all; Option to remove for all but admins
- Change excerpt length
- Remove `?ver=` from specific or all scripts
- Disable JSON REST API
- Enable TinyMCE lower toolbar
- Completely disable comments
- Inject standard Google Analytics script

[![Analytics](https://ga-beacon.appspot.com/UA-67333102-2/dmhendricks/wordpress-mu-common-toolkit?flat)](https://github.com/igrigorik/ga-beacon/?utm_source=github.com&utm_medium=referral&utm_content=button&utm_campaign=dmhendricks%2Fwordpress-mu-common-toolkit)
