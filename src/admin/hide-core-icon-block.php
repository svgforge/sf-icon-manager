<?php

/**
 * Hiding WordPress' own Icon block (core/icon) in the editing backend.
 *
 * This is **not** the plugin's icon block (that one lives in src/block). This
 * module only removes core's built-in core/icon block from the backend, so
 * sites that manage a central sprite end up with a single icon block while
 * editing: core/icon takes its icons from WordPress' own icon registry, not
 * from the sprite, so its output cannot be kept consistent with the sprite
 * pipeline.
 *
 * Backend only. The plugin loads this file for wp-admin requests and for REST
 * requests (the block editor reads its settings through the REST API), never
 * while a frontend page is rendered. Content that already contains core/icon
 * therefore keeps both its stored markup and its previous frontend output.
 *
 * The switch is a plain checkbox ("Disable the WordPress core Icon block")
 * stored in the SFIM_HIDE_CORE_ICON_BLOCK_OPTION option ('0' by default).
 * When it is on, the block type is deregistered, excluded from the allowed
 * block types and removed client-side in the editor.
 *
 * @package sf-icon-manager
 */
defined('ABSPATH') || exit;

/**
 * Option key for the "hide the WordPress core Icon block" switch.
 *
 * Values: '0' (default, core/icon stays as in core) or '1' (core/icon is
 * removed from the backend). Every other value counts as '0'.
 */
const SFIM_HIDE_CORE_ICON_BLOCK_OPTION = 'sfim_hide_core_icon_block';

/**
 * Returns whether WordPress' core Icon block is hidden in the backend.
 *
 * @return bool True when core/icon should be removed.
 */
function sfim_core_icon_block_hidden(): bool
{
    return '1' === get_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '0');
}

/**
 * Deregisters core/icon when the "hide core Icon block" switch is on.
 *
 * Removal happens repeatedly because the block metadata collection of core
 * registers blocks lazily: any registry lookup after an unregister would
 * re-add it. Running the unregister late in init, during REST setup and right
 * before the block editor renders covers every path.
 *
 * The parameter stays untyped on purpose: the function doubles as a hook
 * callback for rest_api_init, which passes a WP_REST_Server object (never a
 * block name). The is_string() guard filters such hook arguments.
 *
 * @param mixed $block_name Block type to deregister. Default 'core/icon'.
 */
function sfim_deregister_core_icon_block($block_name = 'core/icon'): void
{
    // Hook callbacks receive argument values (e.g. rest_api_init passes the
    // WP_REST_Server); those are never block names.
    if (! is_string($block_name) || $block_name === '') {
        $block_name = 'core/icon';
    }

    if (! sfim_core_icon_block_hidden() || ! class_exists('WP_Block_Type_Registry')) {
        return;
    }

    $registry = WP_Block_Type_Registry::get_instance();

    if ($registry->is_registered($block_name)) {
        $registry->unregister($block_name);
    }
}
add_action('init', 'sfim_deregister_core_icon_block', PHP_INT_MAX);
add_action('rest_api_init', 'sfim_deregister_core_icon_block', PHP_INT_MAX);
add_action('admin_enqueue_scripts', 'sfim_deregister_core_icon_block', PHP_INT_MAX);
add_action('enqueue_block_editor_assets', 'sfim_deregister_core_icon_block', PHP_INT_MAX);

/**
 * Removes core/icon from the editor's allowed block types.
 *
 * Falls back to the full registry when no explicit allowlist was set so the
 * block never appears in the inserter, without losing the current selection.
 *
 * @param bool|array|null $allowed Current allowlist, or a boolean to
 *                                 enable/disable all block types.
 * @return bool|array|null
 */
function sfim_deny_core_icon_block_types(mixed $allowed): bool|array|null
{
    if (! sfim_core_icon_block_hidden()) {
        return is_bool($allowed) || is_array($allowed) ? $allowed : null;
    }

    $exclude = ['core/icon'];

    if (false === $allowed) {
        return false;
    }

    if (is_array($allowed)) {
        return array_values(array_diff($allowed, $exclude));
    }

    $all = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());

    return array_values(array_diff($all, $exclude));
}
add_filter('allowed_block_types_all', 'sfim_deny_core_icon_block_types');

/**
 * Unregisters core/icon in the block editor when the switch is on.
 *
 * Registers the blocks' client-side types independently of the server-side
 * block registry, so an unregister_block_type() alone leaves blocks that are
 * already saved in content working in the editor. This script removes the
 * block type there as well; domReady runs after the core block library has
 * registered the client-side types.
 *
 * @return void
 */
function sfim_hide_core_icon_block_editor_assets(): void
{
    if (! sfim_core_icon_block_hidden()) {
        return;
    }

    $path = dirname(SFIM_PLUGIN_FILE) . '/assets/js/unregister-icon-block.js';

    if (! is_file($path)) {
        return;
    }

    wp_enqueue_script(
        'sf-icon-manager-unregister-icon-block',
        plugins_url('assets/js/unregister-icon-block.js', SFIM_PLUGIN_FILE),
        ['wp-dom-ready', 'wp-blocks'],
        (string) @filemtime($path),
        true,
    );
}
add_action('enqueue_block_editor_assets', 'sfim_hide_core_icon_block_editor_assets');
