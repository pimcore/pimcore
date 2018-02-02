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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\ServiceLocator;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

abstract class NameServiceLocator
{
    /**
     * @var PsrContainerInterface
     */
    protected $locator;

    /**
     * @var string
     */
    protected $defaultName = 'default';

    public function __construct(PsrContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    protected function locate(string $name = null)
    {
        $name = $this->resolveName($name);

        if (!$this->locator->has($name)) {
            throw $this->buildNotFoundException($name);
        }

        return $this->locator->get($name);
    }

    protected function resolveName(string $name = null)
    {
        if (empty($name)) {
            return $this->defaultName;
        }

        return $name;
    }

    abstract protected function buildNotFoundException(string $name): UnsupportedException;
}
