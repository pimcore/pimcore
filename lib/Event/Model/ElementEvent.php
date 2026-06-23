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
use Pimcore\Model\Element\ElementInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ElementEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    protected ElementInterface $element;

    /**
     * ElementEvent constructor.
     *
     */
    public function __construct(ElementInterface $element, array $arguments = [])
    {
        $this->element = $element;
        $this->arguments = $arguments;
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    public function setElement(ElementInterface $element): void
    {
        $this->element = $element;
    }
}
