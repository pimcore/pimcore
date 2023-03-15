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

namespace Pimcore\Bundle\WebToPrintBundle\Event\Model;

use Pimcore\Bundle\WebToPrintBundle\Processor;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class PrintConfigEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Processor $processor;

    /**
     * DocumentEvent constructor.
     *
     * @param Processor $processor
     * @param array $arguments
     */
    public function __construct(Processor $processor, array $arguments = [])
    {
        $this->processor = $processor;
        $this->arguments = $arguments;
    }

    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    /**
     * @return $this
     */
    public function setProcessor(Processor $processor): static
    {
        $this->processor = $processor;

        return $this;
    }
}
