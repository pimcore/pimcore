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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IMysqlConfig;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\IRelationInterpreter;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Db\Connection;
use Pimcore\Logger;
use Pimcore\Model\Object\AbstractObject;

/**
 * @property IMysqlConfig $tenantConfig
 */
class DefaultMysql extends AbstractWorker implements IWorker
{
    /**
     * @var array
     */
    protected $_sqlChangeLog = [];

    /**
     * @var Helper\MySql
     */
    protected $mySqlHelper;

    public function __construct(IMysqlConfig $tenantConfig, Connection $db)
    {
        parent::__construct($tenantConfig, $db);

        $this->mySqlHelper = new Helper\MySql($tenantConfig, $db);
    }

    public function createOrUpdateIndexStructures()
    {
        $this->mySqlHelper->createOrUpdateIndexStructures();
    }

    public function deleteFromIndex(IIndexable $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach ($subObjectIds as $subObjectId => $object) {
            $this->doDeleteFromIndex($subObjectId, $object);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    protected function doDeleteFromIndex($subObjectId, IIndexable $object = null)
    {
        $this->db->deleteWhere($this->tenantConfig->getTablename(), 'o_id = ' . $this->db->quote($subObjectId));
        $this->db->deleteWhere($this->tenantConfig->getRelationTablename(), 'src = ' . $this->db->quote($subObjectId));
        if ($this->tenantConfig->getTenantRelationTablename()) {
            $this->db->deleteWhere($this->tenantConfig->getTenantRelationTablename(), 'o_id = ' . $this->db->quote($subObjectId));
        }
    }

    public function updateIndex(IIndexable $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach ($subObjectIds as $subObjectId => $object) {
            if ($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = \Pimcore::inAdmin();
                $b = AbstractObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                AbstractObject::setGetInheritedValues(true);
                $hidePublishedMemory = AbstractObject::doHideUnpublished();
                AbstractObject::setHideUnpublished(false);
                $categories = $this->tenantConfig->getCategories($object);
                $categoryIds = [];
                $parentCategoryIds = [];
                if ($categories) {
                    foreach ($categories as $c) {
                        if ($c instanceof AbstractCategory) {
                            $categoryIds[$c->getId()] = $c->getId();
                        }

                        $currentCategory = $c;
                        while ($currentCategory instanceof AbstractCategory) {
                            $parentCategoryIds[$currentCategory->getId()] = $currentCategory->getId();

                            if ($currentCategory->getOSProductsInParentCategoryVisible()) {
                                $currentCategory = $currentCategory->getParent();
                            } else {
                                $currentCategory = null;
                            }
                        }
                    }
                }

                ksort($categoryIds);

                $virtualProductId = $subObjectId;
                $virtualProductActive = $object->isActive();
                if ($object->getOSIndexType() == 'variant') {
                    $virtualProductId = $this->tenantConfig->createVirtualParentIdForSubId($object, $subObjectId);
                }

                $virtualProduct = AbstractObject::getById($virtualProductId);
                if ($virtualProduct && method_exists($virtualProduct, 'isActive')) {
                    $virtualProductActive = $virtualProduct->isActive();
                }

                $data = [
                    'o_id' => $subObjectId,
                    'o_classId' => $object->getClassId(),
                    'o_virtualProductId' => $virtualProductId,
                    'o_virtualProductActive' => $virtualProductActive,
                    'o_parentId' => $object->getOSParentId(),
                    'o_type' => $object->getOSIndexType(),
                    'categoryIds' => ',' . implode(',', $categoryIds) . ',',
                    'parentCategoryIds' => ',' . implode(',', $parentCategoryIds) . ',',
                    'priceSystemName' => $object->getPriceSystemName(),
                    'active' => $object->isActive(),
                    'inProductList' => $object->isActive(true)
                ];

                $relationData = [];

                foreach ($this->tenantConfig->getAttributes() as $attribute) {
                    try {
                        $value = $attribute->getValue($object, $subObjectId, $this->tenantConfig);

                        if (null !== $attribute->getInterpreter()) {
                            $value = $attribute->interpretValue($value);

                            if ($attribute->getInterpreter() instanceof IRelationInterpreter) {
                                foreach ($value as $v) {
                                    $relData = [];
                                    $relData['src'] = $subObjectId;
                                    $relData['src_virtualProductId'] = $virtualProductId;
                                    $relData['dest'] = $v['dest'];
                                    $relData['fieldname'] = $attribute->getName();
                                    $relData['type'] = $v['type'];
                                    $relationData[] = $relData;
                                }
                            } else {
                                $data[$attribute->getName()] = $value;
                            }
                        } else {
                            $data[$attribute->getName()] = $value;
                        }

                        if (is_array($data[$attribute->getName()])) {
                            $data[$attribute->getName()] = $this->convertArray($data[$attribute->getName()]);
                        }
                    } catch (\Exception $e) {
                        Logger::err('Exception in IndexService: ' . $e);
                    }
                }

                if ($a) {
                    \Pimcore::setAdminMode();
                }
                AbstractObject::setGetInheritedValues($b);
                AbstractObject::setHideUnpublished($hidePublishedMemory);

                try {
                    $this->mySqlHelper->doInsertData($data);
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index table: ' . $e);
                }

                try {
                    $this->db->deleteWhere($this->tenantConfig->getRelationTablename(), 'src = ' . $this->db->quote($subObjectId));
                    foreach ($relationData as $rd) {
                        $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                    }
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index relation table: ' . $e);
                }
            } else {
                Logger::info("Don't adding product " . $subObjectId . ' to index.');

                try {
                    $this->db->deleteWhere($this->tenantConfig->getTablename(), 'o_id = ' . $this->db->quote($subObjectId));
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index table: ' . $e);
                }

                try {
                    $this->db->deleteWhere($this->tenantConfig->getRelationTablename(), 'src = ' . $this->db->quote($subObjectId));
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index relation table: ' . $e);
                }

                try {
                    if ($this->tenantConfig->getTenantRelationTablename()) {
                        $this->db->deleteWhere($this->tenantConfig->getTenantRelationTablename(), 'o_id = ' . $this->db->quote($subObjectId));
                    }
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index tenant relation table: ' . $e);
                }
            }
            $subTenantData = $this->tenantConfig->prepareSubTenantEntries($object, $subObjectId);
            $this->tenantConfig->updateSubTenantEntries($object, $subTenantData, $subObjectId);
        }

        //cleans up all old zombie data
        $this->doCleanupOldZombieData($object, $subObjectIds);
    }

    protected function getValidTableColumns($table)
    {
        return $this->mySqlHelper->getValidTableColumns($table);
    }

    protected function getSystemAttributes()
    {
        return $this->mySqlHelper->getSystemAttributes();
    }

    public function __destruct()
    {
        $this->mySqlHelper->__destruct();
    }

    /**
     * returns product list implementation valid and configured for this worker/tenant
     *
     * @return mixed
     */
    public function getProductList()
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql($this->getTenantConfig());
    }
}
