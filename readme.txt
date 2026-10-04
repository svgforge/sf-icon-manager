=== SVG Forge Icon Manager ===
Authors: wpdynamics
Contributors: wpdynamics
Tags: svg, icons, sprite, gutenberg
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.5.0
License: MIT
License URI: https://opensource.org/licenses/MIT

SVG Icon Block: inserts icons from your own sprite via <use>. No frontend PHP, CSS or JS, and one static, cacheable SVG file for all icons.

== Description ==

The SVG Forge Icon Manager Block loads a central SVG sprite file (by default the bundled `i.svg`), shows all contained `<symbol>` elements in a convenient picker and inserts the selected icon as `<svg><use href="/i.svg#symbol-id">` into your content.

= Why another icon plugin? =

* **A W3C standard:** every icon is referenced with the `<use>` element ([SVG 2](https://www.w3.org/TR/SVG2/struct.html#UseElement)). All current browsers support it — no polyfill, no build step.
* **No frontend cost:** the plugin loads no external CSS or JavaScript. The browser fetches the sprite once and renders every icon from it.
* **One cacheable file:** a static SVG file, fetched once and reused by every icon on the site.
* **Full SVG survives:** stroke icons, gradients, `currentColor`, inline styles and custom `viewBox` values. WordPress 7.1's sanitizer allows only `<svg>`, `<path>` and `<polygon>` and breaks most stroke-based sets.
* **Classic Themes**: You can use php to output icons in your classic theme, `echo sfim_get_icon( 'icon-id' );`

= Who is this plugin for? =

Developers who maintain their own sprite file. Every icon must exist as a `<symbol>` in a sprite you build and version — there is no icon manager UI.

WordPress 7.1 ships its own icon system (`wp_register_icon()`, `wp_get_icon()`). That is enough for a few icons registered in PHP. If you run a sprite, use this plugin: no frontend code, and it works on WordPress 6.6 and later.

= Features =

* Settings page to upload the sprite file (`.svg` or `.svgz`).
* Uploaded sprites are sanitized with a strict allowlist: no scripts, no event handlers, no `foreignObject`, no unknown tags. Same engine as [Safe SVG](https://wordpress.org/plugins/safe-svg/).
* Upload a sprite you already have, or build one with svgforge-cli.
* `sfim_sprite_url` filter overrides the sprite file (e.g. `get_theme_file_uri()`).
* Symbol picker in the editor with a live preview.
* Icons from sprite subdirectories are grouped.
* Icons can be linked (new tab, `rel` including `noopener`/`noreferrer`).
* Aria-label for screen readers.
* Fill and stroke colour as well as size per block.
* Server-side rendering with `get_block_wrapper_attributes()`.
* Alignment, anchor and additional CSS classes.
* Optional switch to remove the core Icon block (`core/icon`) from the editor.
* Icon helper for classic themes: `echo sfim_get_icon( 'close', [ 'size' => 20 ] );`

= Honest limitations =

* Adding or editing icons means rebuilding the sprite file — typically with [svgforge-cli](https://github.com/svgforge/svgforge-cli/).

= Configure the sprite file =

The SVG sprite file is resolved in this order (first existing source wins):

1. Filter `sfim_sprite_url` – the canonical override (has priority).
2. Uploaded file from Settings → SVG Forge Icon Manager (backend upload).
3. Fallback: `sprite.svg` in the plugin directory.

Override with a filter (recommended, versionable with the theme) in the theme's `functions.php`:

    add_filter( 'sfim_sprite_url', fn () => get_theme_file_uri( 'assets/ico.svg' ) );

or point it at a CDN:

    add_filter( 'sfim_sprite_url', fn () => 'https://cdn.example.com/icons/ico.svg' );

If you have no sprite yet, this [tutorial walks you through building your own icon set](https://svgforge.github.io/blog/posts/custom-icon-sets/) step by step.

= Icons in theme templates =

Classic themes (and any other custom PHP) can print icons from the sprite without using the block:

    // Decorative icon: hidden from screen readers, sizes with the font (1em).
    echo sfim_get_icon( 'close' );

    // Sized, labelled and linked.
    echo sfim_get_icon( 'close', [ 'size' => 20, 'label' => 'Close', 'link' => get_permalink() ] );

`sfim_get_icon()` returns the same `<svg><use>` fragment the block renders, so icons look identical in a template and in post content. It is a plain function of the plugin – no `require`, no `use`, just call it. Useful arguments: `size` (or `width`/`height`), `color`, `background`, `padding`, `class`, `label`, `link`, `target` and `rel`.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (or install the ZIP via Plugins → Add New).
2. Activate the plugin under "Plugins".
3. Upload the sprite file (`ico.svg` with `<symbol id="...">` elements) via **Settings → SVG Forge Icon Manager** or reference your own file with the `sfim_sprite_url` filter.
4. In the editor, add the "SVG Icon" block and choose an icon.

== Frequently Asked Questions ==

= Where do the icons come from? =

From the central sprite file `ico.svg`. Each icon is a `<symbol id="my-icon" viewBox="0 0 24 24">…</symbol>` element. The sprite is a plain static SVG file; the browser pulls single icons out of it with the `<use>` element ([SVG 2, W3C](https://www.w3.org/TR/SVG2/struct.html#UseElement)).

= Why not simply use the native SVG icons of WordPress 7.1? =

WordPress 7.1 offers a native icon system with `wp_register_icon_collection()` / `wp_register_icon()` / `wp_get_icon()` — that is enough if you register a few icons directly in code. With a central SVG sprite this plugin goes further: the icons are served as standard SVG fragments ([W3C specification](https://www.w3.org/TR/SVG2/struct.html#UseElement)) with no frontend PHP, CSS or JS, full SVG freedom (including stroke icons), existing sprites without per-icon PHP code, one cacheable file and per-instance block styling.

= Can I remove the built-in Icon block? =

Yes. On the settings page (WordPress core Icon block) you can enable "Disable the WordPress core Icon block". `core/icon` is then removed from the block editor (also for blocks already saved in content) and from the block-types REST API, so the SVG Icon block is the only icon block while editing. This is a backend switch: the frontend is not touched and stored content keeps rendering as before, so you can switch it back on at any time.

= Does it work without JS in the frontend? =

Yes. The frontend markup is generated server-side in `render.php`; the built JS is only needed in the editor.

= How do I disable the color options for the block? =

Put the block's color settings into your theme's `theme.json`:

    {
        "version": 3,
        "settings": {
            "blocks": {
                "sf-icon-manager/svg-icon": {
                    "color": { "custom": false, "palette": [] }
                }
            }
        }
    }

That removes the standard Gutenberg Color/Background panels for the SVG Icon block. As in the core Icon block, multi-color icons (e.g. Tango icon sets) keep their baked-in colors and are not affected by the color controls; monochrome icons follow the chosen color.

= How do I offer preset sizes like font sizes? =

The size is the standard Gutenberg Dimensions panel (`supports.dimensions.width`, like the core Icon block): the block is square, and size presets are standard theme.json `dimensionSizes` per block:

    {
        "version": 3,
        "settings": {
            "blocks": {
                "sf-icon-manager/svg-icon": {
                    "dimensions": {
                        "dimensionSizes": [
                            { "name": "S", "slug": "s", "size": "32px" },
                            { "name": "L", "slug": "l", "size": "64px" }
                        ],
                        "width": true
                    }
                }
            }
        }
    }

With presets the panel shows a slider that moves across the preset sizes, like the core Icon block. A toggle next to it switches to a custom value input with a slider, using the allowed units (`spacing.units`). `dimensions.width: false` disables sizing entirely, so the block always renders at its default size.

== Screenshots ==

1. Symbol picker in the Gutenberg editor with a live preview of all icons from the sprite file.

== Development ==

Development is done on [GitHub](https://github.com/svgforge/sf-icon-manager).

== Changelog ==

= 0.5.0 =
* `sfim_get_icon()` renders a sprite icon as an `<svg><use>` fragment from theme code, for classic themes and custom PHP (size, colors, padding, label, optional link and theme presets).
* Icon picker: new view-size toggle between Normal and Large (double size) icon previews.
* Icon picker: toolbar buttons are now reachable with the keyboard and focus the button itself.
* Icon picker: the toolbar stays visible while scrolling and the dialog no longer shows a second scrollbar.

= 0.4.0 =
* Removed the experimental WordPress 7.1 native icon integration: core's SVG sanitizer was too strict for real sprite libraries.
* WordPress' own Icon block can now be removed with a checkbox ("Disable the WordPress core Icon block"). It only affects the editor, not the frontend.
* Uninstalling the plugin now also removes its own settings. The uploaded sprite file is kept.
* Readme leads with the plugin's advantages: no external CSS or JavaScript on the frontend, W3C-standard SVG fragments.
* Updated npm dependencies; the test suite now runs on Vitest.

= 0.3.4 =
* Readme: clarified that the sprite is a static SVG fragment file loaded by the browser via `<use>` (no JS on the frontend), added the custom icon sets tutorial link and a "Cache friendly" selling point.
* High-resolution plugin screenshot.

= 0.3.3 =
* `Tested up to: 7.1` (wp.org plugin check rejects minor versions).

= 0.3.2 =
* Admin stylesheet enqueued via `wp_enqueue_style`.
* `composer.json` ships in the plugin ZIP (required when `vendor/` exists).
* Test suite upgraded to WordPress 7.1.1 with PHPUnit 12.

= 0.3.1 =
* Admin UI branded as "SVG Forge Icon Manager" (settings menu, page title, native icons collection label); translations regenerated.
* `/i.svg` no longer cached immutably: the local-file branch revalidates on every request (`no-cache, must-revalidate`) with real `304 Not Modified` responses, so sprite changes are picked up immediately.
* TypeScript sources, test files and type declarations no longer ship in the production plugin ZIP.

= 0.3.0 =
* Short URL `/i.svg` for the sprite, opt-in via the `sfim_short_url` filter.
* Stricter upload sanitization (allowlist via enshrined/svg-sanitize instead of regex); automated dev releases from `main`; the plugin ZIP ships its vendored dependencies.
* Type declarations across the source (PHP 8.3+); padding applies to the SVG element, not the wrapper.
* New `docs/api.md` API reference; settings page shows the short URL under "Active sprite file".

= 0.2.1 =
* wp.org compliance: escaped SVG output with an input allowlist, direct-access guard in the render template, readme and changelog cleanup.
* Remove the now-discouraged `load_plugin_textdomain()` call; WordPress loads translations for the plugin slug automatically.

= 0.2.0 =
* WordPress 7.1 native icon integration (experimental): symbols of the configured sprite are registered as an `sf-icon-manager` icon collection (setting "WordPress native icon integration", default Off).
* New setting mode "Off + disable core Icon block" deregisters the built-in Icon block in the editor (including in content) and its frontend rendering.
* Registration is lazy: it only runs when needed (core Icon block or REST), keeping page-load cost independent of the icon count.

= 0.1.0 =
* Initial release.
