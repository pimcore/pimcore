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

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\SynonymProvider\SynonymProviderInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch\AbstractElasticSearch as DefaultElasticSearchWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\DefaultMockup;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Default configuration for elastic search as product index implementation.
 *
 * @method DefaultElasticSearchWorker getTenantWorker()
 */
class ElasticSearch extends AbstractConfig implements MockupConfigInterface, ElasticSearchConfigInterface
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
        'o_virtualProductId' => 'system.o_virtualProductId',
        'o_virtualProductActive' => 'system.o_virtualProductActive',
        'o_parentId' => 'system.o_parentId',
        'o_type' => 'system.o_type',
        'categoryIds' => 'system.categoryIds',
        'parentCategoryIds' => 'system.parentCategoryIds',
        'categoryPaths' => 'system.categoryPaths',
        'priceSystemName' => 'system.priceSystemName',
        'active' => 'system.active',
        'inProductList' => 'system.inProductList',
    ];

    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /** @var SynonymProviderInterface[] */
    protected $synonymProviders = [];

    /**
     * @inheritDoc
     *
     * @param SynonymProviderInterface[] $synonymProviders
     */
    public function __construct(
        string $tenantName,
        array $attributes,
        array $searchAttributes,
        array $filterTypes,
        array $options = [],
        iterable $synonymProviders = []
    ) {
        $this->synonymProviders = $synonymProviders;
        parent::__construct($tenantName, $attributes, $searchAttributes, $filterTypes, $options);
    }

    protected function addAttribute(Attribute $attribute)
    {
        parent::addAttribute($attribute);

        $attributeType = 'attributes';
        if (null !== $attribute->getInterpreter() && $attribute->getInterpreter() instanceof RelationInterpreterInterface) {
            $attributeType = 'relations';
        }

        $this->fieldMapping[$attribute->getName()] = sprintf('%s.%s', $attributeType, $attribute->getName());
    }

    protected function addSearchAttribute(string $searchAttribute)
    {
        if (isset($this->attributes[$searchAttribute])) {
            $this->searchAttributes[] = $searchAttribute;

            return;
        }

        $fieldNameParts = $this->extractPossibleFirstSubFieldnameParts($searchAttribute);
        foreach ($fieldNameParts as $fieldNamePart) {
            if (isset($this->attributes[$fieldNamePart])) {
                $this->searchAttributes[] = $searchAttribute;

                return;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'The search attribute "%s" in product index tenant "%s" is not defined as attribute',
            $searchAttribute,
            $this->tenantName
        ));
    }

    protected function processOptions(array $options)
    {
        $options = $this->resolveOptions($options);

        // TODO validate client config and other settings/params?
        $this->clientConfig = $options['client_config'];
        $this->indexSettings = $options['index_settings'];
        $this->elasticSearchClientParams = $options['es_client_params'];
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $arrayFields = [
            'client_config',
            'index_settings',
            'es_client_params',
            'mapping',
        ];

        foreach ($arrayFields as $field) {
            $resolver->setDefault($field, []);
            $resolver->setAllowedTypes($field, 'array');
        }

        $resolver->setDefined('mapper');
        $resolver->setAllowedTypes('mapper', 'string');

        $resolver->setDefined('analyzer');
        $resolver->setDefined('synonym_providers');

        $resolver->setDefault('store', true);
        $resolver->setAllowedTypes('store', 'bool');
    }

    /**
     * @param string $fieldName
     *
     * @return array
     */
    protected function extractPossibleFirstSubFieldnameParts($fieldName)
    {
        $parts = [];

        $delimiters = ['.', '^'];

        foreach ($delimiters as $delimiter) {
            if (strpos($fieldName, $delimiter) !== false) {
                $fieldNameParts = explode($delimiter, $fieldName);
                $parts[] = $fieldNameParts[0];
            }
        }

        return $parts;
    }

    /**
     * returns the full field name
     *
     * @param string $fieldName
     * @param bool $considerSubFieldNames - activate to consider subfield names like name.analyzed or score definitions like name^3
     *
     * @return string
     */
    public function getFieldNameMapped($fieldName, $considerSubFieldNames = false)
    {
        if ($this->fieldMapping[$fieldName]) {
            return $this->fieldMapping[$fieldName];
        }

        // consider subfield names like name.analyzed or score definitions like name^3
        if ($considerSubFieldNames) {
            $fieldNameParts = $this->extractPossibleFirstSubFieldnameParts($fieldName);
            foreach ($fieldNameParts as $fieldNamePart) {
                if ($this->fieldMapping[$fieldNamePart]) {
                    return $this->fieldMapping[$fieldNamePart] . str_replace($fieldNamePart, '', $fieldName);
                }
            }
        }

        return $fieldName;
    }

    /**
     * returns short field name based on full field name
     * also considers subfield names like name.analyzed etc.
     *
     * @param string $fullFieldName
     *
     * @return false|int|string
     */
    public function getReverseMappedFieldName($fullFieldName)
    {
        //check for direct match of field name
        $fieldName = array_search($fullFieldName, $this->fieldMapping);
        if ($fieldName) {
            return $fieldName;
        }

        //search for part match in order to consider sub field names like name.analyzed
        $fieldNamePart = $fullFieldName;
        while (!empty($fieldNamePart)) {

            // cut off part after last .
            $fieldNamePart = substr($fieldNamePart, 0, strripos($fieldNamePart, '.'));

            // search for mapping with field name part
            $fieldName = array_search($fieldNamePart, $this->fieldMapping);

            if ($fieldName) {
                // append cut off part again to returned field name
                return $fieldName . str_replace($fieldNamePart, '', $fullFieldName);
            }
        }

        //return full field name if no mapping was found
        return $fullFieldName;
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
     * @return array $subTenantData
     */
    public function prepareSubTenantEntries(IndexableInterface $object, $subObjectId = null)
    {
        return [];
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
        if ($currentSubTenant = $this->environment->getCurrentAssortmentSubTenant()) {
            return ['term' => ['subtenants.ids' => $currentSubTenant]];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setTenantWorker(WorkerInterface $tenantWorker)
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

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param int $objectId
     *
     * @return IndexableInterface | array
     */
    public function getObjectMockupById($objectId)
    {
        $listing = $this->getTenantWorker()->getProductList();
        $listing->addCondition($objectId, 'o_id');
        $listing->setLimit(1);
        $product = $listing->current();

        return $product ? $product : null;
    }

    /**
     * @required
     *
     * @param EnvironmentInterface $environment
     */
    public function setEnvironment(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Get an associative array of configured synonym providers.
     *  - key: the name of the synonym provider configuration, which is equivalent to the name of the configured filter
     *  - value: the synonym provider
     *
     * @return SynonymProviderInterface[]
     */
    public function getSynonymProviders(): array
    {
        return $this->synonymProviders;
    }
}
