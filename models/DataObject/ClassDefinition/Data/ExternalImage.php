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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class ExternalImage extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

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
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return Model\DataObject\Data\ExternalImage
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        $externalImage = new Model\DataObject\Data\ExternalImage($data);

        if (isset($params['owner'])) {
            $externalImage->setOwner($params['owner'], $params['fieldname'], $params['language'] ?? null);
        }

        return $externalImage;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
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
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return Model\DataObject\Data\ExternalImage
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return new Model\DataObject\Data\ExternalImage($data);
    }

    /**
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return Model\DataObject\Data\ExternalImage
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\ExternalImage|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
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
     * @param DataObject\Concrete $object
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

        return '';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
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
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        return $this->getForCsvExport($object, $params);
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param Model\Element\AbstractElement $relatedObject
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
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
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param string $data
     * @param DataObject\Concrete|null $object
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
     * @param Model\DataObject\ClassDefinition\Data\ExternalImage $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->previewHeight = $masterDefinition->previewHeight;
        $this->previewWidth = $masterDefinition->previewWidth;
        $this->inputWidth = $masterDefinition->inputWidth;
    }

    /**
     * @param DataObject\Data\ExternalImage|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return !($data instanceof DataObject\Data\ExternalImage && $data->getUrl());
    }

    /**
     * @param DataObject\Data\ExternalImage|null $oldValue
     * @param DataObject\Data\ExternalImage|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\ExternalImage ? $oldValue->getUrl() : null;
        $newValue = $newValue instanceof DataObject\Data\ExternalImage ? $newValue->getUrl() : null;

        return $oldValue == $newValue;
    }
}
