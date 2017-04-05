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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;
use Pimcore\Logger;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultFindologic
 * @method \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\IFindologicConfig getTenantConfig()
 */
class DefaultFindologic extends AbstractMockupCacheWorker implements IWorker, IBatchProcessingWorker
{
    const STORE_TABLE_NAME = "ecommerceframework_productindex_store_findologic";
    const EXPORT_TABLE_NAME = "ecommerceframework_productindex_export_findologic";
    const MOCKUP_CACHE_PREFIX = "ecommerce_mockup_findologic";


    /**
     * findologic supported fields
     * @var array
     */
    protected $supportedFields = [
        'id'
        , 'ordernumber'
        , 'name'
        , 'summary'
        , 'description'
        , 'price'
    ];


    /**
     * @var \SimpleXMLElement
     */
    protected $batchData;


    /**
     * creates or updates necessary index structures (like database tables and so on)
     *
     * @return void
     */
    public function createOrUpdateIndexStructures()
    {
        $this->createOrUpdateStoreTable();
    }

    /**
     * deletes given element from index
     *
     * @param IIndexable $object
     *
     * @return void
     */
    public function deleteFromIndex(IIndexable $object)
    {
        $this->doDeleteFromIndex($object->getId(), $object);
    }


    /**
     * updates given element in index
     *
     * @param IIndexable $object
     *
     * @return void
     */
    public function updateIndex(IIndexable $object)
    {
        if (!$this->tenantConfig->isActive($object)) {
            Logger::info("Tenant {$this->name} is not active.");

            return;
        }

        $this->prepareDataForIndex($object);
        $this->fillupPreparationQueue($object);
    }


    /**
     * @param      $objectId
     * @param null $data
     */
    protected function doUpdateIndex($objectId, $data = null)
    {
        $xml = $this->createXMLElement();

        $xml->addAttribute('id', $objectId);
        $xml->addChild('allOrdernumbers')
            ->addChild('ordernumbers');
        $xml->addChild('names');
        $xml->addChild('summaries');
        $xml->addChild('descriptions');
        $xml->addChild('prices');
        $xml->addChild('allAttributes')
            ->addChild('attributes');


        $attributes = $xml->allAttributes->attributes;
        /* @var \SimpleXMLElement $attributes */


        // add optional fields
        if (array_key_exists('salesFrequency', $data['data'])) {
            $xml->addChild('salesFrequencies')
                ->addChild('salesFrequency', (int)$data['data']['salesFrequency'])
            ;
        }
        if (array_key_exists('dateAdded', $data['data'])) {
            $xml->addChild('dateAddeds')
                ->addChild('dateAdded', date('c', $data['data']['dateAdded']))
            ;
        }


        /**
         * Adds a child with $value inside CDATA
         * @param \SimpleXMLElement $parent
         * @param string           $name
         * @param null             $value
         *
         * @return \SimpleXMLElement
         */
        $addChildWithCDATA = function (\SimpleXMLElement $parent, $name, $value = null) {
            $new_child = $parent->addChild($name);

            if ($new_child !== null) {
                $node = dom_import_simplexml($new_child);
                $no   = $node->ownerDocument;
                $node->appendChild($no->createCDATASection($value));
            }

            return $new_child;
        };


        // add default data
        foreach ($data['data'] as $field => $value) {
            // skip empty values
            if ((string)$value === '') {
                continue;
            }
            $value = is_string($value) ? htmlspecialchars($value) : $value;


            if (in_array($field, $this->supportedFields)) {
                // supported field
                switch ($field) {
                    case 'ordernumber':
                        $parent = $xml->allOrdernumbers->ordernumbers;
                        $parent->addChild('ordernumber', $value);
                        break;

                    case 'name':
                        $parent = $xml->names;
                        $parent->addChild('name', $value);
                        break;

                    case 'summary':
                        $parent = $xml->summaries;
                        $parent->addChild('summary', $value);
                        break;

                    case 'description':
                        $parent = $xml->descriptions;
                        $parent->addChild('description', $value);
                        break;

                    case 'price':
                        $parent = $xml->prices;
                        $parent->addChild('price', $value);
                        break;
                }
            } else {
                // unsupported, map all to attributes
                switch ($field) {
                    // richtige reihenfolge der kategorie berücksichtigen
                    case 'categoryIds':
                        $value = trim($value, ',');
                        if ($value) {
                            $attribute = $attributes->addChild('attribute');
                            $attribute->addChild('key', 'cat');
                            $values = $attribute->addChild('values');
                            $categories = explode(',', $value);

                            foreach ($categories as $c) {
                                $categoryIds = [];

                                $currentCategory = \Pimcore\Model\Object\Concrete::getById($c);
                                while ($currentCategory instanceof \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory) {
                                    $categoryIds[$currentCategory->getId()] = $currentCategory->getId();

                                    if ($currentCategory->getOSProductsInParentCategoryVisible()) {
                                        $currentCategory = $currentCategory->getParent();
                                    } else {
                                        $currentCategory = null;
                                    }
                                }

                                $values->addChild('value', implode('_', array_reverse($categoryIds, true)));
                            }
                        }
                        break;

                    default:
                        if (!is_array($value)) {
                            $attribute = $attributes->addChild('attribute');

                            $attribute->addChild('key', $field);
                            $values = $attribute->addChild('values');
                            $addChildWithCDATA($values, 'value', $value);
                        }
                }
            }
        }


        // add relations
        $groups = [];
        foreach ($data['relations'] as $relation) {
            $groups[ $relation['fieldname'] ][] = $relation['dest'];
        }
        foreach ($groups as $name => $values) {
            $attribute = $attributes->addChild('attribute');
            $attribute->addChild('key', $name);
            $v = $attribute->addChild('values');

            foreach ($values as $value) {
                $v->addChild('value', $value);
            }
        }


        // update export item
        $this->updateExportItem($objectId, $xml);


        // create / update mockup cache
        $this->saveToMockupCache($objectId, $data);
    }


