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
 * @method DefaultFindologicWorker getTenantWorker()
 */
class DefaultFindologic extends AbstractConfig implements FindologicConfigInterface, MockupConfigInterface
{
    use OptionsResolverTrait;

    /**
     * @var array
     */
    protected $clientConfig;

    protected function processOptions(array $options)
    {
        $options = $this->resolveOptions($options);

        // TODO validate client config for required options?
        $this->clientConfig = $options['client_config'];
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'client_config' => [],
        ]);

        $resolver->setAllowedTypes('client_config', 'array');
    }

    /**
     * @param string $property
     *
     * @return array|string
     */
    public function getClientConfig($property = null)
    {
        return $property
            ? $this->clientConfig[$property]
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
    public function inIndex(IndexableInterface $object)
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
    public function prepareSubTenantEntries(IndexableInterface $object, $subObjectId = null)
    {
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
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function setTenantWorker(WorkerInterface $tenantWorker)
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
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition()
    {
        return [];
    }

    /**
     * creates object mockup for given data
     *
     * @param int $objectId
     * @param mixed $data
     * @param array $relations
     *
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations)
    {
        return new DefaultMockup($objectId, $data, $relations);
    }
}
