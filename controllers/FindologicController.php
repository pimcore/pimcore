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


class OnlineShop_FindologicController extends Pimcore\Controller\Action\Frontend
{
    /**
     * create xml output for findologic
     */
    public function exportAction()
    {
        // init
        $start = (int)$this->getParam('start');
        $count = (int)$this->getParam('count', 200);
        $shopKey = $this->getParam('shopkey');
        $db = Pimcore\Resource::getConnection();


        // load export items
        $query = <<<SQL
SELECT SQL_CALC_FOUND_ROWS id, data
FROM {$this->getExportTableName()}
WHERE shop_key = :shop_key
LIMIT {$start}, {$count}
SQL;
        $items = $db->fetchAll($query, ['shop_key' => $shopKey]);


        // get counts
        $indexCount = $db->fetchOne('SELECT FOUND_ROWS()');
        $itemCount = count($items);


        // create xml header
        $xml = <<<XML
<?xml version="1.0"?>
<findologic version="0.9">
    <items start="{$start}" count="{$itemCount}" total="{$indexCount}">
XML;


        // add items
        $transmitIds = [];
        foreach($items as $row)
        {
            $xml .= $row['data'];

            $transmitIds[] = $row['id'];
        }


        // complete xml
        $xml .= <<<XML
    </items>
</findologic>
XML;


        // output
        if( $this->getParam('validate') )
        {
            $doc = new DOMDocument();
            $doc->loadXML( $xml );

            var_dump( $doc->schemaValidate('plugins/OnlineShop/static/vendor/findologic/export.xsd') );
        }
        else
        {
            $this->getResponse()->setHeader('Content-Type', 'text/xml');
            echo $xml;


            // mark items as transmitted
            if($transmitIds)
            {
                $db->query(sprintf('UPDATE %1$s SET last_transmit = now() WHERE id in(%2$s)'
                    , $this->getExportTableName()
                    , implode(',', $transmitIds)
                ));
            }
        }


        // disable output
        $this->disableViewAutoRender();
    }


    /**
     * @return string
     */
    protected function getExportTableName()
    {
        return 'plugin_onlineshop_productindex_export_findologic';
    }
}
