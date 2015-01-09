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

namespace Pimcore\Model\Webservice\Data;

use Pimcore\Tool; 
use Pimcore\Model;

abstract class Mapper {

    /**
     * @param $object
     * @param $type
     * @return null|string
     * @throws \Exception
     */
    public static function findWebserviceClass($object, $type) {

        $mappingClasses = array(
            "Asset\\File",
            "Asset\\Folder",
            "Document\\Folder",
            "Document\\Page",
            "Document\\Snippet",
            "Document\\Link",
            "Document\\Hardlink",
            "Document\\Email",
            "Object\\Folder",
            "Object\\Concrete"
        );

        $retVal = null;
        if($object instanceof Model\Property){
            $retVal = "\\Pimcore\\Model\\Webservice\\Data\\Property";
        } else if ($object instanceof Model\Document\Tag) {
            $retVal = "\\Pimcore\\Model\\Webservice\\Data\\Document\\Element";
        } else if (is_object($object)) {
            $orgclass = str_replace("Pimcore\\Model\\", "", get_class($object));

            if (in_array($orgclass,$mappingClasses)) {
                $apiclass = "\\Pimcore\\Model\\Webservice\\Data\\" . $orgclass . "\\" . ucfirst($type);
                if (!Tool::classExists($apiclass)) {
                    $apiclass = "\\Pimcore\\Model\\Webservice\\Data\\" . $orgclass;
                    if (!Tool::classExists($apiclass)) {
                        throw new \Exception("Webservice\\Data\\Mapper: no API class found for [ " . $orgclass . " ]");
                    }
                }
            } else {
                $apiclass = $orgclass;
            }
            $retVal = $apiclass;
        } else $retVal = "Array";
        return $retVal;
    }

    /**
     * @param $object
     * @param $apiclass
     * @param $type
     * @param null $options
     * @return array|string
     * @throws \Exception
     */
    public static function map($object, $apiclass, $type, $options = null) {
        if($object instanceof \Zend_Date){
            $object=$object->toString();
        } else if (is_object($object)) {
            if (Tool::classExists($apiclass)) {
                $new = new $apiclass();
                if (method_exists($new, "map")) {
                    $new->map($object, $options);
                    $object = $new;
                }
            } else {
                throw new \Exception("Webservice\\Data\\Mapper: Cannot map [ $apiclass ] - class does not exist");
            }
        }
        else if (is_array($object)) {
            $tmpArray = array();
            foreach ($object as $v) {
                $className = self::findWebserviceClass($v, $type);
                $tmpArray[] = self::map($v, $className, $type);
            }
            $object = $tmpArray;
        }

        return $object;
    }

    /**
     * @param $el
     * @return \stdClass
     */
    public static function toObject($el) {
        if (is_object($el)) {
            $el = object2array($el);
        }

        $obj = new \stdClass();
        foreach ($el as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;
    }

}
