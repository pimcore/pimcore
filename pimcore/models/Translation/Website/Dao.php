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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation\Website;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Translation\Website $model
 */
class Dao extends Model\Translation\AbstractTranslation\Dao
{

    /**
     * @var string
     */
    public static $_tableName = "translations_website";

    /**
     * @return mixed
     */
    public static function getTableName()
    {
        return Model\Translation\Website\Dao::$_tableName;
    }
}
