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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\MysqlConfigInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter\RelationInterpreterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @property MysqlConfigInterface $tenantConfig
 *
 * @method MysqlConfigInterface getTenantConfig()
 */
class DefaultMysql extends AbstractWorker implements WorkerInterface
{
    protected array $_sqlChangeLog = [];

    protected Helper\MySql $mySqlHelper;

    protected LoggerInterface $logger;

    public function __construct(MysqlConfigInterface $tenantConfig, Connection $db, EventDispatcherInterface $eventDispatcher, LoggerInterface $pimcoreEcommerceSqlLogger)
    {
        parent::__construct($tenantConfig, $db, $eventDispatcher);

        $this->logger = $pimcoreEcommerceSqlLogger;
        $this->mySqlHelper = new Helper\MySql($tenantConfig, $db);
    }

    public function createOrUpdateIndexStructures(): void
    {
        $this->mySqlHelper->createOrUpdateIndexStructures();
    }

    public function deleteFromIndex(IndexableInterface $object): void
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

    protected function doDeleteFromIndex(int $subObjectId, IndexableInterface $object = null): void
    {
        $this->db->delete($this->tenantConfig->getTablename(), ['id' => $subObjectId]);
        $this->db->delete($this->tenantConfig->getRelationTablename(), ['src' => $subObjectId]);
        if ($this->tenantConfig->getTenantRelationTablename()) {
            $this->db->delete($this->tenantConfig->getTenantRelationTablename(), ['id' => $subObjectId]);
        }
    }

    public function updateIndex(IndexableInterface $object): void
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $subObjectIds = $this->tenantConfig->createSubIdsForObject($object);

        foreach ($subObjectIds as $subObjectId => $object) {
            if ($object->getOSDoIndexProduct() && $this->tenantConfig->inIndex($object)) {
                $a = \Pimcore::inAdmin();
                $b = DataObject::doGetInheritedValues();
                \Pimcore::unsetAdminMode();
                DataObject::setGetInheritedValues(true);
                $hidePublishedMemory = DataObject::doHideUnpublished();
                DataObject::setHideUnpublished(false);
                $categories = $this->tenantConfig->getCategories($object, $subObjectId);
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
                if ($object->getOSIndexType() == ProductListInterface::PRODUCT_TYPE_VARIANT) {
                    $virtualProductId = $this->tenantConfig->createVirtualParentIdForSubId($object, $subObjectId);
                }

                $virtualProduct = DataObject::getById($virtualProductId);
                if ($virtualProduct && method_exists($virtualProduct, 'isActive')) {
                    $virtualProductActive = $virtualProduct->isActive();
                }

                $data = [
                    'id' => $subObjectId,
                    'classId' => $object->getClassId(),
                    'virtualProductId' => $virtualProductId,
                    'virtualProductActive' => $virtualProductActive,
                    'parentId' => $object->getOSParentId(),
                    'type' => $object->getOSIndexType(),
                    'categoryIds' => ',' . implode(',', $categoryIds) . ',',
                    'parentCategoryIds' => ',' . implode(',', $parentCategoryIds) . ',',
                    'priceSystemName' => $object->getPriceSystemName(),
                    'active' => $object->isActive(),
                    'inProductList' => $object->isActive(true),
                ];

                $relationData = [];

                foreach ($this->tenantConfig->getAttributes() as $attribute) {
                    try {
                        $value = $attribute->getValue($object, $subObjectId, $this->tenantConfig);

                        if (null !== $attribute->getInterpreter()) {
                            $value = $attribute->interpretValue($value);

                            if ($attribute->getInterpreter() instanceof RelationInterpreterInterface) {
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

                        if (isset($data[$attribute->getName()]) && is_array($data[$attribute->getName()])) {
                            $data[$attribute->getName()] = $this->convertArray($data[$attribute->getName()]);
                        }
                    } catch (\Exception $e) {
                        Logger::err('Exception in IndexService: ' . $e);
                    }
                }

                if ($a) {
                    \Pimcore::setAdminMode();
                }
                DataObject::setGetInheritedValues($b);
                DataObject::setHideUnpublished($hidePublishedMemory);

                try {
                    $this->mySqlHelper->doInsertData($data);
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index table: ' . $e);
                }

                try {
                    $this->db->delete($this->tenantConfig->getRelationTablename(), ['src' => $subObjectId]);
                    foreach ($relationData as $rd) {
                        $this->db->insert($this->tenantConfig->getRelationTablename(), $rd);
                    }
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index relation table: ' . $e);
                }
            } else {
                Logger::info("Don't adding product " . $subObjectId . ' to index.');

                try {
                    $this->db->delete($this->tenantConfig->getTablename(), ['id' => $subObjectId]);
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index table: ' . $e);
                }

                try {
                    $this->db->delete($this->tenantConfig->getRelationTablename(), ['src' => $subObjectId]);
                } catch (\Exception $e) {
                    Logger::warn('Error during updating index relation table: ' . $e);
                }

                try {
                    if ($this->tenantConfig->getTenantRelationTablename()) {
                        $this->db->delete($this->tenantConfig->getTenantRelationTablename(), ['id' => $subObjectId]);
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

    /**
     * @return string[]
     */
    protected function getValidTableColumns(string $table): array
    {
        return $this->mySqlHelper->getValidTableColumns($table);
    }

    /**
     * @return string[]
     */
    protected function getSystemAttributes(): array
    {
        return $this->mySqlHelper->getSystemAttributes();
    }

    public function __destruct()
    {
        $this->mySqlHelper->__destruct();
    }

    public function getProductList(): \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultMysql($this->getTenantConfig(), $this->logger);
    }
}
