<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\IndexService\Worker;

/**
 * Class \OnlineShop\Framework\IndexService\Worker\DefaultFindologic
 * @method \OnlineShop\Framework\IndexService\Config\IFindologicConfig getTenantConfig()
 */
class DefaultFindologic extends AbstractWorker implements IWorker, IBatchProcessingWorker
{
    use \OnlineShop\Framework\IndexService\Worker\WorkerTraits\BatchProcessing
    {
        \OnlineShop\Framework\IndexService\Worker\WorkerTraits\BatchProcessing::processUpdateIndexQueue as traitProcessUpdateIndexQueue;
    }

    use \OnlineShop\Framework\IndexService\Worker\WorkerTraits\MockupCache;


    const STORE_TABLE_NAME = "plugin_onlineshop_productindex_store_findologic";
    const EXPORT_TABLE_NAME = "plugin_onlineshop_productindex_export_findologic";
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
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return void
     */
    public function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object)
    {
        $this->doDeleteFromIndex( $object->getId(), $object );
    }


    /**
     * updates given element in index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return void
     */
    public function updateIndex(\OnlineShop\Framework\Model\IIndexable $object)
    {
        if(!$this->tenantConfig->isActive($object))
        {
            \Logger::info("Tenant {$this->name} is not active.");
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
        if(in_array('salesFrequency', $data['data']))
        {
            $xml->addChild('salesFrequencies')
                ->addChild('salesFrequency', (int)$data['data']['salesFrequency'])
            ;
        }
        if(in_array('dateAdded', $data['data']))
        {
            $xml->addChild('dateAddeds')
                ->addChild('dateAdded', date('c', $data['data']['dateAdded']))
            ;
        }


        /**
         * Adds a child with $value inside CDATA
         * @param SimpleXMLElement $parent
         * @param string           $name
         * @param null             $value
         *
         * @return SimpleXMLElement
         */
        $addChildWithCDATA = function (\SimpleXMLElement $parent, $name, $value = NULL) {
            $new_child = $parent->addChild($name);

            if ($new_child !== NULL) {
                $node = dom_import_simplexml($new_child);
                $no   = $node->ownerDocument;
                $node->appendChild($no->createCDATASection($value));
            }

            return $new_child;
        };


        // add default data
        foreach($data['data'] as $field => $value)
        {
            // skip empty values
            if((string)$value === '')
            {
                    continue;
            }
            $value = is_string($value) ? htmlspecialchars($value) : $value;


            if(in_array($field, $this->supportedFields))
            {
                // supported field
                switch($field)
                {
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

            }
            else
            {
                // unsupported, map all to attributes
                switch($field)
                {
                    // richtige reihenfolge der kategorie berücksichtigen
                    case 'categoryIds':
                        $value = trim($value, ',');
                        if($value)
                        {
                            $attribute = $attributes->addChild('attribute');
                            $attribute->addChild('key', 'cat');
                            $values = $attribute->addChild('values');
                            $categories = explode(',', $value);

                            foreach($categories as $c)
                            {
                                $categoryIds = [];
                                $parent = \Pimcore\Model\Object\Concrete::getById($c);
                                if ($parent != null)
                                {
                                    if($parent->getOSProductsInParentCategoryVisible())
                                    {
                                        while($parent && $parent instanceof \OnlineShop\Framework\Model\AbstractCategory)
                                        {
                                            $categoryIds[$parent->getId()] = $parent->getId();
                                            $parent = $parent->getParent();
                                        }
                                    } else {
                                        $categoryIds[$parent->getId()] = $parent->getId();
                                    }
                                }

                                $values->addChild('value', implode('_', array_reverse($categoryIds, true)));
                            }
                        }
                        break;

                    default:
                        if(!is_array($value))
                        {
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
        foreach($data['relations'] as $relation)
        {
            $groups[ $relation['fieldname'] ][] = $relation['dest'];
        }
        foreach($groups as $name => $values)
        {
            $attribute = $attributes->addChild('attribute');
            $attribute->addChild('key', $name);
            $v = $attribute->addChild('values');

            foreach($values as $value)
            {
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
    protected function doDeleteFromIndex($objectId, \OnlineShop\Framework\Model\IIndexable $object = null)
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
        $this->db->query( $query, [
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
     * @return \OnlineShop\Framework\IndexService\ProductList\DefaultFindologic
     */
    public function getProductList()
    {
        return new \OnlineShop\Framework\IndexService\ProductList\DefaultFindologic( $this->getTenantConfig() );
    }
}