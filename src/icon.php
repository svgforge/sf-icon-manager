<?php

/**
 * Template helpers: render a sprite icon as an SVG fragment from theme code.
 *
 * Classic themes (and any other custom PHP) can print an icon without
 * writing the <svg>/<use> markup by hand:
 *
 *     echo sfim_get_icon('close', ['size' => 20, 'label' => 'Close']);
 *
 * The markup is the same the SVG Icon block renders, so an icon looks
 * identical in a theme template and in post content.
 *
 * @package sf-icon-manager
 */
defined('ABSPATH') || exit;

/**
 * Builds the <use> href for one symbol of the active sprite.
 *
 * The sprite source is resolved exactly as for the block: the short URL when
 * enabled, otherwise filter, upload or bundled fallback. A sprite URL from
 * the `sfim_sprite_url` filter may already carry a fragment — that fragment
 * then wins, as in the block render.
 *
 * @param string $id Symbol id of the sprite, e.g. 'close'. See sfim_sprite_symbols().
 *
 * @return string Raw, unescaped href such as
 *                'https://example.com/ico.svg?m=1694000000#close'.
 *                Returns '' when the id is empty or no sprite is configured.
 */
function sfim_get_icon_href(string $id): string
{
    $id = trim($id);

    if ($id === '') {
        return '';
    }

    $sprite = sfim_sprite_url();

    if ($sprite === '') {
        return '';
    }

    if (str_contains($sprite, '#')) {
        return $sprite;
    }

    // rawurlencode() instead of sanitize_key(): ids come from theme code and
    // may legitimately contain uppercase letters or dots, which sanitize_key()
    // would rewrite and break the reference.
    return rtrim($sprite, '#') . '#' . rawurlencode($id);
}

/**
 * Normalizes a size argument to a CSS length: a bare number becomes pixels
 * ('20' → '20px'), everything else is passed through ('1.5rem', '2em', '20%').
 *
 * @param string $value Raw length.
 * @return string CSS length, or '' when empty.
 */
function sfim_icon_length(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    return preg_match('/^\d+(\.\d+)?$/', $value) === 1 ? $value . 'px' : $value;
}

/**
 * Converts the `padding` argument of sfim_get_icon() into CSS declarations.
 *
 * @param string|array<string, string> $padding Shorthand value, or an array
 *                                              with the keys top, right, bottom, left.
 * @return array<string, string> Declarations such as ['padding-top' => '4px'].
 */
function sfim_icon_padding_declarations(string|array $padding): array
{
    if (! is_array($padding)) {
        $padding = sfim_resolve_spacing(trim($padding));

        return $padding === '' ? [] : ['padding' => sfim_icon_length($padding)];
    }

    $declarations = [];

    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        $value = trim((string) ($padding[$side] ?? ''));

        if ($value !== '') {
            $declarations['padding-' . $side] = sfim_resolve_spacing($value);
        }
    }

    return $declarations;
}

/**
 * Renders one sprite icon as an <svg><use></use></svg> fragment.
 *
 * The returned string is fully escaped and can be echoed directly. In a theme
 * that runs PHP_CodeSniffer, silence the escape warning once:
 *
 *     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in sfim_get_icon().
 *     echo sfim_get_icon('close');
 *
 * Supported arguments:
 *
 * | Key          | Type             | Default         | Purpose                                                |
 * |--------------|------------------|-----------------|--------------------------------------------------------|
 * | `size`       | string           | `'1em'`         | Width and height; a bare number gets `px` appended.    |
 * | `width`      | string           | `''`            | Overrides `size` for the width.                        |
 * | `height`     | string           | `''`            | Overrides `size` for the height.                       |
 * | `fill`       | string           | `'currentColor'`| Icon fill; `''` keeps the fill of the symbol itself.   |
 * | `color`      | string           | `''`            | Text color; accepts theme palette presets.             |
 * | `background` | string           | `''`            | Background color; accepts theme palette presets.       |
 * | `padding`    | string or array  | `''`            | Shorthand, or ['top' => …, 'right' => …, …].          |
 * | `class`      | string           | `''`            | Extra class names on the <svg>.                        |
 * | `label`      | string           | `''`            | Accessible name; without it the icon stays hidden.     |
 * | `link`       | string           | `''`            | Wraps the icon in a link.                              |
 * | `target`     | string           | `''`            | Link target: _blank, _self, _parent or _top.           |
 * | `rel`        | string           | `''`            | Link rel; noopener noreferrer are added for _blank.    |
 *
 * Colors and spacings are resolved like the block does, so preset references
 * (vivid-red, var:preset|spacing|30) work unchanged.
 *
 * @param string               $id   Symbol id of the sprite.
 * @param array<string, mixed> $args Optional. See the table above.
 *
 * @return string Icon markup, or '' when the id is empty or no sprite exists.
 */
