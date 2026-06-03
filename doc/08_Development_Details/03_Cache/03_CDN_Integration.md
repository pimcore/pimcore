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
| `FASTLY_API_BASE_URL` | no          | `https://api.fastly.com` (default) | Override the Fastly API base URL if your environment needs a custom API endpoint/proxy.                                                                       |
| `CDN_BASE_URL`        | no          | `https://cdn.example.com`        | Public base URL of the CDN. Required when original assets are served statically and must be purged by URL — see [Original Assets](#original-assets). |

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

…but this only reaches the cached object if `/var/assets/...` responses pass
through PHP. See [Original Assets](#original-assets).

## Cookie Stripping

Pimcore's personalization bundle sets cookies (`_pc_tss`, `_pc_tvs`) on every
response, including assets. Most CDNs refuse to cache responses that carry a
`Set-Cookie` header. `CdnAssetCookieStripperListener` runs at priority `-200`
(after the targeting listener at `-115`) and removes all cookies from
- Thumbnails: `(?:^|/)(image|video)-thumb__\d+__([a-zA-Z0-9_\-]+)/`
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

## Original Assets

For original assets (`/var/assets/...`) there are two valid operation modes.

### Mode A: Tag-based purge for originals

Use this mode if you want `asset-path-{hash}` surrogate tags to invalidate
cached original assets.

Requirement: original asset requests must be handled by PHP on cache fill,
not served directly as static files. If the web server serves the file from
disk before Symfony runs, `CdnSurrogateKeyListener` cannot emit
`Surrogate-Key`/`Cache-Tag` and tag purge has nothing to target.

For Upsun/Platform.sh style routing, configure `/var/assets` to pass through
`/index.php` and avoid static short-circuiting for these paths.

### Mode B: URL purge for originals (static serving)

Use this mode if you keep `/var/assets` as static-file serving.

In this case, original responses usually do not carry CDN tags. Configure
`CDN_BASE_URL` so `CdnPurgeListener` dispatches `PurgeCdnUrlMessage` on
asset update/delete (including old URL on rename/move). Fastly then issues a
direct `PURGE https://cdn.example.com/var/assets/...` by URL.

If `CDN_BASE_URL` is not set in static mode, originals refresh only when TTL
expires naturally.

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

Register it as a service and tag it with `pimcore.cdn.purge_client`, setting the
`provider` attribute to the identifier you will use in `CDN_PROVIDER`:

```yaml
services:
    App\Cdn\MyCdnPurgeClient:
        tags:
            - { name: pimcore.cdn.purge_client, provider: mycdn }
```

`CdnPurgeClientRegistry` collects every tagged client and lazily resolves the one
whose `provider` matches `CDN_PROVIDER` at runtime. You do **not** replace the
`Pimcore\Cdn\PurgeClientInterface` alias — it points at the registry, which does
the selection. The Fastly implementation in `bundles/CoreBundle/config/cdn.yaml`
(tagged via the `#[AutoconfigureTag]` attribute on `FastlyPurgeClient`) is a
reference template.

> **Multi-provider selection** is controlled by `CDN_PROVIDER` and the `pimcore.cdn.purge_client` service tags (the registry resolves the selected provider lazily).

## Verification Guidance

For deployment verification, test against your target CDN endpoint and check:

1. `Cache-Tag`/`Surrogate-Key` presence on 2xx thumbnail responses.
2. Expected behavior for originals based on your selected mode in
   [Original Assets](#original-assets).
3. Messenger queue health for `pimcore_cdn_purge` and
   `pimcore_cdn_purge_failed`.
