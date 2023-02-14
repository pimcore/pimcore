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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Redirect;
use Pimcore\Normalizer\NormalizerInterface;

class UrlSlug extends Data implements CustomResourcePersistingInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, PreGetDataInterface, PreSetDataInterface
{
    use Extension\ColumnType;
    use Model\DataObject\Traits\ContextPersistenceTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'urlSlug';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var int|null
     */
    public $domainLabelWidth;

    /**
     * @internal
     *
     * @var string
     */
    public $action;

    /**
     * @internal
     *
     * @var null|int[]
     */
    public $availableSites;

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
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
     * @see Data::getDataFromEditmode
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\UrlSlug[]
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $result = [];
        if (is_array($data)) {
            foreach ($data as $siteId => $item) {
                $siteId = $item[0];
                $slug = $item[1];
                $slug = new Model\DataObject\Data\UrlSlug($slug, $siteId);

                if ($item[2]) {
                    $slug->setPreviousSlug($item[2]);
                }

                $result[] = $slug;
            }
        }

        return $result;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\UrlSlug[]
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if ($data && !is_array($data)) {
            throw new Model\Element\ValidationException('Invalid slug data');
        }
        $foundSlug = false;
        if (is_array($data)) {
            /** @var Model\DataObject\Data\UrlSlug $item */
            foreach ($data as $item) {
                $slug = htmlspecialchars($item->getSlug());
                $foundSlug = true;

                if (strlen($slug) > 0) {
                    $slugToCompare = preg_replace('/[#\?\*\:\\\\<\>\|"%&@=;]/', '-', $item->getSlug());
                    if($item->getSlug() !== $slugToCompare){
                        throw new Model\Element\ValidationException('Slug contains forbidden characters!');
                    }

                    $document = Model\Document::getByPath($slug);
                    if ($document) {
                        throw new Model\Element\ValidationException('Slug must be unique. Found conflict with document path "' . $slug . '"');
                    }

                    if (strlen($slug) < 2 || $slug[0] !== '/') {
                        throw new Model\Element\ValidationException('Slug must be at least 2 characters long and start with slash');
                    }

                    if (strpos($slug, '//') !== false || !filter_var('https://example.com' . $slug, FILTER_VALIDATE_URL)) {
                        throw new Model\Element\ValidationException('Slug "' . $slug . '" is not valid');
                    }
                }
            }
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && !$foundSlug) {
            throw new Model\Element\ValidationException('Mandatory check failed');
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string|null $action
     *
     * @return $this
     */
    public function setAction(?string $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save($object, $params = [])
    {
        if (isset($params['isUntouchable']) && $params['isUntouchable']) {
            return;
        }

        $data = $this->getDataFromObjectParam($object, $params);

        $slugs = $this->prepareDataForPersistence($data, $object, $params);
        $db = Db::get();

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
                } catch (\Exception $e) {
                    Logger::error((string) $e);
                    if ($e instanceof UniqueConstraintViolationException) {
                        // check if the slug action can be resolved.

                        $existingSlug = Model\DataObject\Data\UrlSlug::resolveSlug($slug['slug'], $slug['siteId']);
                        if ($existingSlug) {
                            // this will also remove an invalid slug and throw an exception.
                            // retrying the transaction should success the next time
                            try {
                                $existingSlug->getAction();
                            } catch (\Exception $e) {
                                $db->insert(Model\DataObject\Data\UrlSlug::TABLE_NAME, $slug);

                                return;
                            }

                            // if now exception is thrown then the slug is owned by a diffrent object/field
                            throw new \Exception('Unique constraint violated. Slug "' . $slug['slug'] . '" is already used by object '
                                . $existingSlug->getObjectId() . ', fieldname: ' . $existingSlug->getFieldname());
                        }
                    }

                    throw $e;
                }
            }
        }

