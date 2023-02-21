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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\ServiceLocator;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

abstract class NameServiceLocator
{
    protected PsrContainerInterface $locator;

    protected string $defaultName = 'default';

    public function __construct(PsrContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    protected function locate(string $name = null): mixed
    {
        $name = $this->resolveName($name);

        if (!$this->locator->has($name)) {
            throw $this->buildNotFoundException($name);
        }

        return $this->locator->get($name);
    }

    protected function resolveName(string $name = null): string
    {
        if (empty($name)) {
            return $this->defaultName;
        }

        return $name;
    }

    abstract protected function buildNotFoundException(string $name): UnsupportedException;
}
