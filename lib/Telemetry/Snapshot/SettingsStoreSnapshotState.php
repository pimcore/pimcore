<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Telemetry\Snapshot;

use Pimcore\Model\Tool\SettingsStore;

/**
 * Stores the last-snapshot timestamp in the Pimcore settings-store (no extra table needed).
 *
 * @internal
 */
final class SettingsStoreSnapshotState implements TelemetrySnapshotStateInterface
{
    private const SETTING_ID = 'telemetry.last_snapshot_at';

    private const SCOPE = 'pimcore_telemetry';

    public function getLastSnapshotAt(): ?int
    {
        $entry = SettingsStore::get(self::SETTING_ID, self::SCOPE);

        return $entry === null ? null : (int) $entry->getData();
    }

    public function markSnapshotTaken(int $timestamp): void
    {
        SettingsStore::set(self::SETTING_ID, $timestamp, SettingsStore::TYPE_INTEGER, self::SCOPE);
    }
}
