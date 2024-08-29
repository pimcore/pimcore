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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Config;

use Exception;
use Pimcore;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Bundle\CustomReportsBundle\Tool\Config $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    private const CONFIG_KEY = 'custom_reports';

    public function configure(): void
    {
        $config = Pimcore::getContainer()->getParameter('pimcore_custom_reports.config_location');
        $definitions = Pimcore::getContainer()->getParameter('pimcore_custom_reports.definitions');

        $storageConfig = $config[self::CONFIG_KEY];

        parent::configure([
            'containerConfig' => $definitions,
            'settingsStoreScope' => 'pimcore_custom_reports',
            'storageConfig' => $storageConfig,
            'legacyConfigFile' => 'custom-reports.php',
        ]);
    }

    /**
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getByName(string $id = null): void
    {
        if ($id != null) {
            $this->model->setName($id);
        }

        $data = $this->getDataByName($this->model->getName());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
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
     * @throws Exception
     */
    public function save(): void
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
        $this->saveData($this->model->getName(), $data);
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->deleteData($this->model->getName());
    }

    protected function prepareDataStructureForYaml(string $id, mixed $data): mixed
    {
        return [
            'pimcore_custom_reports' => [
                'definitions' => [
                    $id => $data,
                ],
            ],
        ];
    }
}
