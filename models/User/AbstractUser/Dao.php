<?php

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

namespace Pimcore\Model\User\AbstractUser;

use DateTime;
use Exception;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\User\AbstractUser $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id): void
    {
        if ($this->model->getType()) {
            $data = $this->db->fetchAssociative('SELECT * FROM users WHERE `type` = ? AND id = ?', [$this->model->getType(), $id]);
        } else {
            $data = $this->db->fetchAssociative('SELECT * FROM users WHERE `id` = ?', [$id]);
        }

        if ($data) {
            $data = $this->castUserDataToBoolean($data);
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException("user doesn't exist");
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName(string $name): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM users WHERE `type` = ? AND `name` = ?', [$this->model->getType(), $name]);

        if ($data) {
            $data = $this->castUserDataToBoolean($data);
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('User with name "%s" does not exist', $name));
        }
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByPasswordRecoveryToken(string $token): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM users WHERE `passwordRecoveryToken` = ?', [$token]);

        if ($data) {
            $data = $this->castUserDataToBoolean($data);
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf('Token does not match any user.'));
        }
    }

    private function castUserDataToBoolean(array $data): array
    {
        $data['admin'] = (bool)$data['admin'];
        $data['active'] = (bool)$data['active'];
        $data['welcomescreen'] = (bool)$data['welcomescreen'];
        $data['closeWarning'] = (bool)$data['closeWarning'];
        $data['memorizeTabs'] = (bool)$data['memorizeTabs'];
        $data['allowDirtyClose'] = (bool)$data['allowDirtyClose'];

        return $data;
    }

    public function create(): void
    {
        $this->db->insert('users', [
            'name' => $this->model->getName(),
            'type' => $this->model->getType(),
        ]);

        $this->model->setId((int) $this->db->lastInsertId());
    }

    /**
     * Quick test if there are children
     *
     */
    public function hasChildren(): bool
    {
        if (!$this->model->getId()) {
            return false;
        }

        $c = $this->db->fetchOne('SELECT id FROM users WHERE parentId = ?', [$this->model->getId()]);

        return (bool) $c;
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        if (strlen($this->model->getName()) < 2) {
            throw new Exception('Name of user/role must be at least 2 characters long');
        }

        $data = [];
        $dataRaw = $this->model->getObjectVars();
        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('users'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                } elseif (in_array($key, ['permissions', 'roles', 'docTypes', 'classes', 'perspectives', 'websiteTranslationLanguagesEdit', 'websiteTranslationLanguagesView'])) {
                    // permission and roles are stored as csv
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                } elseif (in_array($key, ['twoFactorAuthentication'])) {
                    $value = json_encode($value);
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('users', $data, ['id' => $this->model->getId()]);
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $userId = $this->model->getId();
        Logger::debug('delete user with ID: ' . $userId);

        $this->db->delete('users', ['id' => $userId]);
    }

    /**
     * @throws Exception
     */
    public function setLastLoginDate(): void
    {
        $data['lastLogin'] = (new DateTime())->getTimestamp();
        $this->db->update('users', $data, ['id' => $this->model->getId()]);
    }
}
