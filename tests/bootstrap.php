<?php

/**
 * PHPUnit bootstrap: loads Composer, the PHPUnit polyfills, the WordPress
 * test suite (wp-phpunit) and then the plugin under test.
 *
 * @package sf-icon-manager
 */

if (! file_exists($sfim_autoload = dirname(__DIR__) . '/vendor/autoload.php')) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI bootstrap; WP escape helpers are not loaded yet.
    printf("Missing %s - run 'composer install' first.\n", $sfim_autoload);
    exit(1);
}

require_once $sfim_autoload;

// PHPUnit cross-version compatibility.
require_once dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$sfim_wp_tests_dir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit/includes';

if (! file_exists("{$sfim_wp_tests_dir}/bootstrap.php")) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI bootstrap; WP escape helpers are not loaded yet.
    printf("Missing the WordPress test library at %s.\n", $sfim_wp_tests_dir);
    exit(1);
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Name is required verbatim by the WP test suite (WP_UnitTestCase).
if (! defined('WP_TESTS_CONFIG_FILE_PATH')) {
    define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php');
}

// The WordPress test suite (gives tests access to tests_add_filter()).
require_once "{$sfim_wp_tests_dir}/functions.php";

/**
 * Manually loads the plugin being tested.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Test-hook loader; the name carries the sfim prefix.
function _sfim_manually_load_plugin(): void
{
    require dirname(__DIR__) . '/sf-icon-manager.php';
}
tests_add_filter('muplugins_loaded', '_sfim_manually_load_plugin');

// Starts up the WordPress testing environment.
require "{$sfim_wp_tests_dir}/bootstrap.php";

// Admin-only module: make it explicit for tests that call upload/sanitizer helpers.
require_once dirname(__DIR__) . '/src/admin/admin.php';
