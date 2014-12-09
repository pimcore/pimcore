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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Element;

use Pimcore\Model;

class Recyclebin extends Model\AbstractModel {
    
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
