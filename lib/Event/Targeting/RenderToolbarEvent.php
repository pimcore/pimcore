<?php

declare(strict_types=1);

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

namespace Pimcore\Event\Targeting;

use Symfony\Component\EventDispatcher\Event;

class RenderToolbarEvent extends Event
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $template, array $data)
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }
}
