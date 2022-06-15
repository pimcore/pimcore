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

namespace Pimcore\Model\Asset\Video\Thumbnail\Config;

use Pimcore\Messenger\CleanupThumbnailsMessage;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Video\Thumbnail\Config $model
 */
class Dao extends Model\Dao\PimcoreLocationAwareConfigDao
{
    public function configure()
    {
        $config = \Pimcore::getContainer()->getParameter('pimcore.config');

        parent::configure([
            'containerConfig' => $config['assets']['video']['thumbnails']['definitions'],
            'settingsStoreScope' => 'pimcore_video_thumbnails',
            'storageDirectory' => $_SERVER['PIMCORE_CONFIG_STORAGE_DIR_VIDEO_THUMBNAILS'] ?? PIMCORE_CONFIGURATION_DIRECTORY . '/video-thumbnails',
            'legacyConfigFile' => 'video-thumbnails.php',
            'writeTargetEnvVariableName' => 'PIMCORE_WRITE_TARGET_VIDEO_THUMBNAILS',
        ]);
    }

    /**
     * @param string|null $id
     *
     * @throws \Exception
     */
    public function getByName($id = null)
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
                'Thumbnail with ID "%s" does not exist.',
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
        $allowedProperties = ['name', 'description', 'group', 'items', 'medias',
            'videoBitrate', 'audioBitrate', 'creationDate', 'modificationDate', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        $this->saveData($this->model->getName(), $data);
        $this->autoClearTempFiles();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->deleteData($this->model->getName());
        $this->autoClearTempFiles();
    }

    protected function autoClearTempFiles()
    {
        $enabled = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['video']['thumbnails']['auto_clear_temp_files'];
        if ($enabled) {
            \Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new CleanupThumbnailsMessage('video', $this->model->getName())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDataStructureForYaml(string $id, $data)
    {
        return [
            'pimcore' => [
                'assets' => [
                    'video' => [
                        'thumbnails' => [
                            'definitions' => [
                                $id => $data,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
