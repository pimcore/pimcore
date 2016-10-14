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
 * @package    User
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\Permission;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\User\Permission\Definition\Dao getDao()
 */
class Definition extends Model\AbstractModel
{
    public $key;

    /**
     * @param array
     */
    public function __construct($data = [])
    {
        if (is_array($data) && !empty($data)) {
            $this->setValues($data);
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @param $permission
     * @return mixed
     * @throws \Exception
     */
    public static function getByKey($permission)
    {
        if (!$permission) {
            throw new \Exception("No permisson defined.");
        }
        $list = new Definition\Listing();
        $list->setCondition("`key`=?", [$permission]);
        $list->setLimit(1);
        $permissionDefinition = $list->load();
        if ($permissionDefinition[0]) {
            return $permissionDefinition[0];
        }
    }

    /**
     * @param $permission
     * @return mixed|static
     * @throws \Exception
     */
    public static function create($permission)
    {
        if (!$permission) {
            throw new \Exception("No permisson defined.");
        }
        $permissionDefinition = static::getByKey($permission);
        if ($permissionDefinition instanceof self) {
            Logger::info("Permission $permission allready exists. Skipping creation.");

            return $permissionDefinition;
        } else {
            $permissionDefinition = new static();
            $permissionDefinition->setKey($permission);
            $permissionDefinition->save();

            return $permissionDefinition;
        }
    }
}
