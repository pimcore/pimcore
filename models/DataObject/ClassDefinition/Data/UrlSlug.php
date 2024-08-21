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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Pimcore\Db;
use Pimcore\Event\Model\DataObject\ClassDefinition\UrlSlugEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\UrlSlugEvents;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Normalizer\NormalizerInterface;

class UrlSlug extends Data implements CustomResourcePersistingInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, PreGetDataInterface, PreSetDataInterface
{
    use DataObject\Traits\DataWidthTrait;
    use Model\DataObject\Traits\ContextPersistenceTrait;
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @internal
     */
    public ?int $domainLabelWidth = null;

    /**
     * @internal
     */
    public string $action;

    /**
     * @internal
     *
     * @var null|int[]
     */
    public ?array $availableSites = null;

    /**
     * @see Data::getDataForEditmode
     *
     * @param null|Model\DataObject\Concrete $object
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $result = [];
        if (is_array($data)) {
            foreach ($data as $slug) {
                if ($slug instanceof Model\DataObject\Data\UrlSlug) {
                    $siteId = $slug->getSiteId();
                    $site = null;
                    if ($siteId) {
                        $site = Model\Site::getById($siteId);
                    }

                    $resultItem = [
                        'slug' => $slug->getSlug(),
                        'siteId' => $slug->getSiteId(),
                        'domain' => $site ? $site->getMainDomain() : null,
                    ];

                    $result[$slug->getSiteId()] = $resultItem;
                }
            }
        }
        ksort($result);

        return array_values($result);
    }

    /**
     * @return Model\DataObject\Data\UrlSlug[]
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        $result = [];
        if (is_array($data)) {
            foreach ($data as $siteId => $item) {
                $siteId = $item[0];
                $slug = $item[1];
                $slug = new Model\DataObject\Data\UrlSlug($slug, (int) $siteId);

                if ($item[2]) {
                    $slug->setPreviousSlug($item[2]);
                }

                $result[] = $slug;
            }
        }

        return $result;
    }

    /**
     * @param Model\DataObject\Concrete|null $object
     *
     * @return Model\DataObject\Data\UrlSlug[]
     */
    public function getDataFromGridEditor(float $data, Concrete $object = null, array $params = []): array
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if ($data && !is_array($data)) {
            throw new Model\Element\ValidationException('Invalid slug data');
        }
        $foundSlug = false;
        if (is_array($data)) {
            /** @var Model\DataObject\Data\UrlSlug $item */
            foreach ($data as $item) {
                $matches = [];
                $slug = htmlspecialchars($item->getSlug());
                $foundSlug = true;

                if (strlen($slug) > 0) {
                    $document = Model\Document::getByPath($slug);
                    if ($document) {
                        throw new Model\Element\ValidationException('Slug must be unique. Found conflict with document path "' . $slug . '"');
                    }

                    if (strlen($slug) < 2 || $slug[0] !== '/') {
                        throw new Model\Element\ValidationException('Slug must be at least 2 characters long and start with slash');
                    }

                    if (preg_match_all('([?#])', $item->getSlug(), $matches)) {
                        throw new Model\Element\ValidationException('Slug contains reserved characters! [' . implode(' ', array_unique($matches[0])) . ']');
                    }
                }
            }
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && !$foundSlug) {
            throw new Model\Element\ValidationException('Mandatory check failed');
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return $this
     */
    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function save(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        if (isset($params['isUntouchable']) && $params['isUntouchable']) {
            return;
        }

        $db = Db::get();
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data !== null) {
            $slugs = $this->prepareDataForPersistence($data, $object, $params);

            // delete rows first
            $deleteDescriptor = [
                'fieldname' => $this->getName(),
            ];
            $this->enrichDataRow($object, $params, $classId, $deleteDescriptor, 'objectId');
            $conditionParts = Model\DataObject\Service::buildConditionPartsFromDescriptor($deleteDescriptor);
            $db->executeQuery('DELETE FROM ' . Model\DataObject\Data\UrlSlug::TABLE_NAME . ' WHERE ' . implode(' AND ', $conditionParts));
            // now save the new data
            if (is_array($slugs) && !empty($slugs)) {
                /** @var Model\DataObject\Data\UrlSlug $slug */
                foreach ($slugs as $slug) {
                    if (!$slug['slug']) {
                        continue;
                    }

                    $this->enrichDataRow($object, $params, $classId, $slug, 'objectId');

                    // relation needs to be an array with src_id, dest_id, type, fieldname
                    try {
                        $db->insert(Model\DataObject\Data\UrlSlug::TABLE_NAME, $slug);
                    } catch (Exception $e) {
                        Logger::error((string)$e);
                        if ($e instanceof UniqueConstraintViolationException) {
                            // check if the slug action can be resolved.

                            $existingSlug = Model\DataObject\Data\UrlSlug::resolveSlug($slug['slug'], $slug['siteId']);
                            if ($existingSlug) {
                                // this will also remove an invalid slug and throw an exception.
                                // retrying the transaction should success the next time
                                try {
                                    $existingSlug->getAction();
                                } catch (Exception $e) {
                                    $db->insert(Model\DataObject\Data\UrlSlug::TABLE_NAME, $slug);

                                    return;
                                }

                                // if now exception is thrown then the slug is owned by a diffrent object/field
                                throw new Exception('Unique constraint violated. Slug "' . $slug['slug'] . '" is already used by object '
                                    . $existingSlug->getObjectId() . ', fieldname: ' . $existingSlug->getFieldname());
                            }
                        }

                        throw $e;
                    }
                }
            }
        }
        $event = new UrlSlugEvent($this, $data);
        $this->dispatchEvent($event, UrlSlugEvents::POST_SAVE);
    }

