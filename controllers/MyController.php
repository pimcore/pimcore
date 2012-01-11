<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cfasching
 * Date: 03.06.11
 * Time: 09:49
 * To change this template use File | Settings | File Templates.
 */
 
class OnlineShop_MyController extends Website_Controller_Action {


    public function testAction() {

//        $t = new OnlineShop_Framework_AbstractProduct();
//        p_r($t);

        $x = OnlineShop_Framework_Factory::getInstance();
//        p_r($x);

        $e = $x->getEnvironment();
        $e->setCurrentUserId(-1);
        p_r($e);

//        p_r($e->getAllCustomItems());

        $cm = $x->getCartManager();
//        $key = $cm->createCart(array("name" => "mycart"));


        $cm->addToCart(Object_Concrete::getById(430), 4, 14, array()); //array("key" => 'mycart', "itemid" => 4459, "count" => 4));



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
