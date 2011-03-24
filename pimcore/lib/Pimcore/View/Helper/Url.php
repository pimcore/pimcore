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
 * @package    Staticroute
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_View_Helper_Url extends Zend_View_Helper_Url {
    
    
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true)
    {
        if($route = Staticroute::getByName($name)) {
            return $route->assemble($urlOptions, $reset, $encode);
        }
        
        return parent::url($urlOptions, $name, $reset, $encode);
    }    
}
