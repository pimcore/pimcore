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
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Element_Recyclebin extends Pimcore_Model_Abstract {
    
    public function flush () {
        $this->getResource()->flush();
        
        $files = scandir(PIMCORE_RECYCLEBIN_DIRECTORY);
        foreach ($files as $file) {
            if (is_file(PIMCORE_RECYCLEBIN_DIRECTORY . "/" . $file)) {
                unlink(PIMCORE_RECYCLEBIN_DIRECTORY . "/" . $file);
            }
        }
    }
}
