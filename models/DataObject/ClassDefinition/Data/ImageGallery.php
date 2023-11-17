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
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class ImageGallery extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface
{
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     *
     */
    public string $uploadPath;

    /**
     * @internal
     */
    public ?int $ratioX = null;

    /**
     * @internal
     */
    public ?int $ratioY = null;

    /**
     * @internal
     *
     */
    public string $predefinedDataTemplates;

    public function setRatioX(int $ratioX): void
    {
        $this->ratioX = $ratioX;
    }

    public function getRatioX(): int
    {
        return $this->ratioX;
    }

    public function setRatioY(int $ratioY): void
    {
        $this->ratioY = $ratioY;
    }

    public function getRatioY(): int
    {
        return $this->ratioY;
    }

    public function getPredefinedDataTemplates(): string
    {
        return $this->predefinedDataTemplates;
    }

    public function setPredefinedDataTemplates(string $predefinedDataTemplates): void
    {
        $this->predefinedDataTemplates = $predefinedDataTemplates;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    public function setUploadPath(string $uploadPath): void
    {
        $this->uploadPath = $uploadPath;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        if ($data instanceof DataObject\Data\ImageGallery) {
            $hotspots = [];
            $ids = [];
            $fd = new Hotspotimage();

            foreach ($data as $key => $item) {
                $itemData = $fd->getDataForResource($item, $object, $params);
                $ids[] = $itemData['__image'];
                $hotspots[] = $itemData['__hotspots'];
            }

            $elementCount = count($ids);
            $ids = implode(',', $ids);
            if ($elementCount > 0) {
                $ids = ',' . $ids . ',';
            }

            return [
                $this->getName() . '__images' => $ids,
                $this->getName() . '__hotspots' => Serialize::serialize($hotspots),
            ];
        }

        return [
            $this->getName() . '__images' => null,
            $this->getName() . '__hotspots' => null,
        ];
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): DataObject\Data\ImageGallery
    {
        if (!is_array($data)) {
            return $this->createEmptyImageGallery($params);
        }

        $images = $data[$this->getName() . '__images'];
        $hotspots = $data[$this->getName() . '__hotspots'];
        $hotspots = Serialize::unserialize($hotspots);

        if (!$images) {
            return $this->createEmptyImageGallery($params);
        }

        $resultItems = [];

        $fd = new Hotspotimage();

        $images = array_map('intval', explode(',', $images));
        for ($i = 1; $i < count($images) - 1; $i++) {
            $imageId = $images[$i];
            $hotspotData = $hotspots[$i - 1];

            $itemData = [
                $fd->getName() . '__image' => $imageId,
                $fd->getName() . '__hotspots' => $hotspotData,
            ];

            $itemResult = $fd->getDataFromResource($itemData, $object, $params);
            if ($itemResult instanceof DataObject\Data\Hotspotimage) {
                $resultItems[] = $itemResult;
            }
        }

        $imageGallery = new DataObject\Data\ImageGallery($resultItems);

        if (isset($params['owner'])) {
            $imageGallery->_setOwner($params['owner']);
            $imageGallery->_setOwnerFieldname($params['fieldname']);
            $imageGallery->_setOwnerLanguage($params['language'] ?? null);
        }

        return $imageGallery;
    }

    private function createEmptyImageGallery(array $params): DataObject\Data\ImageGallery
    {
        $imageGallery = new DataObject\Data\ImageGallery();

        if (isset($params['owner'])) {
            $imageGallery->_setOwner($params['owner']);
            $imageGallery->_setOwnerFieldname($params['fieldname']);
            $imageGallery->_setOwnerLanguage($params['language'] ?? null);
        }

        return $imageGallery;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $result = [];
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $itemData = $fd->getDataForEditmode($item);
                $result[] = $itemData;
            }
        }

        return $result;
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): DataObject\Data\ImageGallery
    {
        $resultItems = [];

        if (is_array($data)) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $resultItem = $fd->getDataFromEditmode($item);
                $resultItems[] = $resultItem;
            }
        }

        return new DataObject\Data\ImageGallery($resultItems);
    }

    public function getDataFromGridEditor(?array $data, DataObject\Concrete $object = null, array $params = []): DataObject\Data\ImageGallery
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
        if ($data instanceof DataObject\Data\ImageGallery) {
            return count($data->getItems()) . ' items';
        }

        return '';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\ImageGallery) {
            return base64_encode(Serialize::serialize($data));
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $tags = $fd->getCacheTags($item, $tags);
            }
        }
        $tags = array_unique($tags);

        return $tags;
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $itemDependencies = $fd->resolveDependencies($item);
                $dependencies = array_merge($dependencies, $itemDependencies);
            }
        }

        return $dependencies;
    }

    public function getDataForGrid(?DataObject\Data\ImageGallery $data, DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        if ($data instanceof DataObject\Data\ImageGallery) {
            $fd = new Hotspotimage();
            foreach ($data as $item) {
                $fd->doRewriteIds($container, $idMapping, $params, $item);
            }
        }

        return $data;
    }

    /**
     *
     * @throws Element\ValidationException
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (
            $this->getMandatory() && !$omitMandatoryCheck &&
            ($data === null || empty($data->getItems()) || $data->hasValidImages() === false)
        ) {
            throw new Model\Element\ValidationException('[ ' . $this->getName() . ' ] At least 1 image should be uploaded!');
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    public function isEmpty(mixed $data): bool
    {
        if (empty($data)) {
            return true;
        }

        if ($data instanceof DataObject\Data\ImageGallery) {
            $items = $data->getItems();
            if (empty($items)) {
                return true;
            }
        }

        return false;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $oldValue instanceof DataObject\Data\ImageGallery ? $oldValue->getItems() : [];
        $newValue = $newValue instanceof DataObject\Data\ImageGallery ? $newValue->getItems() : [];

        if (count($oldValue) != count($newValue)) {
            return false;
        }

        $fd = new Hotspotimage();

        foreach ($oldValue as $i => $item) {
            if (!$fd->isEqual($oldValue[$i], $newValue[$i])) {
                return false;
            }
        }

        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ImageGallery::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\ImageGallery::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\ImageGallery::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\ImageGallery::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Model\DataObject\Data\ImageGallery) {
            $list = [];
            $items = $value->getItems();
            $def = new Hotspotimage();
            if ($items) {
                foreach ($items as $item) {
                    if ($item instanceof DataObject\Data\Hotspotimage) {
                        $list[] = $def->normalize($item, $params);
                    }
                }
            }

            return $list;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\ImageGallery
    {
        if (is_array($value)) {
            $items = [];
            $def = new Hotspotimage();
            foreach ($value as $rawValue) {
                $items[] = $def->denormalize($rawValue, $params);
            }

            return new DataObject\Data\ImageGallery($items);
        }

        return null;
    }

    public function getColumnType(): array
    {
        return [
            'images' => 'text',
            'hotspots' => 'longtext',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'imageGallery';
    }
}
