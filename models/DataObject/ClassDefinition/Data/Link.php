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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\Serialize;

class Link extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\ObjectVarTrait;

    /**
     * @var null|string[]
     */
    public ?array $allowedTypes = null;

    /**
     * @var null|string[]
     */
    public ?array $allowedTargets = null;

    /**
     * @var null|string[]
     */
    public ?array $disabledFields = null;

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof DataObject\Data\Link) {
            $data = clone $data;
            $data->_setOwner(null);
            $data->_setOwnerFieldname('');
            $data->_setOwnerLanguage(null);

            if ($data->getLinktype() === 'internal' && !$data->getPath()) {
                $data->setLinktype(null);
                $data->setInternalType(null);
                if ($data->isEmpty()) {
                    return null;
                }
            }

            $params['resetInvalidFields'] = true;
            $this->checkValidity($data, true, $params);

            return Serialize::serialize($data);
        }

        return null;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Link
    {
        $link = Serialize::unserialize($data);

        if ($link instanceof DataObject\Data\Link) {
            if (isset($params['owner'])) {
                $link->_setOwner($params['owner']);
                $link->_setOwnerFieldname($params['fieldname']);
                $link->_setOwnerLanguage($params['language'] ?? null);
            }

            $params['resetInvalidFields'] = true;
            $this->checkValidity($link, true, $params);

            return $link;
        }

        return null;
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
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (!$data instanceof DataObject\Data\Link) {
            return null;
        }
        $dataArray = $data->getObjectVars();
        $dataArray['path'] = $data->getPath();

        return $dataArray;
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     */
    public function getDataForGrid(?DataObject\Data\Link $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Link
    {
        $link = new DataObject\Data\Link();
        $link->setValues($data);

        if ($link->isEmpty()) {
            return null;
        }

        return $link;
    }

    /**
     * @param null|DataObject\Concrete $object
     *
     */
    public function getDataFromGridEditor(array $data, Concrete $object = null, array $params = []): ?DataObject\Data\Link
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
        return (string) $data;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if ($data instanceof DataObject\Data\Link) {
            if ((int)$data->getInternal() > 0) {
                if ($data->getInternalType() == 'document') {
                    $doc = Document::getById($data->getInternal());
                    if (!$doc instanceof Document) {
                        if (isset($params['resetInvalidFields']) && $params['resetInvalidFields']) {
                            $data->setInternalType(null);
                            $data->setInternal(null);
                        } else {
                            throw new Element\ValidationException('invalid internal link, referenced document with id [' . $data->getInternal() . '] does not exist');
                        }
                    }
                } elseif ($data->getInternalType() == 'asset') {
                    $asset = Asset::getById($data->getInternal());
                    if (!$asset instanceof Asset) {
                        if (isset($params['resetInvalidFields']) && $params['resetInvalidFields']) {
                            $data->setInternalType(null);
                            $data->setInternal(null);
                        } else {
                            throw new Element\ValidationException('invalid internal link, referenced document with id [' . $data->getInternal() . '] does not exist');
                        }
                    }
                }
            }
        } elseif ($data !== null) {
            throw new Element\ValidationException('Expected DataObject\\Data\\Link or null');
        }
    }

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if ($data instanceof DataObject\Data\Link && $data->getInternal()) {
            if ((int)$data->getInternal() > 0) {
                if ($data->getInternalType() == 'document') {
                    if ($doc = Document::getById($data->getInternal())) {
                        $key = 'document_' . $doc->getId();
                        $dependencies[$key] = [
                            'id' => $doc->getId(),
                            'type' => 'document',
                        ];
                    }
                } elseif ($data->getInternalType() == 'asset') {
                    if ($asset = Asset::getById($data->getInternal())) {
                        $key = 'asset_' . $asset->getId();

                        $dependencies[$key] = [
                            'id' => $asset->getId(),
                            'type' => 'asset',
                        ];
                    }
                } elseif ($data->getInternalType() == 'object') {
                    if ($object = DataObject\Concrete::getById($data->getInternal())) {
                        $key = 'object_' . $object->getId();

                        $dependencies[$key] = [
                            'id' => $object->getId(),
                            'type' => 'object',
                        ];
                    }
                }
            }
        }

        return $dependencies;
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if ($data instanceof DataObject\Data\Link && $data->getInternal()) {
            if ((int)$data->getInternal() > 0) {
                $tag = Element\Service::getElementCacheTag($data->getInternalType(), $data->getInternal());
                $tags[$tag] = $tag;
            }
        }

        return $tags;
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            return base64_encode(Serialize::serialize($data));
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Link) {
            return $data->getText();
        }

        return '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either HTML or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDiffVersionPreview(?DataObject\Data\Link $data, Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof DataObject\Data\Link) {
            if ($data->getText()) {
                return $data->getText();
            } elseif ($data->getDirect()) {
                return $data->getDirect();
            }
        }

        return null;
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);
        if ($data instanceof DataObject\Data\Link && $data->getLinktype() == 'internal') {
            $id = $data->getInternal();
            $type = $data->getInternalType();

            if (array_key_exists($type, $idMapping) && array_key_exists($id, $idMapping[$type])) {
                $data->setInternal($idMapping[$type][$id]);
            }
        }

        return $data;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if ($oldValue instanceof DataObject\Data\Link) {
            $oldValue = $oldValue->getObjectVars();
            //clear OwnerawareTrait fields
            unset($oldValue['_owner']);
            unset($oldValue['_fieldname']);
            unset($oldValue['_language']);
        }

        if ($newValue instanceof DataObject\Data\Link) {
            $newValue = $newValue->getObjectVars();
            //clear OwnerawareTrait fields
            unset($newValue['_owner']);
            unset($newValue['_fieldname']);
            unset($newValue['_language']);
        }

        return $this->isEqualArray($oldValue, $newValue);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Link::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Link::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Link::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Link::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\Link) {
            return $value->getObjectVars();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            $link = new DataObject\Data\Link();
            $link->setValues($value);

            return $link;
        } elseif ($value instanceof DataObject\Data\Link) {
            return $value;
        }

        return null;
    }

    public function getColumnType(): string
    {
        return 'text';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'link';
    }

    /**
     * @return null|string[]
     */
    public function getAllowedTypes(): ?array
    {
        return $this->allowedTypes;
    }

    /**
     * @param null|string[] $allowedTypes
     *
     * @return $this
     */
    public function setAllowedTypes(?array $allowedTypes): static
    {
        $this->allowedTypes = $allowedTypes;

        return $this;
    }

    /**
     * @return null|string[]
     */
    public function getAllowedTargets(): ?array
    {
        return $this->allowedTargets;
    }

    /**
     * @param null|string[] $allowedTargets
     *
     * @return $this
     */
    public function setAllowedTargets(?array $allowedTargets): static
    {
        $this->allowedTargets = $allowedTargets;

        return $this;
    }

    /**
     * @return null|string[]
     */
    public function getDisabledFields(): ?array
    {
        return $this->disabledFields;
    }

    /**
     * @param null|string[] $disabledFields
     *
     * @return $this
     */
    public function setDisabledFields(?array $disabledFields): static
    {
        $this->disabledFields = $disabledFields;

        return $this;
    }
}
