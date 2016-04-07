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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


class EcommerceFramework_MyController extends Website_Controller_Action {


    public function testAction() {

//        $t = new \OnlineShop\Framework\Model\AbstractProduct();
//        p_r($t);

        $x = \OnlineShop\Framework\Factory::getInstance();
//        p_r($x);

        $e = $x->getEnvironment();
        $e->setCurrentUserId(-1);
        p_r($e);

//        p_r($e->getAllCustomItems());

        $cm = $x->getCartManager();
//        $key = $cm->createCart(array("name" => "mycart"));


        $cm->addToCart(\Pimcore\Model\Object\Concrete::getById(430), 4, 14, array()); //array("key" => 'mycart', "itemid" => 4459, "count" => 4));



//        $e->setCustomItem("myitem2", "88776688");
//        $e->save();

        $e = $x->getEnvironment();

        p_r($e);



        $x->saveState();

//        $p = new OnlineShop_Plugin();
//        p_r($p);

        die("meins");

    }
}
