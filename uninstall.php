<?php

/**
 * Uninstall routine: removes the plugin's leftover options.
 *
 * Only options this plugin itself created are removed. The uploaded sprite
 * option (SFIM_SPRITE_OPTION) is deliberately kept: it points at a file in
 * the uploads directory, and uninstalling the plugin must not delete user
 * files. Re-installing the plugin therefore still finds its configuration.
 *
 * @package sf-icon-manager
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

// Native WordPress icon integration (removed from the plugin).
delete_option('sfim_native');

// Cache of the sprite icons parsed for the removed native icon API.
delete_option('sfim_icons');

// Setting of the "hide the WordPress core Icon block" switch.
delete_option('sfim_hide_core_icon_block');
