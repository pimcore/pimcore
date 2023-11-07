<?php
declare(strict_types=1);

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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class ExternalImage extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    /**
     * @internal
     *
     */
    public ?int $previewWidth = null;

    /**
     * @internal
     *
     */
    public ?int $inputWidth = null;

    /**
     * @internal
     *
     */
    public ?int $previewHeight = null;

    public function getPreviewWidth(): ?int
    {
        return $this->previewWidth;
    }

    public function setPreviewWidth(?int $previewWidth): void
    {
        $this->previewWidth = $this->getAsIntegerCast($previewWidth);
    }

    public function getPreviewHeight(): ?int
    {
        return $this->previewHeight;
    }

    public function setPreviewHeight(?int $previewHeight): void
    {
        $this->previewHeight = $this->getAsIntegerCast($previewHeight);
    }

    public function getInputWidth(): ?int
    {
        return $this->inputWidth;
    }

    public function setInputWidth(?int $inputWidth): void
    {
        $this->inputWidth = $this->getAsIntegerCast($inputWidth);
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     *
     * @return Model\DataObject\Data\ExternalImage
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): DataObject\Data\ExternalImage
    {
        $externalImage = new Model\DataObject\Data\ExternalImage($data);

        if (isset($params['owner'])) {
            $externalImage->_setOwner($params['owner']);
            $externalImage->_setOwnerFieldname($params['fieldname']);
            $externalImage->_setOwnerLanguage($params['language'] ?? null);
        }

        return $externalImage;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            return $data->getUrl();
        }

        return null;
    }

    /**
     * @param Model\DataObject\Data\ExternalImage|null $data
     * @param null|DataObject\Concrete $object
     *
     */
    public function getDataForGrid(?DataObject\Data\ExternalImage $data, Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     * @return Model\DataObject\Data\ExternalImage
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): DataObject\Data\ExternalImage
    {
        return new Model\DataObject\Data\ExternalImage($data);
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     * @return Model\DataObject\Data\ExternalImage
     */
    public function getDataFromGridEditor(?string $data, Concrete $object = null, array $params = []): DataObject\Data\ExternalImage
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof Model\DataObject\Data\ExternalImage && $data->getUrl()) {
            return '<img style="max-width:200px;max-height:200px" src="' . $data->getUrl()  . '" /><br><a href="' . $data->getUrl() . '">' . $data->getUrl() . '</>';
        }

        return '';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Model\DataObject\Data\ExternalImage) {
            $return = $data->getUrl();
        }

        return $return ?? '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDiffVersionPreview(string $data, Concrete $object = null, array $params = []): string
    {
        if ($data) {
            return '<img style="max-width:200px;max-height:200px" src="' . $data  . '" />';
        }

        return $data;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\ExternalImage $mainDefinition
     */
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->previewHeight = $mainDefinition->previewHeight;
        $this->previewWidth = $mainDefinition->previewWidth;
        $this->inputWidth = $mainDefinition->inputWidth;
    }

    /**
     *
     * @throws Model\Element\ValidationException
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if ($this->getMandatory() && !$omitMandatoryCheck && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    public function isEmpty(mixed $data): bool
    {
        return !($data instanceof DataObject\Data\ExternalImage && $data->getUrl());
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\ExternalImage ? $oldValue->getUrl() : null;
        $newValue = $newValue instanceof DataObject\Data\ExternalImage ? $newValue->getUrl() : null;

        return $oldValue == $newValue;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ExternalImage::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ExternalImage::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\ExternalImage::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\ExternalImage::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\ExternalImage) {
            return [
                'url' => $value->getUrl(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\ExternalImage
    {
        if (is_array($value)) {
            return new DataObject\Data\ExternalImage($value['url']);
        }

        return null;
    }

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'externalImage';
    }
}
