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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Contracts\EventDispatcher\Event;

class DataObjectEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    protected AbstractObject $object;

    /**
     * DataObjectEvent constructor.
     *
     */
    public function __construct(AbstractObject $object, array $arguments = [])
    {
        $this->object = $object;
        $this->arguments = $arguments;
    }

    public function getObject(): AbstractObject
    {
        return $this->object;
    }

    public function setObject(AbstractObject $object): void
    {
        $this->object = $object;
    }

    public function getElement(): AbstractObject
    {
        return $this->getObject();
    }
}
