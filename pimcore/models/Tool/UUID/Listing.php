<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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