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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


class Webservice_Tool {

    /**
     * @static
     * @return array
     */
    public static function createClassMappings(){

        $modelsDir = PIMCORE_PATH."/models/";
        $files = rscandir($modelsDir);
        $includePatterns = array(
            "/Webservice\/Data/"
        );

        foreach ($files as $file) {
            if(is_file($file)) {

                $file = str_replace($modelsDir,"",$file);
                $file = str_replace(".php","",$file);
                $class = str_replace(DIRECTORY_SEPARATOR,"_",$file);
                
                if(Pimcore_Tool::classExists($class)) {
                    $match = false;
                    foreach ($includePatterns as $pattern) {
                       if(preg_match($pattern,$file)) {
                            $match = true;
                            break;
                        }
                    }

                    if(strpos($file, "Webservice".DIRECTORY_SEPARATOR."Data") !== false) {
                        $match = true;
                    }

                    if(!$match) {
                        continue;
                    }

                    $classMap[str_replace("Webservice_Data_","",$class)] = $class;
                }
            }
        }
        return $classMap;
    }
    
    public static function keyValueReverseMapping ($data) {
        if(is_array($data)) {
            $values = array();
            foreach ($data as $k=>$d) {
                $values[$k] = self::keyValueReverseMapping($d);

            }
            return $values;
        } else if ($data instanceof stdClass) {
            if($data->key) {
                return array($data->key => self::keyValueReverseMapping($data->value));
            }
            if($data->item) {
                $values = array();
                foreach ($data->item as $item) {
                    $values = array_merge($values,self::keyValueReverseMapping($item));
                }
                return $values;
            }
        } else {
            return $data;
        }
    }

}