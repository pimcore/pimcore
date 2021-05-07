<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Webservice;

/**
 * @deprecated
 */
interface IdMapperInterface
{
    /**
     * @deprecated
     *
     * @param string $type
     * @param int $id
     *
     * @return int
     */
    public function getMappedId(string $type, int $id): int;

    /**
     * @deprecated
     *
     * @param string $sourceType
     * @param int $sourceId
     * @param string $destinationType
     * @param int $destinationId
     */
    public function recordMappingFailure(string $sourceType, int $sourceId, string $destinationType, int $destinationId): void;

    /**
     * @deprecated
     *
     * @return bool
     */
    public function ignoreMappingFailures(): bool;
}
