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

class Pimcore_Tool_Frontend {
    
    /**
     * Returns the Website-Config
     * @return Zend_Config
     * @depricated
     */
    public static function getWebsiteConfig () {
        return Pimcore_Config::getWebsiteConfig();
    }
}
