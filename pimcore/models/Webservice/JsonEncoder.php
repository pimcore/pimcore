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
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Webservice;

class JsonEncoder {

    public function encode($data,$returnData = false) {

        $data = \Pimcore\Tool\Serialize::removeReferenceLoops($data);
        $data = \Zend_Json::encode($data, null, array());

        if($returnData){
            return $data;
        }else{
            $response = \Zend_Controller_Front::getInstance()->getResponse();
            $response->setHeader('Content-Type', 'application/json', true);
            $response->setBody($data);
            $response->sendResponse();
            exit;
        }
    }

    public function decode($data){
        $data = \Zend_Json::decode($data);
        return $data;
    }
}