        // check for previous slugs and create redirects
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $slug) {
            if ($previousSlug = $slug->getPreviousSlug()) {
                if ($previousSlug === $slug->getSlug() || !$slug->getSlug()) {
                    continue;
                }

                $checkSql = 'SELECT id FROM redirects WHERE source = :sourcePath AND `type` = :typeAuto';
                if ($slug->getSiteId()) {
                    $checkSql .= ' AND sourceSite = ' . $db->quote($slug->getSiteId());
                } else {
                    $checkSql .= ' AND sourceSite IS NULL';
                }

                $existingCheck = $db->fetchOne($checkSql, ['sourcePath' => $previousSlug, 'typeAuto' => Redirect::TYPE_AUTO_CREATE]);
                if (!$existingCheck) {
                    $redirect = new Redirect();
                    $redirect->setType(Redirect::TYPE_AUTO_CREATE);
                    $redirect->setRegex(false);
                    $redirect->setTarget($slug->getSlug());
                    $redirect->setSource($previousSlug);
                    $redirect->setStatusCode(301);
                    $redirect->setExpiry(time() + 86400 * 365); // this entry is removed automatically after 1 year

                    if ($slug->getSiteId()) {
                        $redirect->setSourceSite($slug->getSiteId());
                        $redirect->setTargetSite($slug->getSiteId());
                    }

                    $redirect->save();
                }

                $slug->setPreviousSlug(null);
            }
        }
    }

    /**
     * @param mixed $data
     * @param Model\DataObject\Concrete|Model\DataObject\Fieldcollection\Data\AbstractData|Model\DataObject\Objectbrick\Data\AbstractData|Model\DataObject\Localizedfield $object
     * @param array $params
     *
     * @return array|null
     */
    public function prepareDataForPersistence($data, $object = null, $params = [])
    {
        $return = [];

        if ($object instanceof Model\DataObject\Localizedfield) {
            $object = $object->getObject();
        } elseif ($object instanceof Model\DataObject\Objectbrick\Data\AbstractData || $object instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            $object = $object->getObject();
        }

        if ($data && !is_array($data)) {
            throw new \Exception('Slug data not valid');
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
                    throw new \Exception('expected instance of UrlSlug');
                }
            }

            return $return;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function load($object, $params = [])
    {
        $rawResult = null;
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
        if (is_array($rawResult)) {
            foreach ($rawResult as $rawItem) {
                $slug = Model\DataObject\Data\UrlSlug::createFromDataRow($rawItem);
                $result[] = $slug;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object, $params = [])
    {
        if (!isset($params['isUpdate']) || !$params['isUpdate']) {
            $db = Db::get();
            $db->delete(Model\DataObject\Data\UrlSlug::TABLE_NAME, ['objectId' => $object->getId()]);
        }
    }

    /**
     * @return bool
     */
    public function getUnique()
    {
        return true;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\UrlSlug $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->action = $masterDefinition->action;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
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

    /**
     * {@inheritdoc}
     */
    public function supportsDirtyDetection()
    {
        return true;
    }

    /**
     * @param Model\DataObject\Data\UrlSlug[]|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
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

    /**
     * @param array $data
     * @param Model\DataObject\Concrete $object
     * @param array $params
     * @param string $lineBreak
     *
     * @return string|null
     */
    protected function getPreviewData($data, $object = null, $params = [], $lineBreak = '<br />')
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

    /**
     * @param Model\DataObject\Data\UrlSlug[]|null $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $this->getPreviewData($data, $object, $params);
    }

    /**
     * @param null|Model\DataObject\Data\UrlSlug[] $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  string $value
     * @param  string $operator
     * @param  array $params
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator, $params = [])
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
    public function setAvailableSites(?array $availableSites)
    {
        $this->availableSites = $availableSites;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDomainLabelWidth(): ?int
    {
        return $this->domainLabelWidth;
    }

    /**
     * @param int|null $domainLabelWidth
     *
     * @return $this
     */
    public function setDomainLabelWidth(?int $domainLabelWidth)
    {
        $this->domainLabelWidth = $domainLabelWidth;

        return $this;
    }

    /**
     * { @inheritdoc }
     */
    public function preGetData(/** mixed */ $container, /** array */ $params = []) // : mixed
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

    /**
     * { @inheritdoc }
     */
    public function preSetData(/** mixed */ $container, /**  mixed */ $data, /** array */ $params = []) // : mixed
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($container);

        return $data;
    }

    /**
     * @return bool
     */
    public function getLazyLoading()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
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

    /**
     * {@inheritdoc}
     */
    public function supportsInheritance()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\UrlSlug::class . '[]';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\UrlSlug::class . '[]';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
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

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
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
}
