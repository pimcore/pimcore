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
use Pimcore\Model\AbstractModel;
use Symfony\Contracts\EventDispatcher\Event;

class ElementEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    protected AbstractModel $element;

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

    public function getElement(): AbstractModel
    {
        return $this->element;
    }

    public function setElement(AbstractModel $element)
    {
        $this->element = $element;
    }
}
