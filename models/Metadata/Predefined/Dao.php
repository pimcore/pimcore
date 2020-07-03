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
 * @package    Metadata
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Metadata\Predefined;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Metadata\Predefined $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('predefined-asset-metadata');
    }

    /**
     * @param int|null $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->getById($this->model->getId());

        if (isset($data['id'])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('Predefined asset metadata with id: ' . $this->model->getId() . ' does not exist');
        }
    }

    /**
     * @param string|null $name
     * @param string|null $language
     *
     * @throws \Exception
     */
    public function getByNameAndLanguage($name = null, $language = null)
    {
        $data = $this->db->fetchAll(function ($row) use ($name, $language) {
            $return = true;
            if ($name && $row['name'] != $name) {
                $return = false;
            }
            if ($language && $row['language'] != $language) {
                $return = false;
            }

            return $return;
        });

        if (count($data) && $data[0]['id']) {
            $this->assignVariablesToModel($data[0]);
        } else {
            throw new \Exception('Predefined asset metadata with name: ' . $name . ' and language: ' . $language . ' does not exist');
        }
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['id', 'name', 'description', 'language', 'type', 'data',
            'targetSubtype', 'config', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->db->insertOrUpdate($data, $this->model->getId());

        if (!$this->model->getId()) {
            $this->model->setId($this->db->getLastInsertId());
        }
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete($this->model->getId());
    }
}
