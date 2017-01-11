<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\UUID;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Tool\UUID\Listing\Dao getDao()
 */
class Listing extends Model\Listing\AbstractListing
{
    public function isValidOrderKey($key)
    {
        $resource = new Model\Tool\UUID\Dao();
        $cols = $resource->getValidTableColumns(Model\Tool\UUID\Dao::TABLE_NAME);

        return in_array($key, $cols);
    }
}
