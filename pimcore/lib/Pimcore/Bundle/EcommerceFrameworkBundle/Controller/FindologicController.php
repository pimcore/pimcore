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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FindologicController
 *
 * Routing see routing.yml
 */
class FindologicController extends FrontendController
{
    /**
     * create xml output for findologic
     */
    public function exportAction(Request $request)
    {
        // init
        $start = (int)$request->get('start');
        $count = (int)$request->get('count', 200);
        $shopKey = $request->get('shopkey');

        $db = \Pimcore\Db::getConnection();

        if ($request->get('id')) {
            $query = "SELECT SQL_CALC_FOUND_ROWS id, data FROM {$this->getExportTableName()} WHERE shop_key = :shop_key and id = :o_id LIMIT {$start}, {$count}";
            $items = $db->fetchAll($query, ['shop_key' => $shopKey, 'o_id' => $request->get('id')]);
        }
        // load export items
        else if ($request->get('type')) {
            $query = "SELECT SQL_CALC_FOUND_ROWS id, data FROM {$this->getExportTableName()} WHERE shop_key = :shop_key and type = :type LIMIT {$start}, {$count}";
            $items = $db->fetchAll($query, ['shop_key' => $shopKey, 'type' => $request->get('type')]);
        } else {
            $query = "SELECT SQL_CALC_FOUND_ROWS id, data FROM {$this->getExportTableName()} WHERE shop_key = :shop_key LIMIT {$start}, {$count}";
            $items = $db->fetchAll($query, ['shop_key' => $shopKey]);
        }

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
        foreach ($items as $row) {
            $xml .= $row['data'];

            $transmitIds[] = $row['id'];
        }

        // complete xml
        $xml .= <<<XML
    </items>
</findologic>
XML;

        // output
        if ($this->getParam('validate')) {
            $doc = new \DOMDocument();
            $doc->loadXML($xml);

            $response = new Response();
            var_dump($doc->schemaValidate('bundles/pimcoreecommerceframework/vendor/findologic/export.xsd'));
        } else {
            $response = new Response($xml);
            $response->headers->set('Content-Type', 'text/xml');

            // mark items as transmitted
            if ($transmitIds) {
                $db->query(sprintf('UPDATE %1$s SET last_transmit = now() WHERE id in(%2$s)', $this->getExportTableName(), implode(',', $transmitIds)
                ));
            }
        }

        return $response;
    }

    /**
     * @return string
     */
    protected function getExportTableName()
    {
        return 'ecommerceframework_productindex_export_findologic';
    }
}
