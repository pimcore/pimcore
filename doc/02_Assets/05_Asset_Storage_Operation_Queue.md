---
title: Asset Storage Operation Queue
description: Deferring physical asset folder moves and deletes on object storage backends that cannot rename directories natively.
---

# Asset Storage Operation Queue

By default, moving or deleting an asset folder performs the physical storage operation
(renaming or deleting every contained object) synchronously, as part of the request or job
that triggered it. On a local filesystem or SFTP backend this is a native directory
rename or delete and stays fast regardless of folder size. On object storage backends
(S3, Azure, GCS) there is no native directory rename: Flysystem has to copy and delete
every object individually, so moving a folder with many assets can take a long time and
block the request.

The asset storage operation queue is an opt-in feature that defers this physical work
instead of performing it synchronously. It covers the three asset-related storages:
`asset` (originals), `thumbnail`, and `asset_cache`. Thumbnails and asset_cache entries
are derived, regenerable content, so once a folder move falls back from a native rename,
they are handled differently from originals — see below.

- **Folder moves** always try the storage's native rename first, on every storage.
  Backends with real directory rename (local filesystem, SFTP) keep behaving exactly as
  before — no queue row is created, and thumbnails/asset_cache renditions are renamed
  along with the originals, so nothing needs to regenerate.
  Only when the backend cannot rename a folder natively (object storage, which throws a
  Flysystem `UnableToMoveFile` error) does behavior diverge by storage:
    - For the **`asset`** storage, the move is recorded as a Move queue row instead,
      making the move an O(1) database operation regardless of folder size. Reads
      transparently resolve through the pending row (pre-move or post-move path, either
      way) until it is processed.
    - For **`thumbnail`** and **`asset_cache`**, the existing renditions under the
      source prefix are not moved and their reads are not translated. Instead, the
      source prefix is tombstoned — recorded as a Delete row, swept by the processing
      command — and new renditions simply regenerate on demand at their new, post-move
      paths through Pimcore's standard deferred-thumbnail mechanism, the same as they
      would for a newly uploaded asset. There is no pending-window read translation for
      these two storages after a folder move: a thumbnail requested at its post-move
      path before the tombstone is swept is generated fresh, exactly as if the folder
      had never been moved.
- **Folder deletes** with existing content are always deferred via a tombstone row, on
  every backend and for all three storages. There is no native-failure signal for
  deletes the way there is for moves, and a deferred local delete is harmless.

While a Move row is pending (the `asset` storage only), the storage adapter transparently
resolves reads, existence checks, metadata lookups, and directory listings by checking
the literal path first and then the mapped (pre-move) candidate paths. Writes always go
to the literal (post-move) path. A pending Delete row (any storage, including the
tombstones `thumbnail`/`asset_cache` moves create) has no mapped candidate: the source
prefix's content stays reachable at its own, unmapped physical path until the row is
processed, and nothing resolves at the target — for thumbnails and asset_cache, a lookup
at the target simply misses and regenerates, the same as for any not-yet-generated
rendition.

Enable it when asset folders on an object storage backend are moved or deleted often
enough, or are large enough, that the synchronous per-object cost is a problem. Folder
*moves* on a local filesystem or SFTP backend are unaffected — those backends always
succeed at a native rename, so no move row is ever created. Folder *deletes*, however,
are always deferred via a tombstone row on every backend, including local filesystem
and SFTP — enabling the flag means the processing command's cron obligation (see below)
applies even to local/SFTP-only installs.

## Enabling

Setting up the feature takes four steps:

### 1. Create the queue table

The feature has no dedicated installer step or database migration — enabling the flag
does **not** create its table automatically. Create the table **before** enabling the flag —
with the flag on and the table missing, asset storage operations fail until it exists:

```sql
CREATE TABLE `asset_storage_operation_queue` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `storage` VARCHAR(50) NOT NULL,
    `operation` ENUM('move','delete') NOT NULL,
    `source_prefix` VARCHAR(765) NOT NULL,
    `target_prefix` VARCHAR(765) DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
```

