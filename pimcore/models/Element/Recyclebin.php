<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Element
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
