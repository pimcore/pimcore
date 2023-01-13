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
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Default configuration for elastic search as product index implementation.
 *
 */
class ElasticSearch extends AbstractConfig implements MockupConfigInterface, ElasticSearchConfigInterface
{
    use OptionsResolverTrait;

    protected array $clientConfig = [];

    protected array $indexSettings = [];

    protected array $elasticSearchClientParams = [];

    /**
     * contains the mapping for the fields in Elasticsearch
     *
     * @var array
     */
    protected array $fieldMapping = [
        'id' => 'system.id',
        'classId' => 'system.classId',
        'virtualProductId' => 'system.virtualProductId',
        'virtualProductActive' => 'system.virtualProductActive',
        'parentId' => 'system.parentid',
        'type' => 'system.type',
        'categoryIds' => 'system.categoryIds',
        'parentCategoryIds' => 'system.parentCategoryIds',
        'categoryPaths' => 'system.categoryPaths',
        'priceSystemName' => 'system.priceSystemName',
        'active' => 'system.active',
        'inProductList' => 'system.inProductList',
    ];

    protected EnvironmentInterface $environment;

    /** @var SynonymProviderInterface[] */
    protected iterable $synonymProviders = [];

    /**
     * {@inheritdoc}
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
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $arrayFields = [
            'client_config',
            'index_settings',
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

        $resolver->setDefined('es_client_name');
        $resolver->setAllowedTypes('es_client_name', 'string');
    }

    protected function extractPossibleFirstSubFieldnameParts(string $fieldName): array
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

    /** @inheritDoc */
    public function getFieldNameMapped(string $fieldName, bool $considerSubFieldNames = false): string
    {
        if (isset($this->fieldMapping[$fieldName])) {
            return $this->fieldMapping[$fieldName];
        }

        // consider subfield names like name.analyzed or score definitions like name^3
        if ($considerSubFieldNames) {
            $fieldNameParts = $this->extractPossibleFirstSubFieldnameParts($fieldName);
            foreach ($fieldNameParts as $fieldNamePart) {
                if (isset($this->fieldMapping[$fieldNamePart])) {
                    return $this->fieldMapping[$fieldNamePart] . str_replace($fieldNamePart, '', $fieldName);
                }
            }
        }

        return $fieldName;
    }

    /** @inheritDoc */
    public function getReverseMappedFieldName(string $fullFieldName): bool|int|string
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
     * @param string|null $property
     *
     * @return array|string|null
     */
    public function getClientConfig(string $property = null): array|string|null
    {
        if ($property) {
            return $this->clientConfig[$property] ?? null;
        }

        return $this->clientConfig;
    }

    public function getIndexSettings(): array
    {
        return $this->indexSettings;
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
     * @return array $subTenantData
     */
    public function prepareSubTenantEntries(IndexableInterface $object, int $subObjectId = null): array
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
    public function updateSubTenantEntries(mixed $objectId, mixed $subTenantData, mixed $subObjectId = null): void
    {
        // nothing to do
        return;
    }

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition(): array
    {
        if ($currentSubTenant = $this->environment->getCurrentAssortmentSubTenant()) {
            return ['term' => ['subtenants.ids' => $currentSubTenant]];
        }

        return [];
    }

    /**
     * {@inheritdoc}
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
     * @param array $data
     * @param array $relations
     *
     * @return DefaultMockup
     */
    public function createMockupObject(int $objectId, mixed $data, array $relations): DefaultMockup
    {
        return new DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param int $objectId
     *
     * @return IndexableInterface|null
     */
    public function getObjectMockupById(int $objectId): ?IndexableInterface
    {
        $listing = $this->getTenantWorker()->getProductList();
        $listing->addCondition((string)$objectId, 'id');
        $listing->setLimit(1);
        $product = $listing->current();

        return $product ? $product : null;
    }

    #[Required]
    public function setEnvironment(EnvironmentInterface $environment): void
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
