<?php

/**
 * Tests for hiding WordPress' own Icon block (core/icon) in the backend.
 *
 * This is deliberately not the plugin's own icon block (src/block); the
 * module under test only removes core/icon from the editing backend.
 *
 * @package sf-icon-manager
 */

/**
 * Tests the "Disable the WordPress core Icon block" switch: the setting
 * itself, the server-side deregistration, the allowed block types and the
 * editor script.
 */
final class Test_Icon_Library_Hide_Core_Icon_Block extends WP_UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        delete_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION);
    }

    public function test_the_settings_panel_renders_the_checkbox(): void
    {
        $user = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user);

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '0');
        $off = $this->render_settings_panel();
        $this->assertStringContainsString('name="sfim_hide_core_icon_block" value="1"', $off);
        $this->assertStringNotContainsString("checked='checked'", $off);
        $this->assertStringNotContainsString('checked="checked"', $off);

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '1');
        $on = $this->render_settings_panel();
        $this->assertTrue(
            str_contains($on, "checked='checked'") || str_contains($on, 'checked="checked"'),
            'Checkbox was not checked in the rendered panel.',
        );
    }

    public function test_saving_the_form_stores_the_checkbox_state(): void
    {
        $user = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user);

        // The settings handler redirects and exits, so the redirect is turned
        // into an exception to keep the assertion in reach.
        add_filter('wp_redirect', static function ($location) {
            throw new RuntimeException((string) $location);
        });

        // check_admin_referer() reads $_REQUEST, which the CLI SAPI does not
        // populate from $_POST on its own.
        $_POST['_wpnonce'] = wp_create_nonce('sfim_update_icon_block');
        $_REQUEST['_wpnonce'] = $_POST['_wpnonce'];
        $_POST['sfim_hide_core_icon_block'] = '1';

        try {
            sfim_handle_icon_block_update();
            $this->fail('The handler did not redirect.');
        } catch (RuntimeException $redirect) {
            $this->assertStringContainsString('sfim_message=icon_block_updated', $redirect->getMessage());
        }

        $this->assertTrue(sfim_core_icon_block_hidden());

        // An unchecked checkbox is not submitted at all and must switch off.
        unset($_POST['sfim_hide_core_icon_block']);

        try {
            sfim_handle_icon_block_update();
            $this->fail('The handler did not redirect.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        $this->assertFalse(sfim_core_icon_block_hidden());
    }

    /**
     * Renders the settings panel and returns its markup.
     *
     * @return string
     */
    private function render_settings_panel(): string
    {
        ob_start();
        sfim_settings_panel();

        return (string) ob_get_clean();
    }

    public function test_the_switch_is_off_by_default(): void
    {
        delete_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION);

        $this->assertFalse(sfim_core_icon_block_hidden());
    }

    public function test_the_switch_reads_the_stored_value(): void
    {
        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '1');
        $this->assertTrue(sfim_core_icon_block_hidden());

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '0');
        $this->assertFalse(sfim_core_icon_block_hidden());

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, 'garbage');
        $this->assertFalse(sfim_core_icon_block_hidden());
    }

    public function test_the_switch_is_wired_to_the_expected_hooks(): void
    {
        $this->assertSame(PHP_INT_MAX, has_action('init', 'sfim_deregister_core_icon_block'));
        $this->assertSame(PHP_INT_MAX, has_action('rest_api_init', 'sfim_deregister_core_icon_block'));
        $this->assertSame(PHP_INT_MAX, has_action('admin_enqueue_scripts', 'sfim_deregister_core_icon_block'));
        $this->assertSame(PHP_INT_MAX, has_action('enqueue_block_editor_assets', 'sfim_deregister_core_icon_block'));
        $this->assertSame(10, has_filter('allowed_block_types_all', 'sfim_deny_core_icon_block_types'));
        $this->assertSame(10, has_action('enqueue_block_editor_assets', 'sfim_hide_core_icon_block_editor_assets'));
    }

    /**
     * The module is backend-only: the plugin adds no frontend filter for the
     * rendered output, so stored content keeps its frontend rendering.
     */
    public function test_the_module_never_filters_frontend_rendering(): void
    {
        $this->assertFalse(function_exists('sfim_strip_core_icon_block'));

        $render_block = $GLOBALS['wp_filter']['render_block'] ?? null;

        if (! $render_block instanceof WP_Hook) {
            $this->assertNull($render_block, 'Unexpected render_block hook state.');

            return;
        }

        $plugin_callbacks = [
            'sfim_core_icon_block_hidden',
            'sfim_deregister_core_icon_block',
            'sfim_deny_core_icon_block_types',
            'sfim_hide_core_icon_block_editor_assets',
        ];

        foreach ($render_block->callbacks as $registered) {
            $function = $registered['function'] ?? null;

            if (is_string($function)) {
                $this->assertNotContains($function, $plugin_callbacks);
            }
        }
    }

    public function test_deregister_hides_the_block_only_when_the_switch_is_on(): void
    {
        if (! class_exists('WP_Block_Type_Registry')) {
            $this->markTestSkipped('WordPress block type registry not available in this test environment.');
        }

        $registry = WP_Block_Type_Registry::get_instance();
        $dummy = 'sf-icon-manager/dummy';

        if (! $registry->is_registered($dummy)) {
            $registry->register($dummy, ['title' => 'Dummy']);
        }

        delete_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION);
        sfim_deregister_core_icon_block($dummy);
        $this->assertTrue($registry->is_registered($dummy));

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '1');
        sfim_deregister_core_icon_block($dummy);
        $this->assertFalse($registry->is_registered($dummy));
    }

    public function test_deregister_ignores_non_string_hook_arguments(): void
    {
        if (! class_exists('WP_Block_Type_Registry')) {
            $this->markTestSkipped('WordPress block type registry not available in this test environment.');
        }

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '1');

        // rest_api_init passes the WP_REST_Server as the first callback
        // argument; it must not break the deregistration or be treated as
        // a block name.
        sfim_deregister_core_icon_block(new stdClass());
        sfim_deregister_core_icon_block(null);

        $this->assertFalse(WP_Block_Type_Registry::get_instance()->is_registered('core/icon'));
    }

    public function test_editor_unregister_script_enqueued_only_when_the_switch_is_on(): void
    {
        delete_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION);
        sfim_hide_core_icon_block_editor_assets();
        $this->assertFalse(wp_script_is('sf-icon-manager-unregister-icon-block', 'registered'));

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '1');
        sfim_hide_core_icon_block_editor_assets();
        $this->assertTrue(wp_script_is('sf-icon-manager-unregister-icon-block', 'registered'));
        $this->assertTrue(wp_script_is('sf-icon-manager-unregister-icon-block', 'enqueued'));

        // The static asset must exist and be served from the plugin.
        $data = wp_scripts()->registered['sf-icon-manager-unregister-icon-block'];
        $this->assertFileExists(dirname(SFIM_PLUGIN_FILE) . '/assets/js/unregister-icon-block.js');
        $this->assertSame(['wp-dom-ready', 'wp-blocks'], $data->deps);
    }

    public function test_deny_removes_the_icon_block_from_the_allowlist_only_when_the_switch_is_on(): void
    {
        delete_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION);
        $this->assertSame(['core/icon', 'core/paragraph'], sfim_deny_core_icon_block_types(['core/icon', 'core/paragraph']));
        $this->assertTrue(sfim_deny_core_icon_block_types(true));
        $this->assertFalse(sfim_deny_core_icon_block_types(false));

        update_option(SFIM_HIDE_CORE_ICON_BLOCK_OPTION, '1');
        $this->assertSame(['core/paragraph'], sfim_deny_core_icon_block_types(['core/icon', 'core/paragraph']));
        $this->assertSame(['core/paragraph'], sfim_deny_core_icon_block_types(['core/paragraph']));
        $this->assertFalse(sfim_deny_core_icon_block_types(false));
        $this->assertIsArray(sfim_deny_core_icon_block_types(true));
        $this->assertNotContains('core/icon', sfim_deny_core_icon_block_types(true));
    }
}