This is the single source of truth for the table's schema — there is no migration or
install.sql entry to keep in sync with it. Running the commands below against a
database that doesn't have this table yet fails with a clear error pointing back at
this section, rather than a raw SQL error.

### 2. Enable the flag

Enable the feature with the `pimcore.assets.storage_operation_queue.enabled` configuration
option, which defaults to `false`:

```yaml
pimcore:
    assets:
        storage_operation_queue:
            enabled: true
```

When disabled (the default), asset storages behave exactly as they did before this
feature was introduced — there is no behavior change.

### 3. Verify the setup

```bash
bin/console pimcore:assets:storage-queue:status
```

reports `The storage operation queue is empty.` once the table exists and the flag is
enabled. If the table is missing, both this command and the processing command below
fail with `The storage operation queue table does not exist yet. Please check the
Asset Storage Operation Queue documentation for the required setup steps.` — re-run
step 2.

### 4. Schedule processing

Enabling the flag alone is not sufficient. Deferred operations only become permanent
once they are applied by:

```bash
bin/console pimcore:assets:storage-queue:process
```

**This command must be scheduled by the operator, for example as a nightly cron job
during low-traffic hours — nothing runs it automatically.** Options:

- `--id`: process only the given queue row.
- `--max-runtime`: stop cleanly after this many seconds; any unfinished rows stay
  queued for the next run.

A single run is guarded by a 24-hour, non-refreshing lock, so it is safe to schedule
the command frequently — a run that finds the lock held (a previous run still in
progress) or that reaches a clean `--max-runtime` timeout exits with code `0`. The
command exits with code `1` if any individual row fails to process. Rows are applied
oldest first, and each apply is timestamp-guarded: if a folder name was re-used and new
content was written into it after the original operation was queued, that new content
is never touched, and an existing target file or folder is never overwritten.

**The lock uses Symfony's default lock store, which is machine-local.** If the cron
could run on more than one host (e.g. multiple web/worker nodes behind a load
balancer), either schedule it on a single, fixed host, or configure a shared
`framework.lock` store (e.g. a database or Redis/Valkey store) so the lock is honored
across hosts.

To monitor whether the queue is being processed, use:

```bash
bin/console pimcore:assets:storage-queue:status
```

This prints a table of pending operations and exits with code `1` and a warning when a
row is older than `--warn-age` hours (default `48`) — the signal that the processing
cron is missing or has stopped running — or when pending rows exist while the feature
flag is disabled.

## Behavior During the Pending Window

Between the moment an operation is queued and the moment it is processed:

- For the `asset` storage's Move rows, reads, existence checks, metadata, and listings
  performed through Pimcore (the PHP API, the asset delivery pipeline, Pimcore Studio)
  resolve transparently through the storage adapter, whether the queried path is the
  pre-move or post-move location. For `thumbnail`/`asset_cache` tombstones created by a
  folder move, there is no such translation: content at the post-move path is generated
  fresh on first request, the same as for a newly uploaded asset.
- The physical storage layout only converges to match the logical (post-move) paths, and
  old thumbnail/asset_cache renditions are only physically removed, once the processing
  command runs.
- For deletes, the content is only physically removed from storage at processing time.
  It is unreadable through Pimcore immediately (the queue row makes it disappear from
  reads), but it still exists physically in the storage backend until processed. On
  local storage, the webserver serves asset originals directly from disk, so deleted
  originals also remain reachable at their direct URLs until the next processing run.
  **If a deletion is compliance-driven (e.g. a data-subject deletion request), run
  `pimcore:assets:storage-queue:process` immediately rather than waiting for the next
  scheduled run.**

## Frontend URLs

With `pimcore.assets.frontend_prefixes.source` configured, frontend URLs for original
asset files — `Asset::getFrontendFullPath()`, and thumbnails that pass through the
original file unmodified (for example an SVG thumbnail falling back to the source file)
— point straight at the storage (e.g. a CDN or bucket) rather than being served through
Pimcore. For such a URL under a pending move, the path is built from the still-valid
physical (pre-move) path instead of the logical (post-move) path, so it keeps resolving
correctly for the duration of the pending window, and automatically switches back to the
logical path once the move is processed.

