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

trait ElementDumpStateTrait
{
    /**
     * This needs to be equal to the value of ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME
     *
     */
    protected bool $_fulldump = false;

    public function setInDumpState(bool $dumpState): void
    {
        $this->_fulldump = $dumpState;
    }

    public function isInDumpState(): bool
    {
        return $this->_fulldump;
    }
}
