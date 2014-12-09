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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model;

class Listing extends Model\Listing\AbstractListing {

    public function isValidOrderKey($key){
        $resource = new Model\Tool\UUID\Resource();
        $cols = $resource->getValidTableColumns(Model\Tool\UUID\Resource::TABLE_NAME);
        return in_array($key,$cols);
    }

}