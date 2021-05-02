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

namespace Pimcore\Model\Asset\Image\Thumbnail\Config;

use Pimcore\Model;
use Pimcore\Tool\Console;

/**
 * @internal
 *
 * @property \Pimcore\Model\Asset\Image\Thumbnail\Config $model
 */
class Dao extends Model\Dao\PhpArrayTable
{
    public function configure()
    {
        parent::configure();
        $this->setFile('image-thumbnails');
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

        $data = $this->db->getById($this->model->getName());

        if (isset($data['id'])) {
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
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name): bool
    {
        return (bool) $this->db->getById($this->model->getName());
    }

    /**
     * @param bool $forceClearTempFiles force removing generated thumbnail files of saved thumbnail config
     *
     * @throws \Exception
     */
    public function save($forceClearTempFiles = false)
    {
        $ts = time();
        if (!$this->model->getCreationDate()) {
            $this->model->setCreationDate($ts);
        }
        $this->model->setModificationDate($ts);

        $dataRaw = $this->model->getObjectVars();
        $data = [];
        $allowedProperties = ['name', 'description', 'group', 'items', 'medias', 'format',
            'quality', 'highResolution', 'creationDate', 'modificationDate', 'preserveColor', 'preserveMetaData',
            'rasterizeSVG', 'downloadable', 'preserveAnimation', ];

        foreach ($dataRaw as $key => $value) {
            if (in_array($key, $allowedProperties)) {
                $data[$key] = $value;
            }
        }

        if ($forceClearTempFiles) {
            $this->db->insertOrUpdate($data, $this->model->getName());
            $this->model->clearTempFiles();
        } else {
            $thumbnailDefinitionAlreadyExisted = $this->db->getById($this->model->getName()) !== null;

            $this->db->insertOrUpdate($data, $this->model->getName());

            if ($thumbnailDefinitionAlreadyExisted) {
                $this->autoClearTempFiles();
            }
        }
    }

    /**
     * Deletes object from database
     *
     * @param bool $forceClearTempFiles force removing generated thumbnail files of saved thumbnail config
     */
    public function delete($forceClearTempFiles = false)
    {
        $this->db->delete($this->model->getName());

        if ($forceClearTempFiles) {
            $this->model->clearTempFiles();
        } else {
            $this->autoClearTempFiles();
        }
    }

    protected function autoClearTempFiles()
    {
        $enabled = \Pimcore::getContainer()->getParameter('pimcore.config')['assets']['image']['thumbnails']['auto_clear_temp_files'];
        if ($enabled) {
            $arguments = [
                'pimcore:thumbnails:clear',
                '--type=image',
                '--name='.$this->model->getName(),
            ];

            Console::runPhpScriptInBackground(realpath(PIMCORE_PROJECT_ROOT.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console'), $arguments);
        }
    }
}
