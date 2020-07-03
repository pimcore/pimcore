<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\Storage\Traits;

trait TimestampsTrait
{
    /**
     * @param \DateTimeInterface|null $createdAt
     * @param \DateTimeInterface|null $updatedAt
     *
     * @return \DateTimeInterface[]
     */
    private function normalizeTimestamps(\DateTimeInterface $createdAt = null, \DateTimeInterface $updatedAt = null): array
    {
        $now = new \DateTimeImmutable();

        $timestamps = [
            'createdAt' => $now,
            'updatedAt' => $now,
        ];

        if (null !== $createdAt) {
            $timestamps['createdAt'] = $createdAt;
        }

        if (null !== $updatedAt) {
            $timestamps['updatedAt'] = $updatedAt;
        }

        return $timestamps;
    }
}
