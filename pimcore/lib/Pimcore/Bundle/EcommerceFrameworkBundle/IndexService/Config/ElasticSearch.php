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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultElasticSearch as DefaultElasticSearchWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Default configuration for elastic search as product index implementation.
 *
 * @method DefaultElasticSearchWorker getTenantWorker()
 */
class ElasticSearch extends AbstractConfig implements IMockupConfig, IElasticSearchConfig
{
    use OptionsResolverTrait;

    /**
     * @var array
     */
    protected $clientConfig = [];

    /**
     * @var array
     */
    protected $indexSettings = [];

    /**
     * @var array
     */
    protected $elasticSearchClientParams = [];

    /**
     * contains the mapping for the fields in Elasticsearch
     *
     * @var array
     */
    protected $fieldMapping = [
        'o_id' => 'system.o_id',
        'o_classId' => 'system.o_classId',
        'o_virtualProductId'=> 'system.o_virtualProductId',
        'o_virtualProductActive'=> 'system.o_virtualProductActive',
        'o_parentId'=> 'system.o_parentId',
        'o_type'=> 'system.o_type',
        'categoryIds'=> 'system.categoryIds',
        'parentCategoryIds'=> 'system.parentCategoryIds',
        'categoryPaths'=> 'system.categoryPaths',
        'priceSystemName'=> 'system.priceSystemName',
        'active'=> 'system.active',
        'inProductList'=> 'system.inProductList',
    ];

    protected function addAttribute(Attribute $attribute)
    {
        parent::addAttribute($attribute);

        $attributeType = 'attributes';
        if (null !== $attribute->getInterpreter() && $attribute->getInterpreter() instanceof IRelationInterpreter) {
            $attributeType = 'relations';
        }

        $this->fieldMapping[$attribute->getName()] = sprintf('%s.%s', $attributeType, $attribute->getName());
    }

    protected function processOptions(array $options)
    {
        $options = $this->resolveOptions($options);

        // TODO validate client config and other settings/params?
        $this->clientConfig = $options['client_config'];
        $this->indexSettings = $options['index_settings'];
        $this->elasticSearchClientParams = $options['elastic_search_client_params'];
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $arrayFields = [
            'client_config',
            'index_settings',
            'es_client_params',
            'mapping'
        ];

        foreach($arrayFields as $field) {
            $resolver->setAllowedTypes($field, 'array');
            $resolver->setDefault($field, []);
        }

        $resolver->setDefined('mapper');
        $resolver->setAllowedTypes('mapper', 'string');

        $resolver->setDefined('analyzer');

        $resolver->setDefault('store', true);
        $resolver->setAllowedTypes('store', 'bool');
    }

    /**
     * returns the full field name
     *
     * @param $fieldName
     *
     * @return string
     */
    public function getFieldNameMapped($fieldName)
    {
        return $this->fieldMapping[$fieldName] ?: $fieldName;
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
     * @return array
     */
    public function getIndexSettings()
    {
        return $this->indexSettings;
    }

    /**
     * @return array
     */
    public function getElasticSearchClientParams()
    {
        return $this->elasticSearchClientParams;
    }

    /**
     * checks, if product should be in index for current tenant
     *
     * @param IIndexable $object
     *
     * @return bool
     */
    public function inIndex(IIndexable $object)
    {
        return true;
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param IIndexable $object
     * @param null $subObjectId
     *
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(IIndexable $object, $subObjectId = null)
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
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null)
    {
        // nothing to do
        return;
    }

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition()
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function setTenantWorker(IWorker $tenantWorker)
    {
        if (!$tenantWorker instanceof DefaultElasticSearchWorker) {
            throw new \InvalidArgumentException(sprintf(
                'Worker must be an instance of %s',
                DefaultElasticSearchWorker::class
            ));
        }

        parent::setTenantWorker($tenantWorker);
    }

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     *
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations)
    {
        return new DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     *
     * @return IIndexable | array
     */
    public function getObjectMockupById($objectId)
    {
        return $this->getTenantWorker()->getMockupFromCache($objectId);
    }
}
