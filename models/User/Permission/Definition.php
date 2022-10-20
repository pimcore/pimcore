<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\User\Permission;

use Pimcore\Logger;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\User\Permission\Definition\Dao getDao()
 * @method void save()
 */
class Definition extends Model\AbstractModel
{
    protected ?string $key;

    protected ?string $category;

    public function __construct(array $data = [])
    {
        if (is_array($data) && !empty($data)) {
            $this->setValues($data);
        }
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): Definition
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @param string $permission
     *
     * @return Definition|null
     *
     * @throws \Exception
     */
    public static function getByKey(string $permission): ?Definition
    {
        if (!$permission) {
            throw new \Exception('No permisson defined.');
        }
        $list = new Definition\Listing();
        $list->setCondition('`key`=?', [$permission]);
        $list->setLimit(1);
        $permissionDefinition = $list->load();

        if (1 === count($permissionDefinition)) {
            return $permissionDefinition[0];
        }

        return null;
    }

    /**
     * @param string $permission
     *
     * @return self|static
     *
     * @throws \Exception
     */
    public static function create(string $permission): self|static
    {
        if (!$permission) {
            throw new \Exception('No permisson defined.');
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
