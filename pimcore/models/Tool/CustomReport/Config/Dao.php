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
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\CustomReport\Config;

use Pimcore\Model;

/**
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
     * @param null $id
     *
     * @throws \Exception
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
            throw new \Exception('Custom report with id: ' . $this->model->getName() . ' does not exist');
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

        try {
            $dataRaw = get_object_vars($this->model);
            $data = [];
            $allowedProperties = ['name', 'sql', 'dataSourceConfig', 'columnConfiguration', 'niceName', 'group', 'xAxis',
                'groupIconClass', 'iconClass', 'reportClass', 'creationDate', 'modificationDate', 'menuShortcut', 'chartType', 'pieColumn',
                'pieLabelColumn', 'yAxis'];

            foreach ($dataRaw as $key => $value) {
                if (in_array($key, $allowedProperties)) {
                    $data[$key] = $value;
                }
            }
            $this->db->insertOrUpdate($data, $this->model->getName());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete($this->model->getName());
    }
}
