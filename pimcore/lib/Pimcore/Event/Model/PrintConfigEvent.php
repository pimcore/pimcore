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
use Pimcore\Web2Print\Processor;
use Symfony\Component\EventDispatcher\Event;

class PrintConfigEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * @var Processor
     */
    protected $processor;

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

    /**
     * @return Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * @param Processor $processor
     *
     * @return $this
     */
    public function setProcessor($processor)
    {
        $this->processor = $processor;

        return $this;
    }
}
