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

namespace Pimcore\Model\Staticroute;

use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid as Uid;

/**
 * @internal
 *
 * @property \Pimcore\Model\Staticroute $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');

        parent::configure([
            'containerConfig' => $config['staticroutes']['definitions'],
            'settingsStoreScope' => 'pimcore_staticroutes',
            'storageDirectory' => $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_STATICROUTES'] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/staticroutes',
            'legacyConfigFile' => 'staticroutes.php',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_STATICROUTES',
        ]);
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->deleteData($this->model->getId());
    }

    /**
     * @deprecated duplicate work, use the listing instead
     *
     * @return Model\Staticroute[]
     */
    public function getAll()
    {
        $list = new Model\Staticroute\Listing();
        $list = $list->load();

        return $list;
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param string|null $id
     *
     * @throws NotFoundException
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->getDataByName($this->model->getId());

        if ($data && $id != null) {
            $data['id'] = $id;
        }

        if ($data) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException(sprintf(
                'Static Route with ID "%s" does not exist.',
                $this->model->getId()
            ));
        }
    }

    /**
     * @param string|null $name
     * @param int|null $siteId
     *
     * @throws NotFoundException
     */
    public function getByName($name = null, $siteId = null)
    {
        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();

        $totalList = new Listing();
        $totalList = $totalList->load();

        $data = array_filter($totalList, function (Model\Staticroute $row) use ($name, $siteId) {
            if ($row->getName() == $name) {
                if (empty($row->getSiteId()) || in_array($siteId, $row->getSiteId())) {
                    return true;
                }
            }

            return false;
        });

        usort($data, function (Model\Staticroute $a, Model\Staticroute $b) {
            if ($a->getSiteId() == $b->getSiteId()) {
                return 0;
            }

            return ($a->getSiteId() < $b->getSiteId()) ? 1 : -1;
        });

        if (count($data) && $data[0]->getId()) {
            $this->assignVariablesToModel($data[0]->getObjectVars());
        } else {
            throw new NotFoundException(sprintf(
                'Static route config with name "%s" does not exist.',
                $this->model->getName()
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDataStructureForYaml(string $id, $data)
    {
        return [
            'pimcore' => [
                'staticroutes' => [
                    'definitions' => [
                        $id => $data,
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->model->getId()) {
            $this->model->setId(Uid::v4());
        }

        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'pattern', 'reverse', 'controller',
            'variables', 'defaults', 'siteId', 'priority', 'methods', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        $this->saveData($this->model->getId(), $data);
    }
}
