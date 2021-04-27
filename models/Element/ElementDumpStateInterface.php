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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

interface ElementDumpStateInterface
{
    public const DUMP_STATE_PROPERTY_NAME = '_fulldump';

    /**
     * Set to true to indicate that we are about to serialize the version data.
     *
     * @param bool $dumpState
     *
     * @return mixed
     */
    public function setInDumpState(bool $dumpState);

    /**
     * @return bool
     */
    public function isInDumpState(): bool;
}
