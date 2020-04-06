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
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Config\Config;
use Pimcore\Model\DataObject\AbstractObject;

abstract class AbstractConfig implements ConfigInterface
{
    /**
     * @var string
     */
    protected $tenantName;

    /**
     * @var array
     */
    protected $attributeConfig;

    /**
     * @var array
     */
    protected $searchAttributeConfig;

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @var array
     */
    protected $searchAttributes;

    /**
     * @var array
     */
    protected $filterTypes;

    /**
     * @var WorkerInterface
     */
    protected $tenantWorker;

    /**
     * @var Config
     */
    protected $filterTypeConfig;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param string $tenantName
     * @param array[]|Attribute[] $attributes
     * @param array $searchAttributes
     * @param array $filterTypes
     * @param array $options
     */
    public function __construct(
        string $tenantName,
        array $attributes,
        array $searchAttributes,
        array $filterTypes,
        array $options = []
    ) {
        $this->tenantName = $tenantName;

        $this->attributeConfig = $attributes;
        $this->searchAttributeConfig = $searchAttributes;

        $this->filterTypes = $filterTypes;
        $this->processOptions($options);
    }

    /**
     * Attribute configuration
     *
     * @return array
     */
    public function getAttributeConfig()
    {
        return $this->attributeConfig;
    }

    /**
     * Sets attribute factory as dependency. This was added as setter for BC reasons and will be added to the constructor
     * signature in Pimcore 7.
     *
     * TODO Pimcore 7 add to constructor signature.
     *
     * @required
     *
     * @param AttributeFactory $attributeFactory
     */
    public function setAttributeFactory(AttributeFactory $attributeFactory)
    {
        if (null !== $this->attributeFactory) {
            throw new \RuntimeException('Attribute factory is already set.');
        }

        $this->attributeFactory = $attributeFactory;

        $this->attributes = [];
        $this->searchAttributes = [];

        $this->buildAttributes($this->attributeConfig);

        foreach ($this->searchAttributeConfig as $searchAttribute) {
            $this->addSearchAttribute($searchAttribute);
        }
    }

    protected function buildAttributes(array $attributes)
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Attribute) {
                $this->addAttribute($attribute);
            } elseif (is_array($attribute)) {
                $attribute = $this->attributeFactory->createAttribute($attribute);
                $this->addAttribute($attribute);
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Wrong type for attribute. Expected Attribute or array, got "%s"',
                    is_object($attribute) ? get_class($attribute) : gettype($attribute)
                ));
            }
        }
    }

    protected function addAttribute(Attribute $attribute)
    {
        $this->attributes[$attribute->getName()] = $attribute;
    }

    protected function addSearchAttribute(string $searchAttribute)
    {
        if (!isset($this->attributes[$searchAttribute])) {
            throw new \InvalidArgumentException(sprintf(
                'The search attribute "%s" in product index tenant "%s" is not defined as attribute',
                $searchAttribute,
                $this->tenantName
            ));
        }

        $this->searchAttributes[] = $searchAttribute;
    }

    protected function processOptions(array $options)
    {
        // noop - to implemented by configs supporting options
    }

    /**
     * @inheritdoc
     */
    public function setTenantWorker(WorkerInterface $tenantWorker)
    {
        $this->checkTenantWorker($tenantWorker);
        $this->tenantWorker = $tenantWorker;
    }

    /**
     * Checks if tenant worker matches prerequisites (config wrapped in worker is this instance and instance has no
     * worker set yet).
     *
     * @param WorkerInterface $tenantWorker
     */
    protected function checkTenantWorker(WorkerInterface $tenantWorker)
    {
        if (null !== $this->tenantWorker) {
            throw new \LogicException(sprintf('Worker for tenant "%s" is already set', $this->tenantName));
        }

        // make sure the worker is the one working on this config instance
        if ($tenantWorker->getTenantConfig() !== $this) {
            throw new \LogicException('Worker config does not match the config the worker is about to be set to');
        }
    }

    /**
     * @inheritDoc
     */
    public function getTenantWorker()
    {
        // the worker is expected to call setTenantWorker as soon as possible
        if (null === $this->tenantWorker) {
            throw new \RuntimeException('Tenant worker is not set.');
        }

        return $this->tenantWorker;
    }

    /**
     * @return string
     */
    public function getTenantName()
    {
        return $this->tenantName;
    }

    /**
     * Returns configured attributes for product index
     *
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        // TODO Pimcore 7 remove as soon as attribute factory was added to the constructor.
        if (null === $this->attributes) {
            throw new \RuntimeException('Attributes are not built yet. Is the service properly configured to set an attribute factory?');
        }

        return $this->attributes;
    }

    /**
     * Returns full text search index attribute names for product index
     *
     * @return array
     */
    public function getSearchAttributes(): array
    {
        // TODO Pimcore 7 remove as soon as attribute factory was added to the constructor.
        if (null === $this->attributes) {
            throw new \RuntimeException('Search attributes are not built yet. Is the service properly configured to set an attribute factory?');
        }

        return $this->searchAttributes;
    }

    /**
     * return all supported filter types for product index
     *
     * @return array|null
     */
    public function getFilterTypeConfig()
    {
        return $this->filterTypeConfig;
    }

    /**
     * @param IndexableInterface $object
     *
     * @return bool
     */
    public function isActive(IndexableInterface $object)
    {
        return true;
    }

    /**
     * @param IndexableInterface $object
     * @param int|null $subObjectId
     *
     * @return AbstractCategory[]
     */
    public function getCategories(IndexableInterface $object, $subObjectId = null)
    {
        return $object->getCategories();
    }

    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param IndexableInterface $object
     *
     * @return IndexableInterface[]
     */
    public function createSubIdsForObject(IndexableInterface $object)
    {
        return [$object->getId() => $object];
    }

    /**
     * checks if there are some zombie subIds around and returns them for cleanup
     *
     * @param IndexableInterface $object
     * @param array $subIds
     *
     * @return mixed
     */
    public function getSubIdsToCleanup(IndexableInterface $object, array $subIds)
    {
        return [];
    }

    /**
     * creates virtual parent id for given sub id
     * default is getOSParentId
     *
     * @param IndexableInterface $object
     * @param int $subId
     *
     * @return mixed
     */
    public function createVirtualParentIdForSubId(IndexableInterface $object, $subId)
    {
        return $object->getOSParentId();
    }

    /**
     * Gets object by id, can consider subIds and therefore return e.g. an array of values
     * always returns object itself - see also getObjectMockupById
     *
     * @param int $objectId
     * @param bool $onlyMainObject - only returns main object
     *
     * @return mixed
     */
    public function getObjectById($objectId, $onlyMainObject = false)
    {
        return AbstractObject::getById($objectId);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param int $objectId
     *
     * @return IndexableInterface|array
     */
    public function getObjectMockupById($objectId)
    {
        return $this->getObjectById($objectId);
    }

    /**
     * returns column type for id
     *
     * @param bool $isPrimary
     *
     * @return string
     */
    public function getIdColumnType($isPrimary)
    {
        if ($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return 'int(11) NOT NULL';
        }
    }
}
