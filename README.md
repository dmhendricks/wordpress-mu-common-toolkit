[![Author](https://img.shields.io/badge/author-Daniel%20M.%20Hendricks-lightgrey.svg?colorB=9900cc&style=flat-square)](https://www.danhendricks.com/?utm_source=github.com&utm_medium=campaign&utm_content=button&utm_campaign=wordpress-mu-common-toolkit)
[![GitHub License](https://img.shields.io/badge/license-GPLv2-yellow.svg?style=flat-square)](https://raw.githubusercontent.com/dmhendricks/wordpress-mu-common-toolkit/master/LICENSE)
[![Get Flywheel](https://img.shields.io/badge/hosting-Flywheel-green.svg?style=flat-square&label=compatible&colorB=AE2A21)](https://share.getf.ly/e25g6k?utm_source=github.com&utm_medium=campaign&utm_content=button&utm_campaign=dmhendricks%2Fwordpress-mu-common-toolkit)
[![Twitter](https://img.shields.io/twitter/url/https/github.com/dmhendricks/wordpress-mu-common-toolkit.svg?style=social)](https://twitter.com/danielhendricks)

# WordPress Common Toolkit MU Plugin

A simple [MU plugin](https://codex.wordpress.org/Must_Use_Plugins) for WordPress that adds functionality that I use on web site projects, including a [configuration registry](#getting-configuration-values).

- [Installation](#installation)
- [Configuration](#configuration)
- [Features](#features)
- [Environment Filter](#environment-filter)
- [Action Hook](#action-hook)
- [Shortcodes](#shortcodes)

## Installation

Simply copy the `common-toolkit.php` file to your `wp-content/mu-plugins` directory (create one if it does not exist).

### Requirements

- **PHP 5.4+** (via JSON config file) and **PHP 7.x** (via array _or_ JSON file)
- WordPress 4.7 or higher

## Configuration

All variables are optional.

| **Variable**              | **Description**                                                                                                          | **Type**          | **Default**   |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------|-------------------|---------------|
| `environment`             | Environment of current instance (ex: 'production', 'development', 'staging')                                             | string            | "production"  |
| `environment_constant`    | Constant used to determine environment, environmental variable name for `getenv()`.                                      | string            | "WP_ENV"      |
| `environment_production`  | The label used to match if production environment.                                                                       | string            | "production"  |
| `admin_bar_color`         | Change admin bar color in current environment                                                                            | string            | _null_        |
| `disable_emojis`          | Remove support for emojis                                                                                                | bool              | false         |
| `disable_search`          | Disable WordPress site search                                                                                            | bool              | false         |
| `disable_updates`         | Disable WordPress core, plugin and/or theme updates. Values: 'core', 'plugin', 'theme'; `true` for all                   | bool/string/array | false         |
| `disable_xmlrpc`          | Disable XML-RPC                                                                                                          | bool              | false         |
| `feed_links`              | Include RSS feed links in page head                                                                                      | bool              | true          |
| `heartbeat`               | Modify or disable the WordPress heartbeat. Set to integer to change, `false` to disable                                  | bool/int          | null          |
| `hide_login_errors`       | Replaces login errors with generic "Login failed" text rather than specific reason                                       | bool/string       | null          |
| `howdy_message`           | Change (string) or remove (false/null) Howdy message in WP admin bar                                                     | bool/string/null  | true          |
| `meta_generator`          | Enable or change meta generator tags in page head and RSS feeds                                                          | bool/string       | false         |
| `script_attributes`       | Enable support for [additional attributes](#add-attributes-to-enqueued-scripts) to script tags via wp_enqueue_script()   | bool              | flase         |
| `shortcodes`              | Enable custom [shortcodes](#shortcodes) created by this class                                                            | bool              | false         |
| `windows_live_writer`     | Enable [Windows Live Writer](https://is.gd/Q6KjEQ) support                                                               | bool              | true          |

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

### Caching JSON Config File

If your WordPress Instance has caching enabled, you can configure this plugin to cache the contents of your configuration JSON file with a constant in `wp-config.php`:

```php
define( 'CTK_CACHE_EXPIRE', 120 ); // In seconds
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

You can set your instance environment using the following methods (in order of precedence; defaults to "production" if not set using any of the following methods):

#### 1. Define a Constant in `wp-config.php`

```php
define( 'WP_ENV', 'staging' );
```

If you wish to use a different constant name, you can set the `environment_constant` in the config:

```php
define( 'MY_ENVIRONMENT', 'development' );
define( 'CTK_CONFIG', [ 'environment_constant' => 'MY_ENVIRONMENT' ] );
```

This will also change the name of the environmental variable used to retrieve the environment:

```php
echo getenv( 'MY_ENVIRONMENT' ); // Result: development
// ...or:
echo apply_filters( 'ctk_environment', null ); // Result: development
```

#### 2. Define `environment` Variable in Config

Setting:

```php
define( 'CTK_CONFIG', [ 'environment' => 'staging' ] );
```

Getting:

```php
echo getenv( 'WP_ENV' ); // Result: staging
// ...or:
echo apply_filters( 'ctk_environment', null ); // Result: staging
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

### Disable WordPress Core, Plugin and/or Theme Updates

You can disable any of the update notifications or specific. It accepts string, boolean or an array of values. Examples:

```php
// Disable all update notifications
define( 'CTK_CONFIG', [ 'disable_updates' => true ] ); // boolean

// Disable WordPress core and theme updates only (not plugins)
define( 'CTK_CONFIG', [ 'disable_updates' => [ 'core', 'theme' ] ] ); // array

// Disable only plugin updates updates
define( 'CTK_CONFIG', [ 'disable_updates' => 'plugin' ] ); // string
```

## Environment Filter

You can alternately retrieve the current environment using the `ctk_environment` filter:

```php
echo apply_filters( 'ctk_environment', null ); // 'production', 'staging', etc
```

You can also pass `is_production` to determine if we're currently in production more. It compares the value of your environment (defined above) with the value of `common_toolkit/environment_production` (which defaults to "production"). In this way, you can set your production label/string value to whatever you like.

Determining if in production mode using **defaults**:

```php
if( apply_filters( 'ctk_environment', 'is_production' ) ) {
   // Do something intended only for production
} else {
   // Do something else
}
```

As noted above, you can change the string comparison of what is considered production in config. For example, if you wanted to use "live" instead of "production":

```php
define( 'CTK_CONFIG', [ 'environment_production' => 'live' ] );

// Result: true
define( 'WP_ENV', 'live' ); // wp-config.php
var_dump( apply_filters( 'ctk_environment', 'is_production' ) );

// Result: false
define( 'WP_ENV', 'staging' ); // wp-config.php
var_dump( apply_filters( 'ctk_environment', 'is_production' ) );
```

This special filter value is provided solely for convenience. You may, of course, do a manual comparison:

```php
if( getenv( 'WP_ENV' ) == 'production' ) { // Replace variable with value of `environment_constant`, if set
   // Do something intended only for production
} else {
   // Do something else
}
```


## Action Hook

If you want to perform some logic only if this script is loaded, you can use the `common_toolkit_loaded` action hook (which executes during the [init](https://codex.wordpress.org/Plugin_API/Action_Reference#Actions_Run_During_a_Typical_Request) phase).

```php
add_action( 'common_toolkit_loaded', function() {
	// Do something if common toolkit is loaded ...
	var_dump( apply_filters( 'ctk_config', null ) );
});
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

[![Analytics](https://ga-beacon.appspot.com/UA-67333102-2/dmhendricks/wordpress-mu-common-toolkit?flat)](https://github.com/igrigorik/ga-beacon/?utm_source=github.com&utm_medium=referral&utm_content=button&utm_campaign=dmhendricks%2Fwordpress-mu-common-toolkit)
