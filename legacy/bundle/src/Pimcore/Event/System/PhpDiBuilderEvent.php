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

namespace Pimcore\Event\System;

use DI\ContainerBuilder;
use Symfony\Component\EventDispatcher\Event;

class PhpDiBuilderEvent extends Event
{

    /**
     * @var ContainerBuilder
     */
    protected $builder;

    /**
     * PhpDiBuilderEvent constructor.
     * @param ContainerBuilder $builder
     */
    public function __construct(ContainerBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return ContainerBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param ContainerBuilder $builder
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
    }
}
