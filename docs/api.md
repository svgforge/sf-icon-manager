# SVG Forge Icon Manager – API Reference

Public PHP functions, filters, and constants exposed by the SVG Forge Icon Manager plugin.

---

## Table of Contents

- [Sprite URL Resolution](#sprite-url-resolution)
- [Short URL (/i.svg)](#short-url-isvg)
- [Filters](#filters)
- [Block Attribute Resolvers](#block-attribute-resolvers)
- [Sprite Helpers](#sprite-helpers)
- [Icon Markup (Classic Themes)](#icon-markup-classic-themes)
- [Core Icon Block](#core-icon-block)
- [Constants](#constants)

---

## Sprite URL Resolution

### `sfim_current_sprite()`

**File:** `sf-icon-manager.php:64`

Resolves the active SVG sprite source. This is the core function that determines which sprite file is used across the entire plugin.

**Source priority:**

| Priority | Source | Condition |
|----------|--------|-----------|
| 1 | Filter `sfim_sprite_url` | Non-empty string returned |
| 2 | Uploaded file (Settings → SVG Forge Icon Manager) | Valid `sfim_sprite` option |
| 3 | Bundled fallback `sprite.svg` | File exists in plugin root |
| 4 | No sprite | — |

**Return value:**

```php
[
    'url'    => string,  // Absolute sprite URL (with cache-busting `?m=` for uploads)
    'path'   => string,  // Local filesystem path, or '' for remote/CDN URLs
    'source' => string,  // 'filter' | 'upload' | 'default' | 'none'
    'data'   => array,   // Upload metadata when source is 'upload', otherwise []
]
```

**Example:**

```php
$sprite = sfim_current_sprite();

if ($sprite['source'] === 'none') {
    // No sprite configured
}

// Use $sprite['url'] to build <use> references:
// <use href="sprite.svg#icon-name">
```

---

### `sfim_sprite_url()`

**File:** `sf-icon-manager.php:121`

Convenience wrapper — returns the URL string used in the rendered HTML.

With the short URL enabled (see the `sfim_short_url` filter below) it
returns the root-relative `/i.svg` (independent of the actual sprite source).
Otherwise it returns the result of `sfim_current_sprite()`.

This helps if you want to use icons directly in your custom template code.

```php
$url = sfim_sprite_url();
// Short URL off:  "https://example.com/wp-content/uploads/sprite.svg?m=1694000000"
// Short URL on:   "/i.svg"
// Enable with: add_filter('sfim_short_url', '__return_true');
```

> Note: the short URL assumes a root-level WordPress install. For WordPress
> in a subdirectory, the rewrite lives inside that subdirectory and the short
> URL becomes `/sub/dir/i.svg`.

---

## Short URL (/i.svg)

Enabled via code — there is no settings toggle:

```php
add_filter('sfim_short_url', '__return_true');
```

While enabled, every rendered icon references `/i.svg#symbol-id` instead of
the full sprite URL. Apache picks up the rewrite rule from `.htaccess` after
the rewrite rules are flushed; Nginx requires a short config block or symlink
(see [Rewrite rule](#rewrite-rule)).

The rewrite works for **any** sprite source — upload, bundled fallback, and
filter-sourced remote URLs.

### Filter `sfim_short_url`

**File:** `src/short-url.php:17`

Return a truthy value from this filter to enable the short URL. The return
value does not matter — the filter is a pure on/off switch that is evaluated
in memory only (no option, no database access).

```php
add_filter('sfim_short_url', '__return_true');

$enabled = sfim_short_url_enabled(); // true
```

### `sfim_short_url_request( array $query ): array`

**File:** `src/short-url.php:57`

Hooked on `request`. Intercepts requests for `/i.svg` before WordPress
resolves rewrite rules and tells WordPress to serve the sprite instead of
returning a 404.

```php
add_filter('request', 'sfim_short_url_request');
```

Typically you plug into this via the `sfim_short_url` filter only;
there is no reason to call it manually.

### `sfim_short_url_serve()`

**File:** `src/short-url.php:79`

Hooked on `template_redirect`. Serves the sprite file when the
`sfim_svg` query variable is set. Sets cache headers optimized for
Varnish/HTTP caching:

| Header | Local file | Remote (proxied) |
|--------|------------|-------------------|
| `Content-Type` | `image/svg+xml; charset=utf-8` | `image/svg+xml; charset=utf-8` |
| `Cache-Control` | `public, no-cache, must-revalidate` | `public, max-age=300` |
| `ETag` | `md5(path + filemtime)` | — |
| `Last-Modified` | file mtime | — |
| `Vary` | `Accept-Encoding` | `Accept-Encoding` |

**Cache invalidation:** `/i.svg` is a stable pointer to the *active* sprite,
so it must revalidate on every request. The `ETag`/`Last-Modified` pair is
derived from the file's modification time; a matching `If-None-Match` /
`If-Modified-Since` request gets a `304 Not Modified`, and a new sprite
upload (which changes the mtime) is picked up automatically — no manual
purge is required. Serving `immutable` here would wrongly pin the sprite
for a year and show stale icons until a hard refresh.

**Error handling:** returns a `404` (with `nocache_headers()`) when no
sprite is available.

### Rewrite rule

The plugin registers a WordPress rewrite rule:

```php
add_rewrite_rule('^i\.svg/?$', 'index.php?sfim_svg=1', 'top');
```

**Apache:** flush the rewrite rules once after enabling the filter so `.htaccess`
contains the rule — *Settings → Permalinks → Save* or `wp rewrite flush --hard`.

**Nginx:** add a `location` block to your server config:

```nginx
# Root-level WordPress
location = /i.svg {
    try_files /wp-content/uploads/sf-icon-manager/ico.svg /index.php?sfim_svg=1;
}

# WordPress in a subdirectory
location = /blog/i.svg {
    try_files /blog/wp-content/uploads/sf-icon-manager/ico.svg /blog/index.php?sfim_svg=1;
}
```

Alternatively, symlink the document root:

```bash
ln -sfn wp-content/uploads/sf-icon-manager/ico.svg i.svg
```

---

## Filters

### `sfim_sprite_url`

**File:** `sf-icon-manager.php:73`

Override the sprite source. Return a URL string to point the plugin at any sprite file.

- Takes priority over admin uploads and the bundled fallback.
- Supports local paths, CDN URLs, and theme-bundled sprites.
- The URL is used as-is — no cache-busting is added by the plugin.

**Usage:**

```php
// Point to a theme-bundled sprite
add_filter('sfim_sprite_url', function () {
    return get_theme_file_uri('assets/icons.svg');
});

// Point to a CDN-hosted sprite
add_filter('sfim_sprite_url', function () {
    return 'https://cdn.example.com/icons/sprite.svg';
});

// Dynamic sprite based on context
add_filter('sfim_sprite_url', function ($url) {
    if (is_page_template('templates/landing.php')) {
        return get_theme_file_uri('assets/landing-icons.svg');
    }
    return $url;
});
```

---

## Block Attribute Resolvers

These functions resolve Gutenberg's internal value formats (preset references, CSS custom properties) into usable CSS values. They are used by the server-side render callback but are also available for custom rendering.

### `sfim_resolve_color( string $value ): string`

**File:** `sf-icon-manager.php:139`

Resolves a Gutenberg block color value to a usable CSS color.

**Input formats handled:**

| Input | Output |
|-------|--------|
| `var:preset\|color\|vivid-red` | `var(--wp--preset--color--vivid-red)` |
| `vivid-red` (slug) | `var(--wp--preset--color--vivid-red)` |
| `#ff0000` | `#ff0000` (pass-through) |
| `rgb(255,0,0)` | `rgb(255,0,0)` (pass-through) |
| `var(--custom)` | `var(--custom)` (pass-through) |

```php
$color = sfim_resolve_color('vivid-red');
// → "var(--wp--preset--color--vivid-red)"

$color = sfim_resolve_color('#ff0000');
// → "#ff0000"
```

---

### `sfim_resolve_dimension( string $value, ?array $presets = null ): string`

**File:** `sf-icon-manager.php:168`

Resolves a Gutenberg dimension value to a usable CSS length.

**Resolution order:**

1. `var:preset|dimension|<slug>` — looks up presets from `theme.json`:
   - Per-block: `settings.blocks.sf-icon-manager/svg-icon.dimensions.dimensionSizes`
   - Global fallback: `settings.dimensions.dimensionSizes`
2. Raw CSS length (e.g. `42px`, `2em`) — passed through unchanged.

```php
$size = sfim_resolve_dimension('var:preset|dimension|icon-large');
// → "64px" (if theme.json defines slug "icon-large" → "64px")

$size = sfim_resolve_dimension('48px');
// → "48px"
```

**Parameter `$presets`:** Injectable array of dimension presets for unit testing. When `null`, the function reads from `theme.json` at runtime.

---

### `sfim_resolve_spacing( string $value ): string`

**File:** `sf-icon-manager.php:217`

Resolves a block spacing value to a usable CSS length.

| Input | Output |
|-------|--------|
| `var:preset\|spacing\|30` | `var(--wp--preset--spacing--30)` |
| `16px` | `16px` (pass-through) |

```php
$spacing = sfim_resolve_spacing('var:preset|spacing|30');
// → "var(--wp--preset--spacing--30)"
```

---

## Sprite Helpers

### `sfim_uploaded_sprite_data()`

**File:** `src/sprite.php:21`

Returns the stored upload data from the `sfim_sprite` option.

```php
$data = sfim_uploaded_sprite_data();

// Returns:
[
    'url'     => 'https://example.com/wp-content/uploads/sf-icon-manager/sprite.svg',
    'path'    => '/var/www/html/wp-content/uploads/sf-icon-manager/sprite.svg',
    'name'    => 'sprite.svg',
    'time'    => 1694000000,
    'symbols' => 142,
]
// or [] when no upload exists
```

---

### `sfim_uploaded_sprite_url()`

**File:** `src/sprite.php:37`

Returns just the URL of the uploaded sprite (`''` when none exists).

```php
$url = sfim_uploaded_sprite_url();
```

---

### `sfim_url_to_path( string $url ): string`

**File:** `src/sprite.php:50`

Maps a URL to a local filesystem path when WordPress serves the file itself. Checks against `WP_CONTENT_DIR` and `ABSPATH`. Returns `''` for cross-origin or CDN URLs.

```php
$path = sfim_url_to_path('https://example.com/wp-content/uploads/sprite.svg');
// → "/var/www/html/wp-content/uploads/sprite.svg"

$path = sfim_url_to_path('https://cdn.example.com/icons.svg');
// → '' (external)
```

---

### `sfim_sprite_content()`

**File:** `src/sprite.php:88`

Returns the raw SVG markup of the active sprite. Reads from the local file when possible; fetches via `wp_remote_get` for remote filter-sourced URLs.

```php
$svg = sfim_sprite_content();
// '<svg xmlns="http://www.w3.org/2000/svg">…</svg>'
```

---

### `sfim_sprite_symbols()`

**File:** `src/sprite.php:121`

Lists every `<symbol>` id from the active sprite. Returns the raw ids, sorted alphabetically — intended for `<use>` consumers where the browser resolves the full symbol.

```php
$ids = sfim_sprite_symbols();
// ['arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', …]
```

---

## Icon Markup (Classic Themes)

### `sfim_get_icon( string $id, array $args = [] ): string`

**File:** `src/icon.php:136`

Renders one sprite icon as an `<svg><use></use></svg>` fragment — the same
markup the SVG Icon block renders, so an icon looks identical in a template
and in post content. The return value is fully escaped and can be echoed
directly.

```php
// Decorative icon, sizes with the surrounding font (1em).
echo sfim_get_icon('close');

// Sized, coloured, labelled and linked.
echo sfim_get_icon('close', [
    'size'  => 20,
    'label' => 'Close',
    'link'  => get_permalink(),
]);
```

**Arguments:**

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `size` | string | `'1em'` | Width and height. A bare number gets `px` appended, so `'20'` and `'20px'` are the same. |
| `width` | string | `''` | Overrides `size` for the width. |
| `height` | string | `''` | Overrides `size` for the height. |
| `fill` | string | `'currentColor'` | Icon fill. `''` keeps the fill of the symbol itself (for multi-colour sprites). |
| `color` | string | `''` | Text color; accepts theme palette presets (`vivid-red`). |
| `background` | string | `''` | Background color; accepts theme palette presets. |
| `padding` | string or array | `''` | Shorthand (`'4px'`) or per side (`['top' => '4px', 'left' => '2em']`). |
| `class` | string or list | `''` | Extra class names on the `<svg>`, appended to `sfim-icon`. |
| `label` | string | `''` | Accessible name. Without it the icon is `aria-hidden`. |
| `link` | string | `''` | Wraps the icon in a link. |
| `target` | string | `''` | Link target: `_blank`, `_self`, `_parent` or `_top`; anything else is ignored. |
| `rel` | string | `''` | Link rel. `noopener noreferrer` are added automatically for `_blank`. |

**Return value:** the icon markup, or `''` when the id is empty or no sprite
is configured.

**Styling:** the fragment carries its own size, fill and colors inline, so it
needs no stylesheet — only the vertical alignment next to text is up to the
theme:

```css
.my-icon {
    vertical-align: -0.15em;
}
```

**Accessibility:** a labelled icon gets `role="img"` plus `aria-label`; a
labelled *linked* icon gets the label on the link and stays `aria-hidden`
itself — the same model the block uses.

---

### `sfim_get_icon_href( string $id ): string`

**File:** `src/icon.php:32`

Builds the `<use>` href for one symbol: the resolved sprite URL (short URL,
filter, upload or fallback) with the symbol as fragment. Returns a **raw,
unescaped** value — pass it through `esc_url()` when you print it yourself.
Returns `''` for an empty id or when no sprite is configured.

```php
echo '<svg style="width:20px;height:20px"><use href="'
    . esc_url(sfim_get_icon_href('close')) . '"></use></svg>';
```

A fragment in the sprite URL from the `sfim_sprite_url` filter wins, exactly as
in the block render. Symbol ids are percent-encoded, so ids with uppercase
letters or dots keep working (the block normalizes them with
`sanitize_key()`, because its ids come from post content).

---

### `sfim_icon_length( string $value ): string`

**File:** `src/icon.php:63`

Normalizes a size argument to a CSS length: a bare number gets `px` appended,
everything else is passed through.

```php
sfim_icon_length('20');     // '20px'
sfim_icon_length('2em');    // '2em'
sfim_icon_length('50%');    // '50%'
```

---

### `sfim_icon_padding_declarations( string|array $padding ): array`

**File:** `src/icon.php:81`

Converts the `padding` argument into CSS declarations. Useful when you build
an icon yourself and want the same padding handling.

```php
sfim_icon_padding_declarations('4px');
// ['padding' => '4px']

sfim_icon_padding_declarations(['top' => '4px', 'right' => '', 'left' => '2em']);
// ['padding-top' => '4px', 'padding-left' => '2em']
```

---

## Core Icon Block

Functions behind the "Disable the WordPress core Icon block" switch on the settings page. They remove WordPress' built-in `core/icon` block (which does not read from the sprite file) from the editing backend, so the SVG Icon block is the only icon block while editing. All of them are no-ops while the switch is off.

**Backend only.** `src/admin/hide-core-icon-block.php` is loaded for wp-admin requests and for REST requests (the block editor reads its settings through the REST API), never while a frontend page is rendered. Content that already contains `core/icon` therefore keeps its stored markup and its previous frontend output.

### `sfim_core_icon_block_hidden(): bool`

**File:** `src/admin/hide-core-icon-block.php:40`

Returns whether the core Icon block is hidden in the backend. The option holds `'0'` (default) or `'1'`; every other value counts as off.

```php
$hidden = sfim_core_icon_block_hidden();
// false by default
```

### `sfim_deregister_core_icon_block( mixed $block_name = 'core/icon' )`

**File:** `src/admin/hide-core-icon-block.php:59`

Deregisters a block type from the server-side block registry while the switch is on. Hooked late on `init`, `rest_api_init`, `admin_enqueue_scripts` and `enqueue_block_editor_assets`, because core registers block types lazily. The `$block_name` parameter is untyped on purpose: the function doubles as a hook callback, and those pass non-string values.

```php
sfim_deregister_core_icon_block('core/icon');
```

### `sfim_deny_core_icon_block_types( mixed $allowed ): bool|array|null`

**File:** `src/admin/hide-core-icon-block.php:92`

Filter callback on `allowed_block_types_all`: removes `core/icon` from an explicit allowlist, or from the full registry when none is set, so the block never shows up in the inserter.

### `sfim_hide_core_icon_block_editor_assets(): void`

**File:** `src/admin/hide-core-icon-block.php:125`

Enqueues `assets/js/unregister-icon-block.js` while the switch is on, so `wp.blocks.unregisterBlockType('core/icon')` also removes the client-side block type in the editor.

---

## Constants

| Constant | File | Value | Purpose |
|----------|------|-------|---------|
| `SFIM_PLUGIN_FILE` | `sf-icon-manager.php:17` | `__FILE__` | Path to the main plugin file |
| `SFIM_SPRITE_OPTION` | `src/sprite.php:14` | `'sfim_sprite'` | Option key for uploaded sprite data |
| `SFIM_HIDE_CORE_ICON_BLOCK_OPTION` | `src/admin/hide-core-icon-block.php:33` | `'sfim_hide_core_icon_block'` | Option key for the "hide core Icon block" switch ('0'/'1') |
