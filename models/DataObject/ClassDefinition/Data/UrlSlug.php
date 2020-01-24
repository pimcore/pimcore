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

class UrlSlug extends Data implements CustomResourcePersistingInterface
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
     * @var int
     */
    public $width;

    /**
     * @var string
     */
    public $action;

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
     * @param int $width
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
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return null|array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        // for now we don't support sites (=> there is just a plain input field in the UI)
        if (is_array($data)) {
            $data = $data[0];
            if ($data instanceof Model\DataObject\Data\UrlSlug) {
                return $data->getSlug();
            }
        }
        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\UrlSlug|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {

        if ($data) {
            // currently slug per site is not supported
            $slug = new Model\DataObject\Data\UrlSlug($data, null);
            return [$slug];
        }
        return null;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float
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
                    //
                    $document = Model\Document::getByPath($slug);
                    if ($document) {
                        throw new Model\Element\ValidationException('Found conflict with docucment path "' . $slug . '"');
                    }

                    if (!preg_match('#^(\/\w+)+(\.)?\w+(\?(\w+=[\w\d]+(&\w+=[\w\d]+)*)+){0,1}$#', $slug)) {
                        throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] is not a valid slug");
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
     * @return $this
     */
    public function setAction(?string $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param Model\DataObject\Concrete $object
     * @param array $params
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
            'fieldname' => $this->getName()
        ];
        $this->enrichDataRow($object, $params, $classId, $deleteDescriptor, false);
        $conditionParts = Model\DataObject\Service::buildConditionPartsFromDescriptor($deleteDescriptor);
        $db->query('DELETE FROM object_url_slugs WHERE ' . implode(' AND ', $conditionParts));
        // now save the new data
        if (is_array($slugs) && !empty($slugs)) {
            /** @var Model\DataObject\Data\UrlSlug $slug */
            foreach ($slugs as $slug) {
                $this->enrichDataRow($object, $params, $classId, $slug, false);

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
                            throw new \Exception("Unique constraint violated. Slug alreay used by object "
                                . $existingSlug->getFieldname() . ", fieldname: " . $existingSlug->getFieldname());
                        }
                    }
                    throw $e;
                }
            }
        }
    }


    /**
     * @param null|Model\DataObject\Data\UrlSlug[] $data
     * @param Model\DataObject\Concrete|Model\DataObject\Fieldcollection\Data\AbstractData|Model\DataObject\Objectbrick\Data\AbstractData $object
     * @param array $params
     * @return array|null
     */
    public function prepareDataForPersistence($data, $object = null, $params = [])
    {
        $return = [];

        if ($object instanceof Model\DataObject\Localizedfield) {
            $object = $object->getObject();
        } else if ($object instanceof Model\DataObject\Objectbrick\Data\AbstractData || $object instanceof Model\DataObject\Fieldcollection\Data\AbstractData) {
            $object = $object->getObject();
        }

        if ($data && !is_array($data)) {
            throw new \Exception("Slug data not valid");
        }

        if (is_array($data) && count($data) > 0) {

            /** @var Model\DataObject\Data\UrlSlug $slugItem */
            foreach ($data as $slugItem) {
                if ($slugItem instanceof Model\DataObject\Data\UrlSlug) {
                    $return[] = [
                        'objectId' => $object->getId(),
                        'fieldname' => $this->getName(),
                        'slug' => $slugItem->getSlug(),
                        'siteId' => $slugItem->getSiteId() ?? 0
                    ];
                } else {
                    throw new \Exception("expected instance of UrlSlug");
                }
            }

            return $return;
        }
        return null;
    }


    /**
     * @param Model\DataObject\Concrete $object
     * @param array $params
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
        $db = Db::get();
        $db->delete('object_url_slugs', ['objectId' => $object->getId()]);
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
    public function isEqual($oldValue, $newValue)
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
        return ($oldData === $newData);
    }

    /**
     * @return bool
     */
    public function supportsDirtyDetection()
    {
        return true;
    }

    /**
     * @param string $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {

        if (is_array($data)) {
            /** @var Model\DataObject\Data\UrlSlug $item */
            foreach ($data as $item) {
                if ($item instanceof Model\DataObject\Data\UrlSlug) {
                    if (!$item->getSlug() && !$item->getSiteId()) {
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
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
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
                $slug = new Model\DataObject\Data\UrlSlug($dataItem["slug"]);
                foreach ($dataItem as $var => $value) {
                    $slug->setObjectVar($var, $value, true);
                }
                $result[] = $slug;
            }
            return $result;
        }


        return null;
    }


    protected function getPreviewData($data, $object = null, $params = [], $lineBreak = '<br />') {
        if (is_array($data) && count($data) > 0) {
            $pathes = [];

            foreach ($data as $e) {
                if ($e instanceof Model\DataObject\Data\UrlSlug) {
                    $line = $e->getSlug();;
                    if ($e->getSiteId()) {
                        $line .= " : " . $e->getSiteId();
                    }
                    $pathes[] = $line;
                }
            }

            return implode($lineBreak, $pathes);
        }
        return null;
    }
    /**
     * @param null|array $data
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
     * @return null
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

}
