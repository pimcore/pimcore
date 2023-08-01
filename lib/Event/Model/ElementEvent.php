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
