<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

trait ElementDumpStateTrait
{
    /**
     * This needs to be equal to the value of ElementDumpStateInterface::DUMP_STATE_PROPERTY_NAME
     *
     * @var bool
     */
    protected $_fulldump = false;

    /**
     * @param bool $dumpState
     *
     * @return mixed|void
     */
    public function setInDumpState(bool $dumpState)
    {
        $this->_fulldump = $dumpState;
    }

    /**
     * @return bool
     */
    public function isInDumpState(): bool
    {
        return $this->_fulldump;
    }
}
