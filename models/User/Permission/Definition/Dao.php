<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\User\Permission\Definition;

use Exception;
use Pimcore\Db\Helper;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\User\Permission\Definition $model
 */
class Dao extends Model\Dao\AbstractDao
{
    public function save(): void
    {
        try {
            Helper::upsert($this->db, 'users_permission_definitions', [
                'key' => $this->model->getKey(),
                'category' => $this->model->getCategory() ? $this->model->getCategory() : '',
            ], $this->getPrimaryKey('users_permission_definitions'));
        } catch (Exception $e) {
            Logger::warn((string) $e);
        }
    }
}
