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

namespace Pimcore\Model\Element;

interface ElementDumpStateInterface
{
    public const DUMP_STATE_PROPERTY_NAME = '_fulldump';

    /**
     * Set to true to indicate that we are about to serialize the version data.
     *
     */
    public function setInDumpState(bool $dumpState): void;

    public function isInDumpState(): bool;
}
