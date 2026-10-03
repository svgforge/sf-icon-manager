# SVG Forge Icon Manager

Gutenberg plugin that inserts SVG icons from a central SVG sprite file (`ico.svg`) via `<use>` and links them. The sprite file can be uploaded directly from a settings page as an SVG fragment library or [theme override](#override-with-a-filter-recommended-git-versionable).

## Why another icon plugin?

- **A W3C standard.** Every icon is referenced with the `<use>` element ([SVG 2](https://www.w3.org/TR/SVG2/struct.html#UseElement)). All current browsers support it — no polyfill, no build step.
- **No frontend cost.** The plugin loads no external CSS or JavaScript. The browser fetches the sprite once and renders every icon from it.
- **One cacheable file.** A static SVG file, fetched once and reused by every icon on the site.
- **Full SVG survives.** Stroke icons, gradients, `currentColor`, inline styles and custom `viewBox` values. WordPress 7.1's sanitizer allows only `<svg>`, `<path>` and `<polygon>` and breaks most stroke-based sets.

![SVG Forge Icon Manager](docs/screen.png)

## Who is this plugin for?

Developers who maintain their own SVG sprite file. Every icon must exist as a `<symbol>` in a sprite you build and version — there is no icon manager UI. The picker then lets you choose icons from that sprite in the editor.

WordPress 7.1 ships its own icon system (`wp_register_icon()`, `wp_get_icon()`). That is enough for a few icons registered in PHP. If you run a sprite, use this plugin: no frontend code, and it works on WordPress 6.6 and later.

## Features

- Settings page to upload the sprite file (`.svg` or `.svgz`)
- Uploaded sprites are sanitized with a strict allowlist: no scripts, no event handlers, no `foreignObject`, no unknown tags (same engine as [Safe SVG](https://wordpress.org/plugins/safe-svg/))
- Upload a sprite you already have, or build one with [svgforge-cli](https://github.com/svgforge/svgforge-cli/)
- `sfim_sprite_url` filter overrides the sprite file (e.g. `get_theme_file_uri()`)
- Symbol picker in the editor with a live preview
- Icons from sprite subdirectories are grouped
- Icons can be linked (new tab, `noopener`/`noreferrer`)
- Aria-label for screen readers
- Fill and stroke colour as well as size per block
- Server-side rendering with `get_block_wrapper_attributes()`
- Alignment, anchor and additional CSS classes
- Optional remove the core Icon block (`core/icon`) from the editor
- Icon helper for classic themes: `echo sfim_get_icon( 'close', [ 'size' => 20 ] );`

## Honest limitations

- Adding or editing icons means rebuilding the sprite file — typically with [svgforge](https://github.com/svgforge/svgforge/).

## Requirements

- WordPress >= 6.6
- PHP >= 8.3

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` (or install the ZIP from a GitHub release)
2. Activate the plugin
3. Under **Settings → SVG Forge Icon Manager**, upload the `ico.svg` file containing `<symbol id="my-icon" viewBox="0 0 24 24">…</symbol>` elements (or configure another source, see below)
4. In the editor, add the "SVG Icon" block and choose an icon

Composer users should read [Composer installation](docs/composer-installation.md).

## Sprite file configuration

### Generating a sprite with svgforge-cli

Install [svgforge-cli](https://github.com/svgforge/svgforge-cli/) and build a valid `ico.svg` sprite (with `<symbol id="…" viewBox="…">` elements) from a folder of SVG icons. If you have no sprite yet, this [tutorial walks you through building your own icon set](https://svgforge.github.io/blog/posts/custom-icon-sets/) step by step:

```bash
npm install --global @svgforge/svgforge-cli
svgforge --symbol --dest=out 'assets/./**/*.svg'
```

svgforge-cli sanitizes the SVG (stripping scripts, event handlers and `javascript:` links) and optimizes the output with svgo. The `./` keeps the relative paths, so files in subdirectories become `directory--filename` IDs — each directory is then a filter group in the icon picker. The generated sprite can be uploaded via the settings page or referenced from the theme via the `sfim_sprite_url` filter, see below.

The SVG sprite file is resolved in this order (the first existing source wins):

1. **Filter `sfim_sprite_url`** – the canonical override (theme file, CDN). **Has priority.**
2. **Upload from settings** – file uploaded via the settings page.
3. **Fallback `sprite.svg`** bundled with the plugin (`wp-content/plugins/sf-icon-manager/sprite.svg`).

### Override with a filter (recommended, Git-versionable)

In your theme's `functions.php`, return the URL of your own sprite file — the file then lives in the theme repo and is versioned with the theme:

```php
add_filter( 'sfim_sprite_url', fn () => get_theme_file_uri( 'assets/ico.svg' ) );
```

or point it at a CDN:

```php
add_filter( 'sfim_sprite_url', fn () => 'https://cdn.example.com/icons/ico.svg' );
```

The filter is the only supported override mechanism and wins over everything, including a backend upload.

## Icons in theme templates

Classic themes (and any other custom PHP) can print icons from the sprite without using the block:

```php
// Decorative icon: hidden from screen readers, sizes with the font (1em).
echo sfim_get_icon( 'close' );

// Sized, coloured, labelled and linked.
echo sfim_get_icon( 'close', [
	'size'  => 20,
	'label' => 'Close',
	'link'  => get_permalink(),
] );
```

`sfim_get_icon()` returns the same `<svg><use>` fragment the block renders, so icons look identical in a template and in post content. It is a plain function of the plugin — no `require`, no `use`, just call it. Useful arguments: `size` (or `width`/`height`), `color`, `background`, `padding`, `class`, `label`, `link`, `target` and `rel`. Colors and spacings accept the same theme presets as the block (`'vivid-red'`, `'var:preset|spacing|30'`).

Vertical alignment next to text is up to the theme, as usual for inline SVG:

```css
.my-icon {
	vertical-align: -0.15em;
}
```

Full argument list: [docs/api.md](docs/api.md#icon-markup-classic-themes).

## Hide the WordPress core Icon block

The built-in `core/icon` block takes its icons from the WordPress icon registry, not from your sprite file. On the settings page you can therefore remove it from the editing backend:

```text
Settings → SVG Forge Icon Manager → WordPress core Icon block → Disable the WordPress core Icon block
```

When enabled, `core/icon` is removed server-side in the backend (`unregister_block_type()`, so it no longer appears in the block editor settings or in the block-types REST API) and in the block editor itself (`wp.blocks.unregisterBlockType()`). This is a backend-only switch: the frontend is not touched, and block markup that is already stored in content keeps rendering as before. Switch the option back on at any time. The SVG Icon block is unaffected.

## Block settings in theme.json

Colors follow the standard Gutenberg block color controls (`supports.color`, like the core Icon block): the icon is rendered with `fill: currentColor`, so the **Color** panel ("Text" – the icon color) recolors monochrome icons. Like the core Icon block, both **Color** and **Background** are applied to the SVG element itself, not to the block row. To disable the Color/Background panels for the block entirely, set it in your theme:

```json
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
```

Multi-color sprite icons (e.g. Tango icon sets) keep their baked-in colors — recoloring has no visible effect on them, exactly like the core Icon block. Monochrome icons (fill `currentColor` / no fixed fill) follow the chosen color.

The **Size** is the standard Gutenberg Dimensions panel (`supports.dimensions.width`, like the core Icon block): the block is square, and size presets come from the theme via the standard `dimensions.dimensionSizes` per block:

```json
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
```

With presets the panel shows a slider that moves across the preset sizes (like the core Icon block). A toggle next to it switches to a custom value input with a slider, using the allowed units (`spacing.units`). `setting.dimensions.width: false` disables sizing entirely, so the block always renders at its default size.

## Documentation

Developer and maintenance topics are split into `docs/`:

- [Composer installation](docs/composer-installation.md) – install the plugin via Composer (GitHub repo or WP Packages)
- [Translations](docs/translations.md) – extract, translate and build the `languages/` files
- [Release & WordPress.org](docs/release.md) – automatic release workflow and publishing requirements
- [Development](docs/development.md) – build scripts, tests and project structure

## License

MIT, see [LICENSE](LICENSE).