function sfim_get_icon(string $id, array $args = []): string
{
    $href = sfim_get_icon_href($id);

    if ($href === '') {
        return '';
    }

    $args = wp_parse_args($args, [
        'size'       => '1em',
        'width'      => '',
        'height'     => '',
        'fill'       => 'currentColor',
        'color'      => '',
        'background' => '',
        'padding'    => '',
        'class'      => '',
        'label'      => '',
        'link'       => '',
        'target'     => '',
        'rel'        => '',
    ]);

    $label = trim((string) $args['label']);
    $link  = trim((string) $args['link']);

    $size = (string) $args['size'];

    // Style declarations in a fixed order: size, fill, colors, padding.
    $declarations = [
        'width'  => sfim_icon_length((string) ($args['width'] !== '' ? $args['width'] : $size)),
        'height' => sfim_icon_length((string) ($args['height'] !== '' ? $args['height'] : $size)),
    ];

    $fill = trim((string) $args['fill']);

    if ($fill !== '') {
        $declarations['fill'] = $fill;
    }

    $color = trim((string) $args['color']);

    if ($color !== '') {
        $declarations['color'] = sfim_resolve_color($color);
    }

    $background = trim((string) $args['background']);

    if ($background !== '') {
        $declarations['background-color'] = sfim_resolve_color($background);
    }

    $padding = $args['padding'];

    $declarations = array_merge(
        $declarations,
        sfim_icon_padding_declarations(is_array($padding) ? $padding : (string) $padding),
    );

    $style = '';

    foreach ($declarations as $property => $value) {
        $value = trim((string) $value);

        if ($value !== '') {
            $style .= $property . ':' . $value . ';';
        }
    }

    $classes = ['sfim-icon'];
    // Class names may be passed as a string or as a list.
    $extra = is_array($args['class'])
        ? implode(' ', array_map('strval', $args['class']))
        : (string) $args['class'];
    $extra = trim((string) preg_replace('/\s+/', ' ', $extra));

    if ($extra !== '') {
        $classes[] = $extra;
    }

    // Accessibility: a linked icon is announced through its link, a standalone
    // icon through its own label. Without a label the icon stays decorative.
    if ($link !== '') {
        $svg_aria  = ' aria-hidden="true"';
        $link_aria = $label !== '' ? ' aria-label="' . esc_attr($label) . '"' : '';
    } else {
        $svg_aria  = $label !== ''
            ? ' role="img" aria-label="' . esc_attr($label) . '"'
            : ' aria-hidden="true"';
        $link_aria = '';
    }

    $svg = '<svg' . $svg_aria
        . ' focusable="false"'
        . ' class="' . esc_attr(implode(' ', $classes)) . '"'
        . ' style="' . esc_attr($style) . '">'
        . '<use href="' . esc_url($href) . '"></use>'
        . '</svg>';

    if ($link === '') {
        return wp_kses($svg, sfim_allowed_svg_kses());
    }

    $link_attributes = 'href="' . esc_url($link) . '"';

    $target = in_array($args['target'], ['_blank', '_self', '_parent', '_top'], true) ? (string) $args['target'] : '';

    if ($target !== '') {
        $link_attributes .= ' target="' . esc_attr($target) . '"';
    }

    $rel = trim((string) preg_replace('/\s+/', ' ', (string) $args['rel']));

    if ($target === '_blank') {
        $rel = implode(' ', array_unique(array_merge(
            $rel === '' ? [] : explode(' ', $rel),
            ['noopener', 'noreferrer'],
        )));
    }

    if ($rel !== '') {
        $link_attributes .= ' rel="' . esc_attr($rel) . '"';
    }

    $html = '<a ' . $link_attributes . $link_aria . '>' . $svg . '</a>';

    return wp_kses($html, sfim_allowed_svg_kses());
}
