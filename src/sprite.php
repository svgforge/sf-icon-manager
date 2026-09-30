<?php

/**
 * Shared SVG sprite helpers (frontend + admin): the uploaded sprite option
 * plus reading and inspecting the active sprite file.
 *
 * @package sf-icon-manager
 */
defined('ABSPATH') || exit;

/**
 * Options key for the uploaded SVG sprite file.
 */
const SFIM_SPRITE_OPTION = 'sfim_sprite';

/**
 * Returns the stored data of the uploaded SVG sprite file.
 *
 * @return array{url: string, path: string, name: string, time: int, symbols: int}|array{}
 */
function sfim_uploaded_sprite_data(): array
{
    $data = get_option(SFIM_SPRITE_OPTION, []);

    if (! is_array($data) || ! isset($data['url'], $data['path'])) {
        return [];
    }

    return $data;
}

/**
 * Returns the URL of the uploaded SVG sprite file ('' when none exists).
 *
 * @return string
 */
function sfim_uploaded_sprite_url(): string
{
    $data = sfim_uploaded_sprite_data();

    return $data['url'] ?? '';
}

/**
 * Maps a URL to a local filesystem path when WordPress serves the file itself.
 *
 * @param string $url Sprite URL.
 * @return string Local path, or '' when the URL points elsewhere (e.g. a CDN).
 */
function sfim_url_to_path(string $url): string
{
    $url = (string) $url;

    if ($url === '') {
        return '';
    }

    $home   = wp_parse_url(home_url());
    $parsed = wp_parse_url($url);
    $host   = strtolower((string) ($parsed['host'] ?? ''));

    if ($host !== '' && strtolower((string) ($home['host'] ?? '')) !== $host) {
        return '';
    }

    $path = urldecode((string) ($parsed['path'] ?? ''));

    if ($path === '' || str_ends_with($path, '/')) {
        return '';
    }

    foreach ([untrailingslashit(WP_CONTENT_DIR), untrailingslashit(ABSPATH)] as $base) {
        $candidate = wp_normalize_path($base . '/' . ltrim($path, '/'));

        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }

    return '';
}

/**
 * Returns the raw SVG markup of the configured sprite.
 *
 * @return string Sprite markup, or '' when the sprite is not readable.
 */
function sfim_sprite_content(): string
{
    $sprite = sfim_current_sprite();

    if ($sprite['path'] !== '' && is_readable($sprite['path'])) {
        return (string) file_get_contents($sprite['path']);
    }

    if ('filter' === $sprite['source'] && $sprite['url'] !== '') {
        $response = wp_remote_get($sprite['url'], ['timeout' => 5]);

        if (! is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);

            if ($body !== '') {
                return $body;
            }
        }
    }

    return '';
}

/**
 * Lists every symbol id of the active sprite.
 *
 * This enumerates the raw sprite symbols without applying an SVG allowlist —
 * it is meant for consumers that render fragments via
 * <use href="sprite.svg#id">, where the browser resolves the full,
 * unrestricted symbol itself.
 *
 * @return string[] Symbol ids, sorted alphabetically.
 */
function sfim_sprite_symbols(): array
{
    $svg = sfim_sprite_content();

    if (! is_string($svg) || $svg === '' || ! class_exists('DOMDocument')) {
        return [];
    }

    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded   = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (! $loaded) {
        return [];
    }

    $xpath   = new DOMXPath($document);
    $symbols = $xpath->query('//*[local-name()="symbol"]');

    if (false === $symbols) {
        return [];
    }

    $ids = [];

    foreach ($symbols as $symbol) {
        $id = (string) $symbol->getAttribute('id');

        if ($id !== '') {
            $ids[$id] = true;
        }
    }

    $ids = array_keys($ids);
    sort($ids);

    return $ids;
}
