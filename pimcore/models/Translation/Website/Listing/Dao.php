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
 * @package    Translation
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation\Website\Listing;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Translation\Website\Listing $model
 */
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
        return '\\Pimcore\\Model\\Translation\\Website';
    }
}
