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