`pimcore.assets.frontend_prefixes.thumbnail` URLs need no queue-awareness at all: a
thumbnail requested at its post-move path is simply a not-yet-generated thumbnail, no
different from one requested for a newly uploaded asset, and it is generated on the fly
through the normal deferred-thumbnail flow — for this to work,
`pimcore.assets.frontend_prefixes.thumbnail_deferred` must be routed to PHP, exactly as
it must be for any new asset. Installs that front original files with a CDN image
optimizer (transforming `frontend_prefixes.source` URLs on the fly, so no thumbnail
storage is ever written to by Pimcore) are entirely unaffected by any of this — there is
no thumbnail rendition to tombstone or regenerate.

An asset uploaded or replaced inside a moved folder during the pending window resolves to
its logical (post-move) URL right away, because writes always target the literal,
post-move key. A metadata-only edit to an asset that was already in the moved folder can
still 404 until the queue is processed — the same as it would without this feature.

While a processing run is under way, folders whose move has already completed briefly
keep resolving to their old physical URL until the whole operation finishes — schedule
processing during low-traffic windows to minimize the impact.

Without a configured prefix, frontend URLs are unaffected by this feature.

## Caveats

- **Direct bucket access:** external consumers that address the storage bucket
  directly by tree-path keys (bypassing Pimcore) see the pre-move physical layout for
  any folder with a pending move, until it is processed.
- **Folder name re-use:** re-using a folder name that was just deleted or moved away is
  safe — the timestamp guard on processing ensures new content written into the re-used
  name is never overwritten or touched by the deferred operation. However, the old
  physical namespace (the objects at the pre-move/pre-delete keys) persists in the
  storage backend until the next processing run.
- **Double round-trips on reads:** for the `asset` storage, reads inside a subtree that
  has a pending Move row cost roughly twice the normal number of storage round-trips,
  because the adapter has to check the literal path and then the mapped candidate paths.
  This is why processing promptly (rather than only nightly) matters for subtrees under
  sustained read load. Pending Delete rows (deletes on any storage, and the tombstones a
  folder move creates on `thumbnail`/`asset_cache`) do not have this cost — there is no
  mapped candidate to check.
- **Thumbnail/asset_cache regeneration cost:** on installs where Pimcore itself
  generates thumbnails (i.e. not offloaded to a CDN image optimizer acting on
  originals), moving a large folder on a backend without native rename causes every one
  of its thumbnails to regenerate on first request after the move, rather than being
  moved along with the originals. The cost (CPU time, and added latency on the first
  view of each asset) is spread out over demand rather than paid up front, but it is
  real cumulative cost across a large folder. Schedule large folder moves accordingly
  (e.g. outside peak traffic) or pre-warm thumbnails for critical folders immediately
  after moving them.
- **Thumbnail temp-file cleanup:** `Asset\Thumbnail` temporary-file cleanup may list
  physical (pre-move) paths while operations on that subtree are pending. This
  converges once the queue is processed; no manual action is needed.
- **Persisted or exported URLs:** a URL exported or persisted during a pending window
  (for example a CSV export embedding image URLs) may capture a physical (pre-move)
  path that stops resolving once the queue is processed. Re-export any long-lived
  artifact after the queue has fully drained if it needs to keep working.
- **Never disable the flag with pending rows:** do not disable
  `pimcore.assets.storage_operation_queue.enabled` while
  `pimcore:assets:storage-queue:status` still shows pending rows. Moved content becomes
  unreachable at its logical paths as soon as the queue-aware adapter is removed, until
  the flag is re-enabled and the queue is processed. Always drain the queue (run
  `pimcore:assets:storage-queue:process` until `status` reports it empty) before turning
  the feature off.

## Extension Point

Processing intentionally uses each affected storage adapter's own copy and delete
operations rather than a backend-optimized bulk API. The processor service is internal
and not currently replaceable — there is no public customization surface in this
version. Backend-optimized movers (for example a cloud provider's native server-side
batch copy/move API instead of per-object Flysystem calls) are a possible future
extension.
