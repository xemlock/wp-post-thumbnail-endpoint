<?php

/**
 * Plugin Name: WP Unified Post Thumbnail URL
 * Plugin URI:  http://github.com/xemlock/wp-unified-post-thumbnail-url
 * Description:
 * Author:      xemlock <xemlock@gmail.com>
 * Author URI:  http://xemlock.github.io
 * Version:     1.0.0-dev
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This plugin provides a special URL dependent only on Post ID, that redirects
 * to the Post Thumbnail (aka Featured Image) corresponding to the provided ID.
 * This can particularly be useful when implementing client-side JavaScript
 * features that only have post ID and want to display Post Thumbnail without
 * sending additional AJAX request to retrieve the thumbnail's location.
 *
 * This plugin is intended for use by theme/plugin developers rather than end
 * users.
 */

defined('ABSPATH') || die();

if (!defined('WP_UNIFIED_POST_THUMBNAIL_URL')) {
    define('WP_UNIFIED_POST_THUMBNAIL_URL', __FILE__);


abstract class WP_Unified_Post_Thumbnail_URL
{
    const POST_THUMBNAIL     = 'post_thumbnail';

    const PERMALINK_PREFIX   = self::POST_THUMBNAIL;

    const VAR_POST_THUMBNAIL = self::POST_THUMBNAIL;
    const VAR_SIZE           = 'size';

    const TAG_POST_ID        = '%post_id%';
    const TAG_SIZE           = '%size%';

    /**
     * Should rewrite rules be flushed whenever 'wp' action is triggered
     * @var bool
     */
    protected static $_flush_rewrite_rules = false;

    /**
     * Setup action and filter hooks provided by the plugin
     */
    public static function init()
    {
        add_action('init',       array(__CLASS__, 'setup_rewrite_rules'));
        add_action('wp_loaded',  array(__CLASS__, 'flush_rewrite_rules'));
        add_action('wp',         array(__CLASS__, 'handle_post_thumbnail'));

        add_filter('query_vars', array(__CLASS__, 'register_query_vars'));

        register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
    }

    /**
     * Register query variables required for post thumbnail URL handling
     *
     * This method is triggered by 'query_vars' filter.
     *
     * @param  array $vars
     * @return array
     */
    public static function register_query_vars(array $vars)
    {
        $vars[] = self::VAR_POST_THUMBNAIL;
        $vars[] = self::VAR_SIZE;
        return $vars;
    }

    /**
     * Setup rewrite rule for post thumbnail URL
     *
     * When not already present, the required rule is added and rewrite
     * rules are marked for flushing.
     *
     * This method is triggered by 'init' action.
     */
    public static function setup_rewrite_rules()
    {
        global $wp_rewrite;

        $pattern = sprintf('%s/(\d+)(/([^/]+))?', self::PERMALINK_PREFIX);
        $target = sprintf(
            'index.php?%s=$matches[1]&%s=$matches[3]',
            self::VAR_POST_THUMBNAIL,
            self::VAR_SIZE
        );

        // retrieve rewrite rules, results of this function are cached
        // internally by WP_Rewrite, so we can assume it is not expensive
        $rules = $wp_rewrite->wp_rewrite_rules();

        // check if rewrite rule is present and valid, otherwise mark
        // rewrite rules for flushing
        if (!isset($rules[$pattern]) || $rules[$pattern] !== $target) {
            self::$_flush_rewrite_rules = true;
        }

        add_rewrite_rule($pattern, $target, 'top');
    }

    /**
     * Flush rewrite rules if they are marked for flushing
     */
    public static function flush_rewrite_rules()
    {
        if (self::$_flush_rewrite_rules) {
            flush_rewrite_rules(false);
        }
    }

    /**
     * Redirect to post thumbnail image if the 'post_thumbnail' query variable
     * containing Post ID is provided
     */
    public static function handle_post_thumbnail()
    {
        $post_id = (int) get_query_var(self::VAR_POST_THUMBNAIL);

        if (empty($post_id)) {
            return;
        }

        $size = get_query_var(self::VAR_SIZE);

        // only registered thumbnail size names are recognized, i.e.
        // built-in sizes and those added via add_image_size()
        if (!in_array($size, get_intermediate_image_sizes(), true)) {
            $size = null;
        }

        $post_thumbnail_id = get_post_thumbnail_id($post_id);
        $img = wp_get_attachment_image_src($post_thumbnail_id, $size);

        if ($img) {
            // [0 => url, 1 => width, 2 => height]
            wp_redirect($img[0]);
        } else {
            status_header(404);
        }
        exit;
    }

    /**
     * Retrieve unified post thumbnail URL structure
     *
     * @return string
     */
    public static function get_url_structure()
    {
        global $wp_rewrite;

        // check if pretty permalinks are enabled
        if ($wp_rewrite->using_permalinks()) {
            return sprintf(
                '/%s/%s/%s',
                self::PERMALINK_PREFIX,
                self::TAG_POST_ID,
                self::TAG_SIZE
            );
        }

        return sprintf(
            '/index.php?%s=%s&%s=%s',
            self::VAR_POST_THUMBNAIL,
            self::TAG_POST_ID,
            self::VAR_SIZE,
            self::TAG_SIZE
        );
    }

    /**
     * Retrieve unified post thumbnail URL for the given post ID
     *
     * @param  int $post_id
     * @param  string $size OPTIONAL
     * @return string
     */
    public static function get_url($post_id, $size = null)
    {
        $url = strtr(self::get_url_structure(), array(
            self::TAG_POST_ID => (int) $post_id,
            self::TAG_SIZE    => urlencode(trim($size)),
        ));

        // strip off dangling slash if empty 'size' value is provided
        // (when pretty permalinks are enabled)
        $url = rtrim($url, '/');

        // strip off empty 'size' query variable (pretty permalinks disabled)
        $suffix = sprintf('&%s=', self::VAR_SIZE);
        if (substr($url, -strlen($suffix)) === $suffix) {
            $url = substr($url, 0, -strlen($suffix));
        }

        return get_site_url() . $url;
    }
}

/**
 * Retrieve unified post thumbnail URL for the given post ID
 *
 * @param  int $post_id
 * @param  string $size OPTIONAL
 * @return string
 */
function get_unified_post_thumbnail_url($post_id, $size = null)
{
    return WP_Unified_Post_Thumbnail_URL::get_url($post_id, $size);
}

/**
 * Retrieve unified post thumbnail URL structure
 *
 * @return string
 */
function get_unified_post_thumbnail_url_structure()
{
    return WP_Unified_Post_Thumbnail_URL::get_url_structure();
}

WP_Unified_Post_Thumbnail_URL::init();

} // WP_UNIFIED_POST_THUMBNAIL_URL
