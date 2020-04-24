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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ContextChain;

class OwnerChain extends \SplDoublyLinkedList
{

    /**
     * @internal
     *
     * OwnerChain constructor.
     */
    public function __construct()
    {
        // only "implemented" here to mark it as internal
    }

    public function __toString() {
        $result = [];
        $dumpedList =  [];
        for ($this->rewind(); $this->valid(); $this->next()) {
            $dumpedList[] = $this->current();
        }

        foreach ($dumpedList as $item) {
            $result[] = (string) ($item);
        }

        return "list: " . implode('->', $result);
    }

}
