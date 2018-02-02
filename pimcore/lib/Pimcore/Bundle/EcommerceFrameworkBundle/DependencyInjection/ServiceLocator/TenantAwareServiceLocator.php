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
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Psr\Container\ContainerInterface as PsrContainerInterface;

abstract class TenantAwareServiceLocator
{
    /**
     * @var PsrContainerInterface
     */
    protected $locator;

    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * If true the locator will not fall back to the default tenant if a tenant is requested but not existing
     *
     * @var bool
     */
    protected $strictTenants = false;

    /**
     * @var string
     */
    protected $defaultTenant = 'default';

    public function __construct(
        PsrContainerInterface $locator,
        IEnvironment $environment,
        bool $strictTenants = false
    ) {
        $this->locator       = $locator;
        $this->environment   = $environment;
        $this->strictTenants = $strictTenants;
    }

    protected function locate(string $tenant = null)
    {
        $tenant = $this->resolveTenant($tenant);

        if (!$this->locator->has($tenant)) {
            throw $this->buildNotFoundException($tenant);
        }

        return $this->locator->get($tenant);
    }

    abstract protected function buildNotFoundException(string $tenant): UnsupportedException;

    protected function resolveTenant(string $tenant = null)
    {
        // explicitly checking for empty here to catch situations where the tenant is just an empty string
        if (empty($tenant)) {
            $tenant = $this->getEnvironmentTenant();
        }

        if (!empty($tenant)) {
            // if tenant isn't available and we're not in strict tenant mode, fall
            // back to the default tenant
            // in strict tenant mode, just return the tenant, no matter if it exists or not
            if ($this->strictTenants || $this->locator->has($tenant)) {
                return $tenant;
            }
        }

        return $this->defaultTenant;
    }

    abstract protected function getEnvironmentTenant();
}
