<?php 
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

chdir(__DIR__);

include_once("startup.php");

use Pimcore\Model\Search;

// clear all data
$db = \Pimcore\Resource::get();
$db->query("TRUNCATE `search_backend_data`;");

$elementsPerLoop = 100;
$types = array("asset","document","object");

foreach ($types as $type) {
    $listClassName = "\\Pimcore\\Model\\" . ucfirst($type) . "\\Listing";
    $list = new $listClassName();
    if(method_exists($list, "setUnpublished")) {
        $list->setUnpublished(true);
    }

    $elementsTotal = $list->getTotalCount();

    for($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
        $list->setLimit($elementsPerLoop);
        $list->setOffset($i*$elementsPerLoop);

        echo "Processing " .$type . ": " . ($list->getOffset()+$elementsPerLoop) . "/" . $elementsTotal . "\n";

        $elements = $list->load();
        foreach ($elements as $element) {
            try {
                $searchEntry = Search\Backend\Data::getForElement($element);
                if($searchEntry instanceof Search\Backend\Data and $searchEntry->getId() instanceof Search\Backend\Data_Id ) {
                    $searchEntry->setDataFromElement($element);
                } else {
                    $searchEntry = new Search\Backend\Data($element);
                }

                $searchEntry->save();
            } catch (Exception $e) {
                \Logger::err($e);
            }
        }
        \Pimcore::collectGarbage();
    }
}

$db->query("OPTIMIZE TABLE search_backend_data;");

