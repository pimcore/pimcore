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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Listing;

use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method DataObject\Listing\Concrete\Dao getDao()
 * @method DataObject\Concrete[] load()
 * @method DataObject\Concrete current()
 */
abstract class Concrete extends Model\DataObject\Listing
{
    /**
     * @var string
     */
    protected $classId;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $locale;

    /**
     * do not use the localized views for this list (in the case the class contains localized fields),
     * conditions on localized fields are not possible
     *
     * @var bool
     */
    public $ignoreLocalizedFields = false;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->initDao(__CLASS__);
    }

    /**
     * @return string
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $classId
     *
     * @return $this
     */
    public function setClassId($classId)
    {
        $this->setData(null);

        $this->classId = $classId;

        return $this;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setClassName($className)
    {
        $this->setData(null);

        $this->className = $className;

        return $this;
    }

    /**
     * @return DataObject\ClassDefinition
     */
    public function getClass()
    {
        $class = DataObject\ClassDefinition::getById($this->getClassId());

        return $class;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->setData(null);

        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param bool $ignoreLocalizedFields
     *
     * @return $this
     */
    public function setIgnoreLocalizedFields($ignoreLocalizedFields)
    {
        $this->setData(null);

        $this->ignoreLocalizedFields = $ignoreLocalizedFields;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIgnoreLocalizedFields()
    {
        return $this->ignoreLocalizedFields;
    }

    /**
     * field collection queries
     *
     * @var array
     */
    private $fieldCollectionConfigs = [];

    /**
     * @param string $type
     * @param string|null $fieldname
     *
     * @throws \Exception
     */
    public function addFieldCollection($type, $fieldname = null)
    {
        $this->setData(null);

        if (empty($type)) {
            throw new \Exception('No fieldcollectiontype given');
        }

        DataObject\Fieldcollection\Definition::getByKey($type);
        $this->fieldCollectionConfigs[] = ['type' => $type, 'fieldname' => $fieldname];
    }

    /**
     * @param array $fieldCollections
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setFieldCollections($fieldCollections)
    {
        $this->setData(null);

        foreach ($fieldCollections as $fc) {
            $this->addFieldCollection($fc['type'], $fc['fieldname']);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getFieldCollections()
    {
        return $this->fieldCollectionConfigs;
    }

    /**
     * object brick queries
     *
     * @var array
     */
    private $objectBrickConfigs = [];

    /**
     * @param string $type
     *
     * @throws \Exception
     */
    public function addObjectbrick($type)
    {
        $this->setData(null);

        if (empty($type)) {
            throw new \Exception('No objectbrick given');
        }

        DataObject\Objectbrick\Definition::getByKey($type);
        if (!in_array($type, $this->objectBrickConfigs)) {
            $this->objectBrickConfigs[] = $type;
        }
    }

    /**
     * @param array $objectbricks
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setObjectbricks($objectbricks)
    {
        $this->setData(null);

        foreach ($objectbricks as $ob) {
            if (!in_array($ob, $this->objectBrickConfigs)) {
                $this->addObjectbrick($ob);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getObjectbricks()
    {
        return $this->objectBrickConfigs;
    }

    /**
     * @return bool
     */
    public function addDistinct()
    {
        $fieldCollections = $this->getFieldCollections();
        if (!empty($fieldCollections)) {
            return true;
        }

        return false;
    }

    /**
     * Filter by path (system field)
     *
     * @param string|int|float|float|array $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return static
     */
    public function filterByPath($data, $operator = '=')
    {
        $this->addFilterByField('o_path', $operator, $data);

        return $this;
    }

    /**
     * Filter by key (system field)
     *
     * @param string|int|float|float|array $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return static
     */
    public function filterByKey($data, $operator = '=')
    {
        $this->addFilterByField('o_key', $operator, $data);

        return $this;
    }

    /**
     * Filter by id (system field)
     *
     * @param string|int|float|float|array $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return static
     */
    public function filterById($data, $operator = '=')
    {
        $this->addFilterByField('o_id', $operator, $data);

        return $this;
    }

    /**
     * Filter by published (system field)
     *
     * @param string|int|float|float|array $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return static
     */
    public function filterByPublished($data, $operator = '=')
    {
        $this->addFilterByField('o_published', $operator, $data);

        return $this;
    }

    /**
     * Filter by creationDate (system field)
     *
     * @param string|int|float|float|array $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return static
     */
    public function filterByCreationDate($data, $operator = '=')
    {
        $this->addFilterByField('o_creationDate', $operator, $data);

        return $this;
    }

    /**
     * Filter by modificationDate (system field)
     *
     * @param string|int|float|float|array $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return static
     */
    public function filterByModificationDate($data, $operator = '=')
    {
        $this->addFilterByField('o_modificationDate', $operator, $data);

        return $this;
    }
}