    /**
     * @param int $objectId
     */
    protected function doDeleteFromIndex($objectId, IIndexable $object = null)
    {
        $this->db->query(sprintf('DELETE FROM %1$s WHERE id = %2$d', $this->getExportTableName(), $objectId));
        $this->db->query(sprintf('DELETE FROM %1$s WHERE id = %2$d', $this->getStoreTableName(), $objectId));
    }


    /**
     * @param int              $objectId
     * @param \SimpleXMLElement $item
     */
    protected function updateExportItem($objectId, \SimpleXMLElement $item)
    {
        // save
        $query = <<<SQL
INSERT INTO {$this->getExportTableName()} (`id`, `shop_key`, `data`, `last_update`)
VALUES (:id, :shop_key, :data, now())
ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `last_update` = VALUES(`last_update`)
SQL;
        $this->db->query($query, [
            'id' => $objectId
            , 'shop_key' => $this->getTenantConfig()->getClientConfig('shopKey')
            , 'data' => str_replace('<?xml version="1.0"?>', '', $item->saveXML())
        ]);
    }


    /**
     * @return string
     */
    protected function getStoreTableName()
    {
        return self::STORE_TABLE_NAME;
    }

    /**
     * @return string
     */
    protected function getMockupCachePrefix()
    {
        return self::MOCKUP_CACHE_PREFIX;
    }

    /**
     * @return string
     */
    protected function getExportTableName()
    {
        return self::EXPORT_TABLE_NAME;
    }


    /**
     * @return \SimpleXMLElement
     */
    protected function createXMLElement()
    {
        return new \SimpleXMLElement('<?xml version="1.0"?><item />');
    }


    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultFindologic
     */
    public function getProductList()
    {
        return new \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\DefaultFindologic($this->getTenantConfig());
    }
}
