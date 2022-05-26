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

namespace Pimcore\Event\Targeting;

use Symfony\Contracts\EventDispatcher\Event;

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

    /**
     * @param string $template
     * @param array $data
     */
    public function __construct(string $template, array $data)
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template
     * @return void
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
}
