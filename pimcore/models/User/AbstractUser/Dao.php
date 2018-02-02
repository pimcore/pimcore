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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\User\AbstractUser;

use Pimcore\Logger;
use Pimcore\Model;

/**
 * @property \Pimcore\Model\User\AbstractUser $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use Model\Element\ChildsCompatibilityTrait;

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getById($id)
    {
        if ($this->model->getType()) {
            $data = $this->db->fetchRow('SELECT * FROM users WHERE `type` = ? AND id = ?', [$this->model->getType(), $id]);
        } else {
            $data = $this->db->fetchRow('SELECT * FROM users WHERE `id` = ?', $id);
        }

        if (is_numeric($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("user doesn't exist");
        }
    }

    /**
     * @param $name
     *
     * @throws \Exception
     */
    public function getByName($name)
    {
        try {
            $data = $this->db->fetchRow('SELECT * FROM users WHERE `type` = ? AND `name` = ?', [$this->model->getType(), $name]);

            if ($data['id']) {
                $this->assignVariablesToModel($data);
            } else {
                throw new \Exception("user doesn't exist");
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function create()
    {
        try {
            $this->db->insert('users', [
                'name' => $this->model->getName(),
                'type' => $this->model->getType()
            ]);

            $this->model->setId($this->db->lastInsertId());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Quick test if there are children
     *
     * @return bool
     */
    public function hasChildren()
    {
        $c = $this->db->fetchOne('SELECT id FROM users WHERE parentId = ?', $this->model->getId());

        return (bool) $c;
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        try {
            if (strlen($this->model->getName()) < 2) {
                throw new \Exception('Name of user/role must be at least 2 characters long');
            }

            $data = [];
            $dataRaw = get_object_vars($this->model);
            foreach ($dataRaw as $key => $value) {
                if (in_array($key, $this->getValidTableColumns('users'))) {
                    if (is_bool($value)) {
                        $value = (int) $value;
                    } elseif (in_array($key, ['permissions', 'roles', 'docTypes', 'classes', 'perspectives', 'websiteTranslationLanguagesEdit', 'websiteTranslationLanguagesView'])) {
                        // permission and roles are stored as csv
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update('users', $data, ['id' => $this->model->getId()]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        $userId = $this->model->getId();
        Logger::debug('delete user with ID: ' . $userId);

        $this->db->delete('users', ['id' => $userId]);
    }
}
