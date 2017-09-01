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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\EventDispatcher\Event;

class DataObjectEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var AbstractObject
     */
    protected $object;

    /**
     * DocumentEvent constructor.
     *
     * @param AbstractObject $object
     * @param array $arguments
     */
    public function __construct(AbstractObject $object, array $arguments = [])
    {
        $this->object = $object;
        $this->arguments = $arguments;
    }

    /**
     * @return AbstractObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param AbstractObject $object
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * @return AbstractObject
     */
    public function getElement()
    {
        return $this->getObject();
    }
}
