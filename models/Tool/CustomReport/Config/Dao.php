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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Config;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Tool\CustomReport\Config $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('custom-reports');
    }

    /**
     * @param string|null $id
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName($id = null)
    {
        if ($id != null) {
            $this->model->setName($id);
        }

        $data = $this->db->getById($this->model->getName());

        if (isset($data['id'])) {
            $this->assignVariablesToModel($data);
            $this->model->setName($data['id']);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Custom report config with name "%s" does not exist.',
                $this->model->getName()
            ));
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
        $allowedProperties = ['name', 'sql', 'dataSourceConfig', 'columnConfiguration', 'niceName', 'group', 'xAxis',
            'groupIconClass', 'iconClass', 'reportClass', 'creationDate', 'modificationDate', 'menuShortcut', 'chartType', 'pieColumn',
            'pieLabelColumn', 'yAxis', 'shareGlobally', 'sharedUserNames', 'sharedRoleNames', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }
        $this->db->insertOrUpdate($data, $this->model->getName());
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete($this->model->getName());
    }
}
