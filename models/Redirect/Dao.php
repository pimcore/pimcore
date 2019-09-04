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
 * @package    Redirect
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Redirect;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Redirect $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM redirects WHERE id = ?', $this->model->getId());

        if (!$data['id']) {
            throw new \Exception(sprintf('Redirect with ID %d doesn\'t exist', $this->model->getId()));
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            // create in database
            $this->db->insert('redirects', []);

            $this->model->setId($this->db->lastInsertId());
        }

        $this->updateModificationInfos();

        $data = [];
        $type = $this->model->getObjectVars();

        foreach ($type as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('redirects'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('redirects', $data, ['id' => $this->model->getId()]);
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('redirects', ['id' => $this->model->getId()]);
    }

    protected function updateModificationInfos()
    {
        $updateTime = time();
        $this->model->setModificationDate($updateTime);

        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($updateTime);
        }

        // auto assign user if possible, if no user present, use ID=0 which represents the "system" user
        $userId = 0;
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        if ($user instanceof Model\User) {
            $userId = $user->getId();
        }
        $this->model->setUserModification($userId);

        if ($this->model->getUserOwner() === null) {
            $this->model->setUserOwner($userId);
        }
    }
}
