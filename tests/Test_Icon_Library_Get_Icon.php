<?php

/**
 * Tests for the icon template helpers (src/icon.php).
 *
 * @package sf-icon-manager
 */

/**
 * Tests for sfim_get_icon() and its helpers.
 */
final class Test_Icon_Library_Get_Icon extends WP_UnitTestCase
{
    private const SPRITE = 'https://example.test/wp-content/uploads/sf-icon-manager/ico.svg?m=1694000000';

    protected function setUp(): void
    {
        parent::setUp();
        remove_all_filters('sfim_sprite_url');
        add_filter('sfim_sprite_url', fn() => self::SPRITE);
    }

    public function test_renders_a_svg_fragment_that_references_the_sprite(): void
    {
        $this->assertSame(
            '<svg aria-hidden="true" focusable="false" class="sfim-icon" style="width:1em;height:1em;fill:currentColor">'
            . '<use href="' . self::SPRITE . '#close"></use></svg>',
            sfim_get_icon('close'),
        );
    }

    public function test_appends_pixels_to_a_numeric_size(): void
    {
        $this->assertStringContainsString('style="width:20px;height:20px;', sfim_get_icon('close', ['size' => 20]));
    }

    public function test_keeps_css_lengths_as_they_are(): void
    {
        $this->assertStringContainsString('style="width:1.5rem;height:1.5rem;', sfim_get_icon('close', ['size' => '1.5rem']));
    }

    public function test_width_and_height_override_the_size(): void
    {
        $this->assertStringContainsString(
            'style="width:32px;height:2em;',
            sfim_get_icon('close', ['size' => '1em', 'width' => 32, 'height' => '2em']),
        );
    }

    public function test_fill_can_be_overridden_or_left_to_the_symbol(): void
    {
        $this->assertStringContainsString('fill:#bada55', sfim_get_icon('close', ['fill' => '#bada55']));
        $this->assertStringNotContainsString('fill:', sfim_get_icon('close', ['fill' => '']));
    }

    public function test_resolves_preset_colors(): void
    {
        $this->assertStringContainsString(
            'color:var(--wp--preset--color--vivid-red);background-color:#123456',
            sfim_get_icon('close', ['color' => 'vivid-red', 'background' => '#123456']),
        );
    }

    public function test_applies_padding_as_a_shorthand(): void
    {
        $this->assertStringContainsString('padding:4px', sfim_get_icon('close', ['padding' => '4px']));
    }

    public function test_applies_padding_per_side_and_skips_empty_sides(): void
    {
        $html = sfim_get_icon('close', [
            'padding' => [
                'top'    => 'var:preset|spacing|30',
                'left'   => '4px',
                'bottom' => '',
            ],
        ]);

        $this->assertStringContainsString(
            'padding-top:var(--wp--preset--spacing--30);padding-left:4px',
            $html,
        );
        $this->assertStringNotContainsString('padding-bottom', $html);
        $this->assertStringNotContainsString('padding-right', $html);
    }

    public function test_appends_extra_class_names(): void
    {
        $this->assertStringContainsString('class="sfim-icon my-icon extra"', sfim_get_icon('close', ['class' => ' my-icon  extra ']));
    }

    public function test_accepts_class_names_as_a_list(): void
    {
        $this->assertStringContainsString('class="sfim-icon my-icon extra"', sfim_get_icon('close', ['class' => ['my-icon', 'extra']]));
    }

    public function test_appends_pixels_to_a_numeric_padding(): void
    {
        $this->assertStringContainsString('padding:8px', sfim_get_icon('close', ['padding' => 8]));
    }

    public function test_marks_a_labelled_icon_as_an_image(): void
    {
        $html = sfim_get_icon('close', ['label' => 'Close dialog']);

        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('aria-label="Close dialog"', $html);
        $this->assertStringNotContainsString('aria-hidden="true"', $html);
    }

    public function test_moves_the_label_to_the_link_when_linked(): void
    {
        $html = sfim_get_icon('close', ['link' => 'https://example.test/about', 'label' => 'About']);

        $this->assertStringContainsString('<a href="https://example.test/about" aria-label="About">', $html);
        $this->assertStringContainsString('<svg aria-hidden="true"', $html);
        $this->assertStringEndsWith('</a>', $html);
    }

    public function test_adds_noopener_and_noreferrer_for_new_tab_links(): void
    {
        $html = sfim_get_icon('close', ['link' => '/about', 'target' => '_blank', 'rel' => 'nofollow']);

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
    }

    public function test_ignores_an_unknown_link_target(): void
    {
        $html = sfim_get_icon('close', ['link' => '/about', 'target' => 'evil" onload="x']);

        $this->assertStringNotContainsString('target=', $html);
        $this->assertStringNotContainsString('onload="', $html);
    }

    public function test_escapes_label_and_class_arguments(): void
    {
        $html = sfim_get_icon('close', [
            'class' => '" onload="x',
            'label' => '<script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('aria-label="&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('onload="', $html);
    }

    public function test_percent_encodes_the_symbol_id(): void
    {
        $this->assertStringContainsString(
            self::SPRITE . '#a%20b%22c%2Fd',
            sfim_get_icon('a b"c/d'),
        );
    }

    public function test_keeps_uppercase_symbol_ids(): void
    {
        $this->assertStringContainsString(self::SPRITE . '#Close', sfim_get_icon('Close'));
    }

    public function test_returns_an_empty_string_for_an_empty_id(): void
    {
        $this->assertSame('', sfim_get_icon(''));
        $this->assertSame('', sfim_get_icon('   '));
        $this->assertSame('', sfim_get_icon_href(''));
    }

    public function test_keeps_a_fragment_from_the_filtered_sprite_url(): void
    {
        remove_all_filters('sfim_sprite_url');
        add_filter('sfim_sprite_url', fn() => 'https://cdn.example.net/icons.svg#brand');

        $this->assertSame('https://cdn.example.net/icons.svg#brand', sfim_get_icon_href('close'));
        $this->assertStringContainsString(
            '<use href="https://cdn.example.net/icons.svg#brand"></use>',
            sfim_get_icon('close'),
        );
    }

    public function test_uses_the_short_url_when_enabled(): void
    {
        add_filter('sfim_short_url', '__return_true');

        $this->assertSame('/i.svg#close', sfim_get_icon_href('close'));
    }

    public function test_icon_length_normalizes_bare_numbers_only(): void
    {
        $this->assertSame('20px', sfim_icon_length('20'));
        $this->assertSame('1.5px', sfim_icon_length('1.5'));
        $this->assertSame('2em', sfim_icon_length('2em'));
        $this->assertSame('50%', sfim_icon_length(' 50% '));
        $this->assertSame('', sfim_icon_length(''));
    }

    public function test_icon_padding_declarations_maps_shorthand_and_sides(): void
    {
        $this->assertSame(['padding' => '4px'], sfim_icon_padding_declarations('4px'));
        $this->assertSame([], sfim_icon_padding_declarations(''));
        $this->assertSame(
            ['padding-top' => '4px', 'padding-left' => '2em'],
            sfim_icon_padding_declarations(['top' => '4px', 'right' => '', 'bottom' => '', 'left' => '2em']),
        );
    }
}
