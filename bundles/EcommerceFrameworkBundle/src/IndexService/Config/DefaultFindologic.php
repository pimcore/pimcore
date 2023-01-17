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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFindologic as DefaultFindologicWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Default implementation for FINDOLOGIC as product index backend
 *
 */
class DefaultFindologic extends AbstractConfig implements FindologicConfigInterface, MockupConfigInterface
{
    use OptionsResolverTrait;

    protected array $clientConfig;

    protected function processOptions(array $options): void
    {
        $options = $this->resolveOptions($options);

        // TODO validate client config for required options?
        $this->clientConfig = $options['client_config'];
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'client_config' => [],
        ]);

        $resolver->setAllowedTypes('client_config', 'array');
    }

    /**
     * @param string|null $setting
     *
     * @return array|string|null
     */
    public function getClientConfig(string $setting = null): array|string|null
    {
        return $setting
            ? $this->clientConfig[$setting]
            : $this->clientConfig
        ;
    }

    /**
     * checks, if product should be in index for current tenant
     *
     * @param IndexableInterface $object
     *
     * @return bool
     */
    public function inIndex(IndexableInterface $object): bool
    {
        return true;
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param IndexableInterface $object
     * @param int|null $subObjectId
     *
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(IndexableInterface $object, int $subObjectId = null): mixed
    {
        return null;
    }

    /**
     * populates index for tenant relations based on gived data
     *
     * @param mixed $objectId
     * @param mixed $subTenantData
     * @param mixed $subObjectId
     *
     * @return void
     */
    public function updateSubTenantEntries(mixed $objectId, mixed $subTenantData, mixed $subObjectId = null): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setTenantWorker(WorkerInterface $tenantWorker): void
    {
        if (!$tenantWorker instanceof DefaultFindologicWorker) {
            throw new \InvalidArgumentException(sprintf(
                'Worker must be an instance of %s',
                DefaultFindologicWorker::class
            ));
        }

        parent::setTenantWorker($tenantWorker);
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantWorker(): DefaultFindologicWorker
    {
        $tenantWorker = parent::getTenantWorker();
        if (!$tenantWorker instanceof DefaultFindologicWorker) {
            throw new \InvalidArgumentException(sprintf(
                'Worker must be an instance of %s',
                DefaultFindologicWorker::class
            ));
        }

        return $tenantWorker;
    }

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition(): array
    {
        return [];
    }

    /**
     * creates object mockup for given data
     *
     * @param int $objectId
     * @param array $data
     * @param array $relations
     *
     * @return DefaultMockup
     */
    public function createMockupObject(int $objectId, array $data, array $relations): DefaultMockup
    {
        return new DefaultMockup($objectId, $data, $relations);
    }
}
