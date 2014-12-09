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

namespace Pimcore\Model\Webservice\Data\Asset;

use Pimcore\Model;

class File extends Model\Webservice\Data\Asset {
    
    /**
     * @var string
     */
    public $data;
    
    
    public function map ($object, $options = null) {
        parent::map($object, $options);
        if (is_array($options)) {
            if ($options["LIGHT"]) {
                return;
            }
        }
        $this->data = base64_encode($object->getData());
    }


    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null) {

        $data = base64_decode($this->data);
        unset($this->data);
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);
        $object->setData($data);

    }
   
}
