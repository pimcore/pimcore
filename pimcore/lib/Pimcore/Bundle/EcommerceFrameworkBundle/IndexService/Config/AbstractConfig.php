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
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Config\Config;
use Pimcore\Model\Object\AbstractObject;

abstract class AbstractConfig implements IConfig
{
    protected $tenantName;
    protected $attributeConfig;
    protected $searchAttributeConfig;

    /**
     * @var Attribute[]
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $searchAttributes = [];

    /**
     * @var array
     */
    protected $filterTypes;

    /**
     * @var IWorker
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
     * @param Attribute[] $attributes
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

        foreach ($attributes as $attribute) {
            $this->addAttribute($attribute);
        }

        foreach ($searchAttributes as $searchAttribute) {
            $this->addSearchAttribute($searchAttribute);
        }

        $this->filterTypes = $filterTypes;
        $this->processOptions($options);
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
    public function setTenantWorker(IWorker $tenantWorker)
    {
        if (null !== $this->tenantWorker) {
            throw new \LogicException(sprintf('Worker for tenant "%s" is already set', $this->tenantName));
        }

        // make sure the worker is the one working on this config instance
        if ($tenantWorker->getTenantConfig() !== $this) {
            throw new \LogicException('Worker config does not match the config the worker is about to be set to');
        }

        $this->tenantWorker = $tenantWorker;
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
        return $this->attributes;
    }

    /**
     * Returns full text search index attribute names for product index
     *
     * @return array
     */
    public function getSearchAttributes(): array
    {
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
     * @param IIndexable $object
     *
     * @return bool
     */
    public function isActive(IIndexable $object)
    {
        return true;
    }

    /**
     * @param IIndexable $object
     *
     * @return AbstractCategory[]
     */
    public function getCategories(IIndexable $object)
    {
        return $object->getCategories();
    }

    /**
     * creates an array of sub ids for the given object
     * use that function, if one object should be indexed more than once (e.g. if field collections are in use)
     *
     * @param IIndexable $object
     *
     * @return IIndexable[]
     */
    public function createSubIdsForObject(IIndexable $object)
    {
        return [$object->getId() => $object];
    }

    /**
     * checks if there are some zombie subIds around and returns them for cleanup
     *
     * @param IIndexable $object
     * @param array $subIds
     *
     * @return mixed
     */
    public function getSubIdsToCleanup(IIndexable $object, array $subIds)
    {
        return [];
    }

    /**
     * creates virtual parent id for given sub id
     * default is getOSParentId
     *
     * @param IIndexable $object
     * @param $subId
     *
     * @return mixed
     */
    public function createVirtualParentIdForSubId(IIndexable $object, $subId)
    {
        return $object->getOSParentId();
    }

    /**
     * Gets object by id, can consider subIds and therefore return e.g. an array of values
     * always returns object itself - see also getObjectMockupById
     *
     * @param $objectId
     * @param $onlyMainObject - only returns main object
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
     * @param $objectId
     *
     * @return IIndexable | array
     */
    public function getObjectMockupById($objectId)
    {
        return $this->getObjectById($objectId);
    }

    /**
     * returns column type for id
     *
     * @param $isPrimary
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
