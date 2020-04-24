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

use Pimcore\Model\DataObject\Concrete;

class ObjectNode extends AbstractNode
{

    /** @var int $id */
    protected $id;

    /**
     * @internal
     *
     * ObjectNode constructor.
     * @param int $id
     */
    public function __construct($id)
    {
        if ($id instanceof Concrete) {
            $id = $id->getId();
        }
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}
