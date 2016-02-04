<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Translation
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Translation\Website\Listing;

use Pimcore\Model;

class Dao extends Model\Translation\AbstractTranslation\Listing\Dao
{
    /**
     * Loads a list of translations for the specified parameters, returns an array of Translation elements
     *
     * @return array
     */

    public static function getTableName()
    {
        return Model\Translation\Website\Dao::$_tableName;
    }

    /**
     * @return string
     */
    public static function getItemClass()
    {
        return "\\Pimcore\\Model\\Translation\\Website";
    }
}
