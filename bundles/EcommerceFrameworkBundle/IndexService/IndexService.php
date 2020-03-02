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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Exception\DefaultWorkerNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Exception\WorkerNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

class IndexService
{
    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var WorkerInterface[]
     */
    protected $tenantWorkers = [];

    /**
     * @var string
     */
    protected $defaultTenant = 'default';

    /**
     * @param EnvironmentInterface $environment
     * @param WorkerInterface[] $tenantWorkers
     * @param string $defaultTenant
     */
    public function __construct(EnvironmentInterface $environment, array $tenantWorkers = [], string $defaultTenant = 'default')
    {
        $this->environment = $environment;

        foreach ($tenantWorkers as $tenantWorker) {
            $this->registerTenantWorker($tenantWorker);
        }

        if (null !== $defaultTenant && !empty($defaultTenant)) {
            $this->defaultTenant = $defaultTenant;
        }
    }

    protected function registerTenantWorker(WorkerInterface $tenantWorker)
    {
        $this->tenantWorkers[$tenantWorker->getTenantConfig()->getTenantName()] = $tenantWorker;
    }

    public function getTenants(): array
    {
        return array_keys($this->tenantWorkers);
    }

    /**
     * Returns a specific tenant worker
     *
     * @param string $tenant
     *
     * @return WorkerInterface
     *
     * @throws WorkerNotFoundException
     */
    public function getTenantWorker(string $tenant): WorkerInterface
    {
        if (!array_key_exists($tenant, $this->tenantWorkers)) {
            throw new WorkerNotFoundException(sprintf('Tenant "%s" doesn\'t exist', $tenant));
        }

        return $this->tenantWorkers[$tenant];
    }

    /**
     * Returns default worker as set in defaultTenant
     *
     * @return WorkerInterface
     *
     * @throws DefaultWorkerNotFoundException
     */
    public function getDefaultWorker(): WorkerInterface
    {
        if (!array_key_exists($this->defaultTenant, $this->tenantWorkers)) {
            throw new DefaultWorkerNotFoundException(sprintf(
                'Could not load default worker as there is no worker registered for the tenant "%s"',
                $this->defaultTenant
            ));
        }

        return $this->tenantWorkers[$this->defaultTenant];
    }

    /**
     * @deprecated
     *
     * @param string|null $tenant
     *
     * @return array
     */
    public function getGeneralSearchColumns(string $tenant = null)
    {
        return $this->getGeneralSearchAttributes($tenant);
    }

    /**
     * Returns all attributes marked as general search attributes for full text search
     *
     * @param string $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getGeneralSearchAttributes(string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getGeneralSearchAttributes();
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * @deprecated
     */
    public function createOrUpdateTable()
    {
        $this->createOrUpdateIndexStructures();
    }

    /**
     * Creates or updates necessary index structures (e.g. database tables)
     */
    public function createOrUpdateIndexStructures()
    {
        foreach ($this->tenantWorkers as $tenant => $tenantWorker) {
            $tenantWorker->createOrUpdateIndexStructures();
        }
    }

    /**
     * Deletes given element from index
     *
     * @param IndexableInterface $object
     */
    public function deleteFromIndex(IndexableInterface $object)
    {
        foreach ($this->tenantWorkers as $tenant => $tenantWorker) {
            $tenantWorker->deleteFromIndex($object);
        }
    }

    /**
     * Updates given element in index
     *
     * @param IndexableInterface $object
     */
    public function updateIndex(IndexableInterface $object)
    {
        foreach ($this->tenantWorkers as $tenant => $tenantWorker) {
            $tenantWorker->updateIndex($object);
        }
    }

    /**
     * Returns all index attributes
     *
     * @param bool $considerHideInFieldList
     * @param string|null $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getIndexAttributes(bool $considerHideInFieldList = false, string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getIndexAttributes($considerHideInFieldList);
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * @deprecated
     *
     * @param bool $considerHideInFieldList
     * @param string|null $tenant
     *
     * @return mixed
     *
     * @throws InvalidConfigException
     */
    public function getIndexColumns($considerHideInFieldList = false, $tenant = null)
    {
        return $this->getIndexAttributes($considerHideInFieldList, $tenant);
    }

    /**
     * Returns all filter groups
     *
     * @param string $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getAllFilterGroups(string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getAllFilterGroups();
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * Returns all index attributes for a given filter group
     *
     * @param string $filterType
     * @param string|null $tenant
     *
     * @return array
     *
     * @throws InvalidConfigException
     */
    public function getIndexAttributesByFilterGroup($filterType, string $tenant = null): array
    {
        try {
            $tenantWorker = $this->resolveTenantWorker($tenant);

            return $tenantWorker->getIndexAttributesByFilterGroup($filterType);
        } catch (DefaultWorkerNotFoundException $e) {
            return [];
        }
    }

    /**
     * @deprecated
     *
     * @param string $filterType
     * @param string|null $tenant
     *
     * @return mixed
     *
     * @throws InvalidConfigException
     */
    public function getIndexColumnsByFilterGroup($filterType, $tenant = null)
    {
        return $this->getIndexAttributesByFilterGroup($filterType, $tenant);
    }

    /**
     * Returns current tenant configuration
     *
     * @return ConfigInterface
     *
     * @throws InvalidConfigException
     */
    public function getCurrentTenantConfig()
    {
        return $this->getCurrentTenantWorker()->getTenantConfig();
    }

    public function getCurrentTenantWorker(): WorkerInterface
    {
        return $this->resolveTenantWorker();
    }

    public function getProductListForCurrentTenant(): ProductListInterface
    {
        $tenantWorker = $this->getCurrentTenantWorker();

        return $tenantWorker->getProductList();
    }

    public function getProductListForTenant(string $tenant): ProductListInterface
    {
        $tenantWorker = $this->resolveTenantWorker($tenant);

        return $tenantWorker->getProductList();
    }

    /**
     * Resolve tenant worker either from given tenant name or from the current tenant
     *
     * @param string|null $tenant
     *
     * @return WorkerInterface
     *
     * @throws WorkerNotFoundException
     */
    protected function resolveTenantWorker(string $tenant = null): WorkerInterface
    {
        if (null === $tenant) {
            $tenant = $this->environment->getCurrentAssortmentTenant();
        }

        if ($tenant) {
            if (!array_key_exists($tenant, $this->tenantWorkers)) {
                throw new WorkerNotFoundException(sprintf('Tenant "%s" doesn\'t exist', $tenant));
            }

            return $this->tenantWorkers[$tenant];
        }

        return $this->getDefaultWorker();
    }

    /**
     * @param WorkerInterface[] $tenantWorkers
     *
     * @return IndexService
     */
    public function setTenantWorkers(array $tenantWorkers): self
    {
        $tenantWorkerAssocList = [];
        foreach ($tenantWorkers as $tenantWorker) {
            $tenantWorkerAssocList[$tenantWorker->getTenantConfig()->getTenantName()] = $tenantWorker;
        }
        $this->tenantWorkers = $tenantWorkerAssocList;

        return $this;
    }
}
