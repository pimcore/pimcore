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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Webservice_Data_Asset_File extends Webservice_Data_Asset {
    
    /**
     * @var string
     */
    public $data;
    
    
    public function map ($object) {
        parent::map($object);
        $this->data = base64_encode($object->getData());
    }


    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null) {

        $data = base64_decode($this->data);
        unset($this->data);
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);
        $object->setData($data);

    }
   
}
