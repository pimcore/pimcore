---
title: CDN Integration
description: Integrate Pimcore with a tag-purge capable CDN (Fastly) to cache and invalidate asset and thumbnail responses at the edge.
---

# CDN Integration

Pimcore can integrate with a CDN that supports tag-based purging. The bundled
implementation targets [Fastly](https://www.fastly.com/), and the architecture
allows other providers to be added by implementing
`Pimcore\Cdn\PurgeClientInterface`.

This document describes the **cache-proxy** integration: the CDN sits in front
of Pimcore, caches asset and thumbnail responses, and is invalidated when
assets or thumbnail configurations change. Image-optimization features
(Fastly IO, Cloudflare Polish, Imgix, …) are out of scope for this phase.

## Quick Start

Set these environment variables on your runtime:

| Variable              | Required    | Example                          | Purpose                                                                                                                                                       |
|-----------------------|-------------|----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `CDN_PROVIDER`        | yes         | `fastly`                         | Activates the CDN listeners. Empty/unset disables all CDN behavior — Pimcore will not emit cache tags or dispatch purges.                                     |
| `FASTLY_API_TOKEN`    | for Fastly  | `xxxxxxxx`                       | Fastly API token with the `purge` scope.                                                                                                                      |
| `FASTLY_API_SERVICE`  | for Fastly  | `abc123…`                        | Fastly service ID.                                                                                                                                            |
| `FASTLY_API_BASE_URL` | no          | `https://api.fastly.com` (default) | Override the Fastly API base URL (useful for testing against a mock such as WireMock).                                                                       |
| `CDN_BASE_URL`        | no          | `https://cdn.example.com`        | Public base URL of the CDN. Required only if you need URL-based purges of original (non-thumbnail) asset URLs — see [Original Assets](#original-assets-limitation). |

Once `CDN_PROVIDER` is set, three event listeners activate:

1. `CdnSurrogateKeyListener` — emits `Surrogate-Key` and `Cache-Tag` headers
   on thumbnail responses
2. `CdnAssetCookieStripperListener` — strips `Set-Cookie` from asset and
   thumbnail responses (priority `-200`, runs after personalization listeners)
3. `CdnPurgeListener` — dispatches purge messages on asset and
   thumbnail-config changes

When `CDN_PROVIDER` is empty, all three listeners short-circuit and the
application behaves exactly as it did before the integration was introduced.

## How Tagging Works

For each thumbnail response, `CdnSurrogateKeyListener` emits the following
tags (via both `Surrogate-Key` for Fastly and `Cache-Tag` for clients/proxies
that prefer the standard header):

- `asset-{id}` — invalidate every cached representation of this asset
  (every thumbnail variant of asset `{id}`)
- `thumb-{configName}` — invalidate every thumbnail using this config,
  across all assets
- `asset-{id}-thumb-{configName}` — invalidate this specific variant only

For original asset URLs (`/var/assets/...`) the same listener also computes:

- `asset-path-{12-char-sha256-prefix}` — the path-hash for tag-purging the
  original. The hash is the first 12 hex characters of
  `sha256('/var/assets' + asset_full_path)`.

…but see the [Original Assets limitation](#original-assets-limitation) below
for why the path-hash tag often cannot reach the actual cached object.

## Cookie Stripping

Pimcore's personalization bundle sets cookies (`_pc_tss`, `_pc_tvs`) on every
response, including assets. Most CDNs refuse to cache responses that carry a
`Set-Cookie` header. `CdnAssetCookieStripperListener` runs at priority `-200`
(after the targeting listener at `-115`) and removes all cookies from
responses whose path matches the thumbnail or original-asset patterns:

- Thumbnails: `^/(image|video)-thumb__\d+__([^/]+)/`
- Originals: `^/var/assets/`

When `CDN_PROVIDER` is empty, this listener is inert and cookies pass through
unchanged.

## Purge Mechanism

`CdnPurgeListener` subscribes to six events:

| Event                                       | Action                                                                                                |
|---------------------------------------------|-------------------------------------------------------------------------------------------------------|
| `AssetEvents::POST_UPDATE`                  | Dispatch `asset-{id}` and `asset-path-{newPathHash}`. If renamed/moved, also `asset-path-{oldPathHash}`. |
| `AssetEvents::POST_DELETE`                  | Same as above.                                                                                        |
| `ImageThumbnailConfigEvents::POST_UPDATE`   | Dispatch `thumb-{configName}` (global, across all assets).                                            |
| `ImageThumbnailConfigEvents::POST_DELETE`   | Same as above.                                                                                        |
| `VideoThumbnailConfigEvents::POST_UPDATE`   | Dispatch `thumb-{configName}` (global, across all assets).                                            |
| `VideoThumbnailConfigEvents::POST_DELETE`   | Same as above.                                                                                        |

Purges are dispatched as `PurgeCdnTagMessage` (and, if `CDN_BASE_URL` is set,
`PurgeCdnUrlMessage`) onto the `pimcore_cdn_purge` Symfony Messenger
transport. A worker consumes the queue and calls the configured
`PurgeClientInterface` implementation (currently `FastlyPurgeClient`).

> **Asset paths with special characters**
> Asset filenames may legitimately contain spaces or non-ASCII characters
> (e.g. `/Car Images/Mötley.jpg`). The listener percent-encodes each path
> segment when building the `PurgeCdnUrlMessage` URL, so the URL sent to the
> CDN matches the cache key the CDN actually stored for the browser-encoded
> request. Tag-based purges hash the decoded path on both sides and are
> unaffected.

### Worker Configuration

Add `pimcore_cdn_purge` to your `messenger:consume` worker setup:

```bash
bin/console messenger:consume pimcore_cdn_purge --memory-limit=250M --time-limit=3600
```

For inspecting failed purges, also consume the failure transport:

```bash
bin/console messenger:consume pimcore_cdn_purge_failed --memory-limit=128M --time-limit=3600
bin/console messenger:failed:show
```

### Retry Behavior

The `pimcore_cdn_purge` transport ships with the following retry policy in
`bundles/CoreBundle/config/pimcore/default.yaml`:

- `max_retries: 5`
- Exponential backoff: 2 s → 4 s → 8 s → 16 s → 32 s, capped at 60 s
- Failure transport: `pimcore_cdn_purge_failed`

A non-2xx HTTP response from Fastly causes `FastlyPurgeClient` to throw,
which lets Messenger retry the message. Network/transport failures (HTTP
client exceptions) also retry. After exhausting retries, the message lands in
`pimcore_cdn_purge_failed` for inspection or manual replay.

## Manual Purges

Use the `pimcore:cdn:purge` console command for recovery scenarios, deploy
hooks, content-migration scripts, or runbooks. Routine purges happen
automatically via the listener — you should not normally need this command.

```bash
# Purge all caches related to a specific asset (asset-{id} + asset-path-{hash})
bin/console pimcore:cdn:purge --asset 42

# Purge multiple assets in one call
bin/console pimcore:cdn:purge --asset 42 --asset 43

# Purge every thumbnail using a given config (thumb-{configName})
bin/console pimcore:cdn:purge --config product_detail

# Combine
bin/console pimcore:cdn:purge --asset 42 --config product_detail
```

> **Notes**
> - The command computes the `asset-path-{hash}` tag itself, so unknown asset
>   IDs are reported and skipped (only the `asset-{id}` thumbnail tag is
>   purged in that case).
> - Tags are deduplicated before being submitted (Fastly limits each batch
>   purge to 256 keys).
> - The command calls `PurgeClientInterface::purgeByTags()` directly and
>   does **not** go through the messenger queue, so retries do not apply.
> - "Purge everything" is intentionally not supported. Use your CDN
>   provider's admin panel or API for that operation.

## CDN Edge / VCL Requirements

The CDN edge must be configured to:

1. **Strip the `Cookie` request header before lookup** for asset paths,
   otherwise per-user cookies fragment the cache key and the cache hit rate
   collapses. A typical Fastly VCL snippet:

   ```vcl
   sub vcl_recv {
     if (req.url ~ "^/(image|video)-thumb__" ||
         req.url ~ "^/var/assets/" ||
         req.url ~ "\.(jpg|jpeg|png|gif|webp|svg|ico|css|js|mp4|webm|woff2?|ttf|eot)(\?|$)") {
         unset req.http.Cookie;
     }
   }
   ```

2. **Honor `Surrogate-Key`** as the cache-tag header. Fastly does this
   natively and strips the header from the downstream response. Other
   providers may need `Cache-Tag` instead — both headers are emitted, so the
   edge can use whichever it prefers.

3. **Accept `PURGE` requests** against asset URLs for `PurgeCdnUrlMessage`,
   and the Fastly batch-purge endpoint
   `POST /service/{service_id}/purge` (with `Surrogate-Key` header) for
   `PurgeCdnTagMessage`. Both are handled by `FastlyPurgeClient`.

A local development stack based on Varnish + WireMock is provided in
`tests/bin/docker-compose.yaml` and replicates the Fastly behavior end-to-end.

## Original Assets Limitation

Original (non-thumbnail) asset URLs such as `/var/assets/products/photo.jpg`
are typically served by nginx directly off disk via `try_files`. PHP — and
therefore `CdnSurrogateKeyListener` — never runs for these requests. As a
consequence:

- Originals **do not carry a `Cache-Tag` / `Surrogate-Key` header** at the
  edge.
- **Tag-based purge does not invalidate originals** — the
  `asset-path-{hash}` tag is computed but no cached object at the edge is
  ever labelled with it.

To work around this, set `CDN_BASE_URL` to your CDN's public origin.
`CdnPurgeListener` will then additionally dispatch `PurgeCdnUrlMessage` for
the original URL on every asset update/delete (and for the old URL after a
rename or move). The Fastly client issues a
`PURGE https://cdn.example.com/var/assets/...` against the asset URL, which
removes the cached object regardless of any surrogate-key state.

If `CDN_BASE_URL` is not set, originals will only refresh when their natural
TTL expires.

## Adding a New CDN Provider

Implement `Pimcore\Cdn\PurgeClientInterface`:

```php
<?php
namespace App\Cdn;

use Pimcore\Cdn\PurgeClientInterface;

final class MyCdnPurgeClient implements PurgeClientInterface
{
    public function purgeByTag(string $tag): void { /* ... */ }

    /** @param list<string> $tags */
    public function purgeByTags(array $tags): void { /* ... */ }

    public function purgeByUrl(string $url): void { /* ... */ }
}
```

Register it as a service and wire it as the `Pimcore\Cdn\PurgeClientInterface`
alias when `CDN_PROVIDER` is set to your provider's identifier. The Fastly
implementation in `bundles/CoreBundle/config/cdn.yaml` is a reference
template.

> **Multi-provider selection** is currently hard-wired to `fastly`. A
> generic provider-selector based on `CDN_PROVIDER` is planned for a future
> phase.

## Known Limitations (Phase 1)

- **Originals require `CDN_BASE_URL`** for purging — see
  [Original Assets](#original-assets-limitation).
- **No private/auth-aware caching**: any path matching the asset/thumbnail
  patterns is cached publicly. If you have private assets, gate them upstream
  of Pimcore or add path-based exclusions at the edge.
- **Signed URLs**: `Request::getPathInfo()` ignores query strings, so signed
  URLs with different signatures collide on the same surrogate key. Either
  exclude signed paths from caching or include the signature in the cache key
  via VCL.
- **`Cache-Tag` is not stripped at the edge**: asset IDs and thumbnail
  config names are visible to downstream clients. Consider stripping
  `Cache-Tag` (and any leaked `Surrogate-Key`) in your edge config if this
  is a concern.
- **Image-optimization mode** (Fastly IO, Cloudflare Polish, Imgix, …) is
  not yet supported. Pimcore continues to generate its own thumbnail variants
  and the CDN caches them as-is.
- **`CDN_PROVIDER` is wired specifically for `fastly`.** Other values are
  recognized by the generic listeners (tagging, cookie stripping, purge
  message dispatch) but require a custom `PurgeClientInterface`
  implementation to actually deliver the purges.

## Testing Locally

The repository includes a Varnish + WireMock stack in
`tests/bin/docker-compose.yaml` that mimics Fastly behavior end-to-end. It
is used by the integration test suite and can be brought up for manual
exploration:

```bash
cd tests/bin
docker compose up -d
```

WireMock records every API request issued against the mock Fastly endpoint,
which makes it straightforward to verify that a purge was dispatched with
the expected tags.
