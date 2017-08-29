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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;

class ExternalImage extends Model\DataObject\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'externalImage';

    /**
     * @var int
     */
    public $previewWidth;

    /**
     * @var int
     */
    public $inputWidth;

    /**
     * @var int
     */
    public $previewHeight;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\ExternalImage';

    /**
     * @return int
     */
    public function getPreviewWidth()
    {
        return $this->previewWidth;
    }

    /**
     * @param int $previewWidth
     */
    public function setPreviewWidth($previewWidth)
    {
        $this->previewWidth = $this->getAsIntegerCast($previewWidth);
    }

    /**
     * @return int
     */
    public function getPreviewHeight()
    {
        return $this->previewHeight;
    }

    /**
     * @param int $previewHeight
     */
    public function setPreviewHeight($previewHeight)
    {
        $this->previewHeight = $this->getAsIntegerCast($previewHeight);
    }

    /**
     * @return int
     */
    public function getInputWidth()
    {
        return $this->inputWidth;
    }

    /**
     * @param int $inputWidth
     */
    public function setInputWidth($inputWidth)
    {
        $this->inputWidth = $this->getAsIntegerCast($inputWidth);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return new Model\DataObject\Data\ExternalImage($data);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return new Model\DataObject\Data\ExternalImage($data);
    }

    /**
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getVersionPreview
     *
     * @param string $data
     * @param null|DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage && $data->getUrl()) {
            return '<img style="max-width:200px;max-height:200px" src="' . $data->getUrl()  . '" /><br><a href="' . $data->getUrl() . '">' . $data->getUrl() . '</>';
        }

        return $data;
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return new Model\DataObject\Data\ExternalImage($importValue);
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return $this->getForCsvExport($object, $params);
    }

    /**
     * @param mixed $value
     * @param null $relatedObject
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $relatedObject = null, $params = [], $idMapper = null)
    {
        return $this->getFromCsvImport($value, $relatedObject, $params);
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            return '<img style="max-width:200px;max-height:200px" src="' . $data  . '" />';
        }

        return $data;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->previewHeight = $masterDefinition->previewHeight;
        $this->previewWidth = $masterDefinition->previewWidth;
        $this->inputWidth = $masterDefinition->inputWidth;
    }

    /**
     * @param DataObject\Data\ExternalImage $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if ($data instanceof DataObject\Data\ExternalImage and $data->getUrl()) {
            return false;
        }

        return true;
    }
}
