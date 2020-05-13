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
use Pimcore\Model\AbstractModel;
use Symfony\Component\EventDispatcher\Event;

class ElementEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var AbstractModel
     */
    protected $element;

    /**
     * ElementEvent constructor.
     *
     * @param AbstractModel $element
     * @param array $arguments
     */
    public function __construct(AbstractModel $element, array $arguments = [])
    {
        $this->element = $element;
        $this->arguments = $arguments;
    }

    /**
     * @return AbstractModel
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param AbstractModel $element
     */
    public function setElement($element)
    {
        $this->element = $element;
    }
}
