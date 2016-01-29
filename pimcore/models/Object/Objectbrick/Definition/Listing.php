<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Objectbrick\Definition;

use Pimcore\Model;

class Listing
{

    /**
     * @return array
     */
    public function load()
    {
        $fields = array();
        $objectBricksFolder = PIMCORE_CLASS_DIRECTORY . "/objectbricks";
        
        if (is_dir($objectBricksFolder)) {
            $files = scandir($objectBricksFolder);
            
            foreach ($files as $file) {
                $file = $objectBricksFolder . "/" . $file;
                if (is_file($file)) {
                    $fieldData = file_get_contents($file);
                    $fields[] = \Pimcore\Tool\Serialize::unserialize($fieldData);
                }
            }
        }
        
        return $fields;
    }
}
