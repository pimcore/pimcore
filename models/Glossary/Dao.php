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
 * @package    Glossary
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Glossary;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Glossary $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param int|null $id
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow('SELECT * FROM glossary WHERE id = ?', $this->model->getId());

        if (!$data['id']) {
            throw new \Exception(sprintf('Unable to load glossary item with ID `%s`', $this->model->getId()));
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $this->create();
        }

        $this->update();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('glossary', ['id' => $this->model->getId()]);
    }

    /**
     * @throws \Exception
     */
    public function update()
    {
        $ts = time();
        $this->model->setModificationDate($ts);

        $data = [];
        $type = $this->model->getObjectVars();

        foreach ($type as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('glossary'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update('glossary', $data, ['id' => $this->model->getId()]);
    }

    /**
     * Create a new record for the object in database
     */
    public function create()
    {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert('glossary', []);

        $this->model->setId($this->db->lastInsertId());
    }
}