    /**
     * @param Model\DataObject\Concrete|Model\DataObject\Fieldcollection\Data\AbstractData|Model\DataObject\Objectbrick\Data\AbstractData|Model\DataObject\Localizedfield|null $object
     */
    public function prepareDataForPersistence(mixed $data, Localizedfield|AbstractData|Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object = null, array $params = []): ?array
    {
        $return = [];

        if ($object instanceof Model\DataObject\Localizedfield) {
            $object = $object->getObject();
        } elseif ($object instanceof Model\DataObject\Objectbrick\Data\AbstractData || $object instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            $object = $object->getObject();
        }

        if ($data && !is_array($data)) {
            throw new Exception('Slug data not valid');
        }

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $slugItem) {
                if ($slugItem instanceof Model\DataObject\Data\UrlSlug) {
                    $return[] = [
                        'objectId' => $object->getId(),
                        'classId' => $object->getClassId(),
                        'fieldname' => $this->getName(),
                        'slug' => $slugItem->getSlug(),
                        'siteId' => $slugItem->getSiteId() ?? 0,
                    ];
                } else {
                    throw new Exception('expected instance of UrlSlug');
                }
            }

            return $return;
        }

        return null;
    }

    public function load(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): array
    {
        $rawResult = [];
        if ($object instanceof Model\DataObject\Concrete) {
            $rawResult = $object->retrieveSlugData(['fieldname' => $this->getName(), 'ownertype' => 'object']);
        } elseif ($object instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            $rawResult = $object->getObject()->retrieveSlugData(['fieldname' => $this->getName(), 'ownertype' => 'fieldcollection', 'ownername' => $object->getFieldname(), 'position' => $object->getIndex()]);
        } elseif ($object instanceof Model\DataObject\Localizedfield) {
            $context = $params['context'] ?? null;
            if (isset($context['containerType']) && (($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick'))) {
                $fieldname = $context['fieldname'] ?? null;
                if ($context['containerType'] === 'fieldcollection') {
                    $index = $context['index'] ?? null;
                    $filter = '/' . $context['containerType'] . '~' . $fieldname . '/' . $index . '/%';
                } else {
                    $filter = '/' . $context['containerType'] . '~' . $fieldname . '/%';
                }
                $rawResult = $object->getObject()->retrieveSlugData(['fieldname' => $this->getName(), 'ownertype' => 'localizedfield', 'ownername' => $filter, 'position' => $params['language']]);
            } else {
                $rawResult = $object->getObject()->retrieveSlugData(['fieldname' => $this->getName(), 'ownertype' => 'localizedfield', 'position' => $params['language']]);
            }
        } elseif ($object instanceof Model\DataObject\Objectbrick\Data\AbstractData) {
            $rawResult = $object->getObject()->retrieveSlugData(['fieldname' => $this->getName(), 'ownertype' => 'objectbrick', 'ownername' => $object->getFieldname(), 'position' => $object->getType()]);
        }

        $result = [];
        foreach ($rawResult as $rawItem) {
            $slug = Model\DataObject\Data\UrlSlug::createFromDataRow($rawItem);
            $result[] = $slug;
        }

        return $result;
    }

    public function delete(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        if (!isset($params['isUpdate']) || !$params['isUpdate']) {
            $db = Db::get();
            $db->delete(Model\DataObject\Data\UrlSlug::TABLE_NAME, ['objectId' => $object->getId()]);
        }
    }

    public function getUnique(): bool
    {
        return true;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\UrlSlug $mainDefinition
     */
    public function synchronizeWithMainDefinition(Model\DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->action = $mainDefinition->action;
    }

    public function getDataForSearchIndex(Localizedfield|AbstractData|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): string
    {
        return '';
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldData = [];
        $newData = [];

        if (is_array($oldValue)) {
            /** @var Model\DataObject\Data\UrlSlug $item */
            foreach ($oldValue as $item) {
                $oldData[] = [$item->getSlug(), $item->getSiteId()];
            }
        } else {
            $oldData = $oldValue;
        }

        if (is_array($newValue)) {
            /** @var Model\DataObject\Data\UrlSlug $item */
            foreach ($newValue as $item) {
                $newData[] = [$item->getSlug(), $item->getSiteId()];
            }
        } else {
            $newData = $newValue;
        }

        $oldData = json_encode($oldData);
        $newData = json_encode($newData);

        return $oldData === $newData;
    }

    public function supportsDirtyDetection(): bool
    {
        return true;
    }

    public function isEmpty(mixed $data): bool
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                if ($item instanceof Model\DataObject\Data\UrlSlug) {
                    if ($item->getSlug()) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected function getPreviewData(?array $data, Concrete $object = null, array $params = [], string $lineBreak = '<br />'): ?string
    {
        if (is_array($data) && count($data) > 0) {
            $pathes = [];

            foreach ($data as $e) {
                if ($e instanceof Model\DataObject\Data\UrlSlug) {
                    $line = $e->getSlug();
                    if ($e->getSiteId()) {
                        $line .= ' : ' . $e->getSiteId();
                    }
                    $pathes[] = $line;
                }
            }

            return implode($lineBreak, $pathes);
        }

        return null;
    }

    public function getVersionPreview(mixed $data, Model\DataObject\Concrete $object = null, array $params = []): string
    {
        return $this->getPreviewData($data, $object, $params) ?? '';
    }

    /**
     * @param null|Model\DataObject\Data\UrlSlug[] $data
     */
    public function getDataForGrid(?array $data, Concrete $object = null, array $params = []): array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     */
    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
    {
        $params['name'] = 'slug';

        return $this->getFilterConditionExt(
            $value,
            $operator,
            $params
        );
    }

    /**
     * @return int[]|null
     */
    public function getAvailableSites(): ?array
    {
        return $this->availableSites;
    }

    /**
     * @param int[]|null $availableSites
     *
     * @return $this
     */
    public function setAvailableSites(?array $availableSites): static
    {
        $this->availableSites = $availableSites;

        return $this;
    }

    public function getDomainLabelWidth(): ?int
    {
        return $this->domainLabelWidth;
    }

    /**
     * @return $this
     */
    public function setDomainLabelWidth(?int $domainLabelWidth): static
    {
        $this->domainLabelWidth = $domainLabelWidth;

        return $this;
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        $data = null;
        if ($container instanceof Model\DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
            if ($this->getLazyLoading() && !$container->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($container);

                $container->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($container);

                if ($container instanceof Model\Element\DirtyIndicatorInterface) {
                    $container->markFieldDirty($this->getName(), false);
                }
            }
        } elseif ($container instanceof Model\DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($container instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            if ($this->getLazyLoading() && $container->getObject()) {
                $subContainer = $container->getObject()->getObjectVar($container->getFieldname());
                if ($subContainer instanceof Model\DataObject\Fieldcollection) {
                    $subContainer->loadLazyField($container->getObject(), $container->getType(), $container->getFieldname(), $container->getIndex(), $this->getName());
                } else {
                    // if container is not available we assume that it is a newly set item
                    $container->markLazyKeyAsLoaded($this->getName());
                }
            }

            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof Model\DataObject\Objectbrick\Data\AbstractData) {
            if ($this->getLazyLoading() && $container->getObject()) {
                $brickGetter = 'get' . ucfirst($container->getFieldname());
                $subContainer = $container->getObject()->$brickGetter();
                if ($subContainer instanceof Model\DataObject\Objectbrick) {
                    $subContainer->loadLazyField($container->getType(), $container->getFieldname(), $this->getName());
                } else {
                    $container->markLazyKeyAsLoaded($this->getName());
                }
            }

            $data = $container->getObjectVar($this->getName());
        }

        return is_array($data) ? $data : [];
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($container);

        return $data;
    }

    public function getLazyLoading(): bool
    {
        return true;
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $result = [];
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            foreach ($data as $slug) {
                if ($slug instanceof Model\DataObject\Data\UrlSlug) {
                    $result[] = $slug->getSlug() . ':' . $slug->getSiteId();
                }
            }
        }

        return implode(',', $result);
    }

    public function supportsInheritance(): bool
    {
        return false;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\UrlSlug::class . '[]';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\UrlSlug::class . '[]';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            /** @var Model\DataObject\Data\UrlSlug $slug */
            foreach ($value as $slug) {
                $result[] = [
                    'slug' => $slug->getSlug(),
                    'siteId' => $slug->getSiteId(),
                ];
            }

            return $result;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $slugData) {
                $slug = new Model\DataObject\Data\UrlSlug($slugData['slug'], $slugData['siteId']);
                $result[] = $slug;
            }

            return $result;
        }

        return null;
    }

    public function getFieldType(): string
    {
        return 'urlSlug';
    }
}
