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
 * @package    Object_Objectbrick
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick_Definition_List {
    
    
    public function load () {
        
        $fields = array();
        $objectBricksFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";
        
        if(is_dir($objectBricksFolder)) {
            $files = scandir($objectBricksFolder);
            
            foreach ($files as $file) {
                $file = $objectBricksFolder . "/" . $file;
                if(is_file($file)) {
                    $fieldData = file_get_contents($file);
                    $fields[] = Pimcore_Tool_Serialize::unserialize($fieldData);
                }
            }
        }
        
        return $fields;
    }
    
}
