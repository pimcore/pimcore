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

namespace Pimcore\Model\DataObject\Listing;

use Exception;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method DataObject\Listing\Concrete\Dao getDao()
 * @method DataObject\Concrete[] load()
 * @method DataObject\Concrete|false current()
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

    protected ?string $locale = null;

    /**
     * do not use the localized views for this list (in the case the class contains localized fields),
     * conditions on localized fields are not possible
     *
     */
    protected bool $ignoreLocalizedFields = false;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->initDao(__CLASS__);
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassId(string $classId): static
    {
        $this->setData(null);

        $this->classId = $classId;

        return $this;
    }

    public function setClassName(string $className): static
    {
        $this->setData(null);

        $this->className = $className;

        return $this;
    }

    public function getClass(): DataObject\ClassDefinition
    {
        $class = DataObject\ClassDefinition::getById($this->getClassId());

        return $class;
    }

    public function setLocale(?string $locale): static
    {
        $this->setData(null);

        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setIgnoreLocalizedFields(bool $ignoreLocalizedFields): static
    {
        $this->setData(null);

        $this->ignoreLocalizedFields = $ignoreLocalizedFields;

        return $this;
    }

    public function getIgnoreLocalizedFields(): bool
    {
        return $this->ignoreLocalizedFields;
    }

    /**
     * field collection queries
     *
     */
    private array $fieldCollectionConfigs = [];

    /**
     *
     * @throws Exception
     */
    public function addFieldCollection(string $type, string $fieldname = null): void
    {
        $this->setData(null);

        if (empty($type)) {
            throw new Exception('No fieldcollectiontype given');
        }

        DataObject\Fieldcollection\Definition::getByKey($type);
        $this->fieldCollectionConfigs[] = ['type' => $type, 'fieldname' => $fieldname];
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setFieldCollections(array $fieldCollections): static
    {
        $this->setData(null);

        foreach ($fieldCollections as $fc) {
            $this->addFieldCollection($fc['type'], $fc['fieldname']);
        }

        return $this;
    }

    public function getFieldCollections(): array
    {
        return $this->fieldCollectionConfigs;
    }

    /**
     * object brick queries
     *
     */
    private array $objectBrickConfigs = [];

    /**
     *
     * @throws Exception
     */
    public function addObjectbrick(string $type): void
    {
        $this->setData(null);

        if (empty($type)) {
            throw new Exception('No objectbrick given');
        }

        DataObject\Objectbrick\Definition::getByKey($type);
        if (!in_array($type, $this->objectBrickConfigs)) {
            $this->objectBrickConfigs[] = $type;
        }
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setObjectbricks(array $objectbricks): static
    {
        $this->setData(null);

        foreach ($objectbricks as $ob) {
            if (!in_array($ob, $this->objectBrickConfigs)) {
                $this->addObjectbrick($ob);
            }
        }

        return $this;
    }

    public function getObjectbricks(): array
    {
        return $this->objectBrickConfigs;
    }

    /**
     * @internal
     *
     */
    public function addDistinct(): bool
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
     * @param float|array|int|string $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return $this
     */
    public function filterByPath(float|array|int|string $data, string $operator = '='): static
    {
        $this->addFilterByField('path', $operator, $data);

        return $this;
    }

    /**
     * Filter by key (system field)
     *
     * @param float|array|int|string $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return $this
     */
    public function filterByKey(float|array|int|string $data, string $operator = '='): static
    {
        $this->addFilterByField('key', $operator, $data);

        return $this;
    }

    /**
     * Filter by id (system field)
     *
     * @param float|array|int|string $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return $this
     */
    public function filterById(float|array|int|string $data, string $operator = '='): static
    {
        $this->addFilterByField('id', $operator, $data);

        return $this;
    }

    /**
     * Filter by published (system field)
     *
     * @param float|array|int|string $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return $this
     */
    public function filterByPublished(float|array|int|string $data, string $operator = '='): static
    {
        $this->addFilterByField('published', $operator, $data);

        return $this;
    }

    /**
     * Filter by creationDate (system field)
     *
     * @param float|array|int|string $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return $this
     */
    public function filterByCreationDate(float|array|int|string $data, string $operator = '='): static
    {
        $this->addFilterByField('creationDate', $operator, $data);

        return $this;
    }

    /**
     * Filter by modificationDate (system field)
     *
     * @param float|array|int|string $data  comparison data, can be scalar or array (if operator is e.g. "IN (?)")
     * @param string $operator  SQL comparison operator, e.g. =, <, >= etc. You can use "?" as placeholder, e.g. "IN (?)"
     *
     * @return $this
     */
    public function filterByModificationDate(float|array|int|string $data, string $operator = '='): static
    {
        $this->addFilterByField('modificationDate', $operator, $data);

        return $this;
    }
}
