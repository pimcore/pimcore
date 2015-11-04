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
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Translation\Admin;

use Pimcore\Model;

class Dao extends Model\Translation\AbstractTranslation\Dao {

    /**
     * @var string
     */
    public static $_tableName = "translations_admin";

    /**
     * @return string
     */
    public static function getTableName(){
        return Model\Translation\Admin\Dao::$_tableName;
    }
}
