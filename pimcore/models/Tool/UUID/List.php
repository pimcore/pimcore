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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_UUID_List extends Pimcore_Model_List_Abstract {

    public function isValidOrderKey($key){
        $resource = new Tool_UUID_Resource();
        $cols = $resource->getValidTableColumns(Tool_UUID_Resource::TABLE_NAME);
        return in_array($key,$cols);
    }

}