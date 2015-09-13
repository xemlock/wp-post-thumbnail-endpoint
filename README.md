# WP Unified Post Thumbnail URL Plugin

This WordPress plugin provides a special URL dependent only on Post ID that, when visited, redirects to the [Post Thumbnail](https://codex.wordpress.org/Post_Thumbnails) (aka Featured Image) corresponding to the provided ID. This can particularily be useful when implementing client-side JavaScript features (in a theme or plugin) that want to display Post Thumbnail based only on Post ID, without sending additional AJAX request to retrieve the thumbnail's location.

## Installation

Place the Plugin directory to `wp-content/plugins` in your WordPress installation. Then go to [plugins administration screen](https://codex.wordpress.org/Administration_Screens#Plugins) and activate the Plugin.

You can also directly include the main Plugin file  `plugin.php` in your own plugin or theme. The Plugin will be active as long as the plugin or theme referencing it remains active.

## Usage

### Post thumbnail URL

After plugin installation post thumbnails are accessible via the following URL:

    /index.php?post_thumbnail=POST_ID

or, if pretty permalinks are enabled, via:

    /post_thumbnail/POST_ID

Post thumbnail size may be specified in the URL either by adding `size` query variable to the raw URL:

    /index.php?post_thumbnail=POST_ID&size=SIZE

or by appending it to the pretty URL:

    /post_thumbnail/POST_ID/SIZE

The size provided in the URL must be one of the registered thumbnail size names, i.e. built-in sizes (thumbnail, medium, large) or those added explicitly via `add_image_size()`. Unrecognized size names will be ignored, which will result in redirection to the original thumbnail image.

### Functions

The Plugin provides two functions for building unified post thumbnail URLs:

* `get_unified_post_thumbnail_url_structure()`

    which returns the current structure for unified URLs, with `post_id` and `size` parameters given as `%post_id%` and `%size%` respectively. This can be used as an URL template on the client side.

* `get_unified_post_thumbnail( $post_id [, $size ] )`

    which returns the unified post thumbnail URL for the given post ID and optionally specified thumbnail size

## Disclaimer

This plugin is intended for use by theme/plugin developers rather than end users, as it provides no usable functionality for the latter.
