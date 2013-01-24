<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 21.01.13
 * Time: 09:33
 * To change this template use File | Settings | File Templates.
 */
class Webservice_JsonEncoder {

    public function encode($data) {
        $data = Zend_Json::encode($data, null, array());

        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setHeader('Content-Type', 'application/json', true);
        $response->setBody($data);
        $response->sendResponse();
        exit;
    }

}