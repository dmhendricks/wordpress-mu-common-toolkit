# Changelog

### 1.1.0

- Fixed: Config JSON loading now safely checks file existence and readability before parsing; removed `@` error suppression
- Fixed: `disable_updates()` now includes `updates` array to properly suppress update nag
- Fixed: `delete_config_cache()` was using wrong cache key
- Fixed: Removed manual `serialize`/`unserialize` around `wp_cache_get`/`wp_cache_set`
- Fixed: `change_admin_bar_color()` had broken PHP syntax; now uses `get_config()` and `esc_attr()`
- Fixed: `block_site_health()` now uses `wp_die()` instead of raw `http_response_code()`/`die()`
- Fixed: `strip_extra_dom_elements()` declared as instance method but called statically; changed to `static`
- Changed: `admin_bar_howdy` filter priority changed from 25 to 9992

### 1.0.0

- Added: Option to disable PHP Update Notice dashboard widget
- Added: Option to disable Site Health
- Added: Support for script preload attribute

### 0.9.0

- Fixed: Firing sequence of `common_toolkit_loaded` hook
- Fixed: Non-static method should not be called statically
- Added: `ctk_environment` filter
- Added: Ability to change or remove Howdy from admin bar
- Added: Ability to change/remove login errors
- Added: Ability to cache JSON config file
- Added: Ability to disable WordPress core, plugin and/or theme updates
- Added: Ability to modify or disable WordPress heartbeat
- Added: Ability to disable WordPress search
- Added: Ability to define custom environment constant

### 0.8.0 - January 27, 2019

- Initial release