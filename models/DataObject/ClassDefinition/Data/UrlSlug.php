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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Redirect;

class UrlSlug extends Data implements CustomResourcePersistingInterface, LazyLoadingSupportInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Extension\ColumnType;

    use Model\DataObject\Traits\ContextPersistenceTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'urlSlug';

    /**
     * @var int|null
     */
    public $width;

    /**
     * @var int|null
     */
    public $domainLabelWidth;

    /**
     * @var string
     */
    public $action;

    /** @var null|int[] */
    public $availableSites;

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\UrlSlug[]';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
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

                    $result[] = $resultItem;
                }
            }
        }
        ksort($result);

        return $result;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
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
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($data && !is_array($data)) {
            throw new Model\Element\ValidationException('Invalid slug data');
        }
        $foundSlug = false;
        if (is_array($data)) {
            /** @var Model\DataObject\Data\UrlSlug $item */
            foreach ($data as $item) {
                $slug = $item->getSlug();
                $foundSlug = true;

                if (strlen($slug) > 0) {
                    $document = Model\Document::getByPath($slug);
                    if ($document) {
                        throw new Model\Element\ValidationException('Found conflict with document path "' . $slug . '"');
                    }

                    if (strlen($slug) < 2 || $slug[0] !== '/') {
                        throw new Model\Element\ValidationException('slug must be at least 2 characters long and start with slash');
                    }
                    $slug = substr($slug, 1);
                    $slug = preg_replace('/\/$/', '', $slug);

                    $parts = explode('/', $slug);
                    for ($i = 0; $i < count($parts); $i++) {
                        $part = $parts[$i];
                        if (strlen($part) === 0) {
                            throw new Model\Element\ValidationException('Slug ' . $slug .' not valid');
                        }
                        $sanitizedKey = Model\Element\Service::getValidKey($part, 'document');
                        if ($sanitizedKey != $part) {
                            throw new Model\Element\ValidationException('Slug part ' . $part .' not valid');
                        }
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
     * @param Model\DataObject\Concrete|Model\DataObject\Objectbrick\Data\AbstractData|Model\DataObject\Fieldcollection\Data\AbstractData|Model\DataObject\Localizedfield $object
     * @param array $params
     *
     * @throws \Exception
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
        $db->query('DELETE FROM object_url_slugs WHERE ' . implode(' AND ', $conditionParts));
        // now save the new data
        if (is_array($slugs) && !empty($slugs)) {
            /** @var Model\DataObject\Data\UrlSlug $slug */
            foreach ($slugs as $slug) {
                $this->enrichDataRow($object, $params, $classId, $slug, 'objectId');

                /* relation needs to be an array with src_id, dest_id, type, fieldname*/
                try {
                    $db->insert('object_url_slugs', $slug);
                } catch (\Exception $e) {
                    Logger::error($e);
                    if ($e instanceof UniqueConstraintViolationException) {

                        // check if the slug action can be resolved.

                        $existingSlug = Model\DataObject\Data\UrlSlug::resolveSlug($slug['slug'], $slug['siteId']);
                        if ($existingSlug) {
                            // this will also remove an invalid slug and throw an exception.
                            // retrying the transaction should success the next time
                            try {
                                $existingSlug->getAction();
                            } catch (\Exception $e) {
                                $db->insert('object_url_slugs', $slug);

                                return;
                            }

                            // if now exception is thrown then the slug is owned by a diffrent object/field
                            throw new \Exception('Unique constraint violated. Slug alreay used by object '
                                . $existingSlug->getFieldname() . ', fieldname: ' . $existingSlug->getFieldname());
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
     * @param null|Model\DataObject\Data\UrlSlug[] $data
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

            /** @var Model\DataObject\Data\UrlSlug $slugItem */
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
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return mixed|void
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
     * @param Model\DataObject\Concrete $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
        if (!isset($params['isUpdate']) || !$params['isUpdate']) {
            $db = Db::get();
            $db->delete('object_url_slugs', ['objectId' => $object->getId()]);
        }
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\UrlSlug $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->action = $masterDefinition->action;
    }

    /**
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
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
     * @return bool
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
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if (is_array($data)) {
            $result = [];

            /** @var Model\DataObject\Data\UrlSlug $slug */
            foreach ($data as $slug) {
                $result[] = $slug->getObjectVars();
            }

            return $result;
        }

        return null;
    }

    /**
     * converts data to be imported via webservices
     *
     * @deprecated
     *
     * @param mixed $value
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $dataItem) {
                $dataItem = (array)$dataItem;
                $slug = new Model\DataObject\Data\UrlSlug($dataItem['slug']);
                foreach ($dataItem as $var => $value) {
                    $slug->setObjectVar($var, $value, true);
                }
                $result[] = $slug;
            }

            return $result;
        }

        return null;
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
     * @inheritdoc
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
     * @param Model\DataObject\Concrete|Model\DataObject\Localizedfield|Model\DataObject\Objectbrick\Data\AbstractData|Model\DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return array
     */
    public function preGetData($object, $params = [])
    {
        $data = null;
        if ($object instanceof Model\DataObject\Concrete) {
            $data = $object->getObjectVar($this->getName());
            if ($this->getLazyLoading() && !$object->isLazyKeyLoaded($this->getName())) {
                $data = $this->load($object);

                $object->setObjectVar($this->getName(), $data);
                $this->markLazyloadedFieldAsLoaded($object);

                if ($object instanceof Model\Element\DirtyIndicatorInterface) {
                    $object->markFieldDirty($this->getName(), false);
                }
            }
        } elseif ($object instanceof Model\DataObject\Localizedfield) {
            $data = $params['data'];
        } elseif ($object instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            if ($this->getLazyLoading() && $object->getObject()) {
                /** @var Model\DataObject\Fieldcollection $container */
                $container = $object->getObject()->getObjectVar($object->getFieldname());
                if ($container) {
                    $container->loadLazyField($object->getObject(), $object->getType(), $object->getFieldname(), $object->getIndex(), $this->getName());
                } else {
                    // if container is not available we assume that it is a newly set item
                    $object->markLazyKeyAsLoaded($this->getName());
                }
            }

            $data = $object->getObjectVar($this->getName());
        } elseif ($object instanceof Model\DataObject\Objectbrick\Data\AbstractData) {
            if ($this->getLazyLoading() && $object->getObject()) {
                $brickGetter = 'get' . ucfirst($object->getFieldname());
                /** @var Model\DataObject\Objectbrick $container */
                $container = $object->getObject()->$brickGetter();
                if ($container) {
                    $container->loadLazyField($object->getType(), $object->getFieldname(), $this->getName());
                } else {
                    $object->markLazyKeyAsLoaded($this->getName());
                }
            }

            $data = $object->getObjectVar($this->getName());
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param Model\DataObject\Concrete|Model\DataObject\Localizedfield|Model\DataObject\Objectbrick\Data\AbstractData|Model\DataObject\Fieldcollection\Data\AbstractData $object
     * @param Model\DataObject\Data\UrlSlug[]|null $data
     * @param array $params
     *
     * @return array|null
     */
    public function preSetData($object, $data, $params = [])
    {
        if ($data === null) {
            $data = [];
        }

        $this->markLazyloadedFieldAsLoaded($object);

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
     * converts object data to a simple string value or CSV Export
     *
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string
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
     * @param string $importValue
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $result = [];
        if (strlen($importValue) > 0) {
            $items = explode(',', $importValue);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $parts = explode(':', $item);
                    $slug = new Model\DataObject\Data\UrlSlug($parts[0], $parts[1]);
                    $result[] = $slug;
                }
            }
        }

        return $result;
    }

    /** @inheritDoc */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    /** @inheritDoc */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function supportsInheritance()
    {
        return false;
    }
}
