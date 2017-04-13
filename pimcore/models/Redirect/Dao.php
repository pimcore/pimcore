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
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param int $id
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM redirects WHERE id = ?', $this->model->getId());
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return bool
     *
     * @todo: update() don't returns anything
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->model->update();
        }

        return $this->create();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('redirects', ['id' => $this->model->getId()]);

        $this->model->clearDependentCache();
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        try {
            $ts = time();
            $this->model->setModificationDate($ts);

            $data = [];
            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->getValidTableColumns('redirects'))) {
                    if (is_bool($value)) {
                        $value = (int) $value;
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update('redirects', $data, ['id' => $this->model->getId()]);
        } catch (\Exception $e) {
            throw $e;
        }

        $this->model->clearDependentCache();
    }

    /**
     * Create a new record for the object in database
     *
     * @return bool
     */
    public function create()
    {
        $this->db->insert('redirects', []);

        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
