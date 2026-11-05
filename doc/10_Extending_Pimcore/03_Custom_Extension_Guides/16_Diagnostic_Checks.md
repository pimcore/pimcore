---
title: Diagnostic Checks
description: Ship a read-only diagnostic check from your bundle that the Pimcore diagnostics bundle collects.
---

# Diagnostic Checks

A diagnostic check is a small, read-only service that judges one aspect of an instance (for example a queue backlog or
a configuration value) and reports fixed-text findings and numeric metrics. Any bundle can provide one by implementing
`Pimcore\Contracts\Diagnostics\CheckInterface`. The checks are collected by the Pimcore diagnostics bundle when it is
installed; without it, your check is never run and costs nothing.

A check must be safe to expose to support teams:

- It never changes anything on the instance.
- It never returns secrets (credentials, tokens, keys, DSNs) or personal data.
- Every finding message is a fixed text. No message, subject or reason may embed a value read from the instance such as
  a host, a path, query text or an exception message.

## Write a Check

Implement the interface and declare the data the check delivers with the `#[DataContract]` attribute. Prefix the id
with a short name of your bundle so ids from different bundles cannot clash.

```php
<?php

namespace App\Diagnostics;

use Pimcore\Contracts\Diagnostics\CheckInterface;
use Pimcore\Contracts\Diagnostics\CheckResult;
use Pimcore\Contracts\Diagnostics\DataClass;
use Pimcore\Contracts\Diagnostics\DataContract;
use Pimcore\Contracts\Diagnostics\Finding;
use Pimcore\Contracts\Diagnostics\NotAvailable;
use Pimcore\Contracts\Diagnostics\Severity;

#[DataContract(
    returns: [DataClass::Metric],
    neverReturns: ['row contents of the queue table'],
    sources: ['table my_bundle_queue'],
    doc: 'docs/Diagnostics.md#queue-backlog',
)]
final readonly class QueueBacklogCheck implements CheckInterface
{
    public function __construct(private QueueStatistics $statistics)
    {
    }

    public function getId(): string
    {
        return 'my_bundle.queue_backlog';
    }

    public function run(): CheckResult
    {
        $oldestAgeSeconds = $this->statistics->oldestEntryAgeSeconds();
        if ($oldestAgeSeconds === null) {
            return new CheckResult(notAvailable: [new NotAvailable('my_bundle_queue', 'not_readable')]);
        }

        $findings = [];
        if ($oldestAgeSeconds > 3600) {
            $findings[] = new Finding(
                'queue_backlog',
                Severity::Warning,
                'The oldest queue entry has been waiting for more than one hour.',
                'my_bundle_queue',
            );
        }

        return new CheckResult(
            findings: $findings,
            metrics: ['oldest_entry_age_seconds' => $oldestAgeSeconds],
        );
    }
}
```

No service configuration is needed: the diagnostics bundle registers `CheckInterface` for autoconfiguration, so every
service implementing it receives the tag `CheckInterface::TAG` (`pimcore.diagnostics.check`) as soon as that bundle is
installed. Core itself registers nothing.

## Findings, Not Available and Severity

- `Finding` has a stable `code`, a `Severity`, a fixed `message` and an optional `subject` that names *what* is
  affected (a setting, a table), never a value read from the instance.
- `Severity::Info` is an observation, `Severity::Warning` needs attention, `Severity::Critical` means something is
  broken or about to break.
- `NotAvailable` states that the check could not determine something (for example a missing privilege). Use a short
  snake_case reason such as `not_readable`. Prefer it over guessing.
- `metrics` holds scalars only (`bool`, `int`, `float`, `string`, `null`).

## The Data Contract

`#[DataContract]` is mandatory. Its `returns` list must contain at least one `DataClass` and `doc` must point to the
description of the check: an anchor, a relative path with anchor into your bundle's documentation, or a URL. The
diagnostics bundle verifies the attribute when the container is built, so a check without a valid contract fails the
build instead of running unnoticed.

There is deliberately no `DataClass` for secrets. Checks declaring `DataClass::Text` or `DataClass::PersonalData`
are not run by the diagnostics overview; it lists them as not available. Such data belongs in a dedicated Studio MCP
tool instead.

## Supporting Older Pimcore Versions

The contract ships with Pimcore 2026.4. A bundle that must also run on an earlier version guards the registration of
its check and keeps the check class out of any auto-scanned service resource, so it is never loaded without the
interface:

```php
use Pimcore\Contracts\Diagnostics\CheckInterface;

if (interface_exists(CheckInterface::class)) {
    $container->register(QueueBacklogCheck::class)->setAutowired(true)->setAutoconfigured(true);
}
```

Do this in your bundle's extension and exclude the check from the resource glob of your `services.yaml`.
