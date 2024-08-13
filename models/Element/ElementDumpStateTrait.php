<?php
declare(strict_types=1);

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
