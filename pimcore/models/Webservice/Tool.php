<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Webservice;

class Tool {

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
                
                if(\Pimcore\Tool::classExists($class)) {
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

                    $classMap[str_replace("\\Pimcore\\Model\\Webservice\\Data\\","",$class)] = $class;
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
        } else if ($data instanceof \stdClass) {
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