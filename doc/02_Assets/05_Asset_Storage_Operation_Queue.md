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
`asset`, `thumbnail`, and `asset_cache`.

- **Folder moves** still try the storage's native rename first. Backends with real
  directory rename (local filesystem, SFTP) keep behaving exactly as before — no queue
  row is created. Only when the backend cannot rename (object storage, which throws a
  Flysystem `UnableToMoveFile` error) is the move recorded as a queue row instead,
  making the move an O(1) database operation regardless of folder size.
- **Folder deletes** with existing content are always deferred via a tombstone row, on
  every backend. There is no native-failure signal for deletes the way there is for
  moves, and a deferred local delete is harmless.

While a queue row is pending, the storage adapter transparently resolves reads,
existence checks, metadata lookups, and directory listings by checking the literal path
first and then the mapped (pre-move) candidate paths. Writes always go to the literal
(post-move) path.

Enable it when asset folders on an object storage backend are moved or deleted often
enough, or are large enough, that the synchronous per-object cost is a problem. Folder
*moves* on a local filesystem or SFTP backend are unaffected — those backends always
succeed at a native rename, so no move row is ever created. Folder *deletes*, however,
are always deferred via a tombstone row on every backend, including local filesystem
and SFTP — enabling the flag means the processing command's cron obligation (see below)
applies even to local/SFTP-only installs.

## Enabling

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

- Reads, existence checks, metadata, and listings performed through Pimcore (the PHP
  API, the asset delivery pipeline, Pimcore Studio) resolve transparently through the
  storage adapter, whether the queried path is the pre-move or post-move location.
- The physical storage layout only converges to match the logical (post-move) paths
  once the processing command runs.
- For deletes, the content is only physically removed from storage at processing time.
  It is unreadable through Pimcore immediately (the queue row makes it disappear from
  reads), but it still exists physically in the storage backend until processed. **If a
  deletion is compliance-driven (e.g. a data-subject deletion request), run
  `pimcore:assets:storage-queue:process` immediately rather than waiting for the next
  scheduled run.**

## Caveats

- **Direct bucket access:** external consumers that address the storage bucket
  directly by tree-path keys (bypassing Pimcore) see the pre-move physical layout for
  any folder with a pending move, until it is processed.
- **Folder name re-use:** re-using a folder name that was just deleted or moved away is
  safe — the timestamp guard on processing ensures new content written into the re-used
  name is never overwritten or touched by the deferred operation. However, the old
  physical namespace (the objects at the pre-move/pre-delete keys) persists in the
  storage backend until the next processing run.
- **Double round-trips on reads:** reads inside a subtree that has pending operations
  cost roughly twice the normal number of storage round-trips, because the adapter has
  to check the literal path and then the mapped candidate paths. This is why processing
  promptly (rather than only nightly) matters for subtrees under sustained read load.
- **Thumbnail temp-file cleanup:** `Asset\Thumbnail` temporary-file cleanup may list
  physical (pre-move) paths while operations on that subtree are pending. This
  converges once the queue is processed; no manual action is needed.
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
