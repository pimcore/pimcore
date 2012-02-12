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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_View_Helper_Url extends Zend_View_Helper_Url {
    
    
    public function url($urlOptions = array(), $name = null, $reset = false, $encode = true)
    {
        if(!$urlOptions) {
            $urlOptions = array();
        }

        if(!$name) {
            if(Staticroute::getCurrentRoute() instanceof Staticroute) {
                $name = Staticroute::getCurrentRoute()->getName();
            }
        }

        if($name && $route = Staticroute::getByName($name)) {
            return $route->assemble($urlOptions, $reset, $encode);
        }


        // this is to add support for arrays as values for the default Zend_View_Helper_Url
        $unset = array(); 
        foreach ($urlOptions as $optionName => $optionValues) {
            if (is_array($optionValues)) {
                foreach ($optionValues as $key => $value) {
                    $urlOptions[$optionName . "[" . $key . "]"] = $value;
                }
                $unset[] = $optionName;
            }
        }
        foreach ($unset as $optionName) {
            unset($urlOptions[$optionName]);
        }

        
        try {
            return parent::url($urlOptions, $name, $reset, $encode);
        } catch (Exception $e) {
            throw new Exception("Route '".$name."' for building the URL not found");
        }
    }    
}
