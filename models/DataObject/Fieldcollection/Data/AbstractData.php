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
 * @package    DataObject\Fieldcollection
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\Concrete;

/**
 * @method Dao getDao()
 * @method void save(Model\DataObject\Concrete $object, $params = [], $saveRelationalData = true)
 */
abstract class AbstractData extends Model\AbstractModel implements Model\DataObject\LazyLoadedFieldsInterface, Model\Element\ElementDumpStateInterface, Model\Element\DirtyIndicatorInterface
{
    use Model\Element\ElementDumpStateTrait;

    use Model\DataObject\Traits\LazyLoadedRelationTrait;

    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * @var int
     */
    protected $index;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var Model\DataObject\Concrete
     */
    protected $object;

    /**
     * @var int
     */
    protected $objectId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = (int) $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param string $fieldname
     *
     * @return $this
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Model\DataObject\Fieldcollection\Definition
     */
    public function getDefinition()
    {
        $definition = Model\DataObject\Fieldcollection\Definition::getByKey($this->getType());

        return $definition;
    }

    /**
     * @param Concrete $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->objectId = $object ? $object->getId() : null;
        $this->object = $object;

        return $this;
    }

    /**
     * @return Concrete
     */
    public function getObject()
    {
        if ($this->objectId && !$this->object) {
            $this->setObject(Concrete::getById($this->objectId));
        }

        return $this->object;
    }

    /**
     * @param string $fieldName
     * @param string|null $language
     *
     * @return mixed
     */
    public function get($fieldName, $language = null)
    {
        return $this->{'get'.ucfirst($fieldName)}($language);
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     * @param string|null $language
     *
     * @return mixed
     */
    public function set($fieldName, $value, $language = null)
    {
        return $this->{'set'.ucfirst($fieldName)}($value, $language);
    }

    /**
     * @inheritdoc
     */
    protected function getLazyLoadedFieldNames(): array
    {
        $lazyLoadedFieldNames = [];
        $fields = $this->getDefinition()->getFieldDefinitions(['suppressEnrichment' => true]);
        foreach ($fields as $field) {
            if (($field instanceof LazyLoadingSupportInterface || method_exists($field, 'getLazyLoading'))
                            && $field->getLazyLoading()) {
                $lazyLoadedFieldNames[] = $field->getName();
            }
        }

        return $lazyLoadedFieldNames;
    }

    /**
     * @inheritDoc
     */
    public function isAllLazyKeysMarkedAsLoaded(): bool
    {
        $object = $this->getObject();
        if ($object instanceof Concrete) {
            return $this->getObject()->isAllLazyKeysMarkedAsLoaded();
        }

        return true;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $parentVars = parent::__sleep();
        $blockedVars = ['loadedLazyKeys', 'object'];
        $finalVars = [];

        if (!$this->isInDumpState()) {
            //Remove all lazy loaded fields if item gets serialized for the cache (not for versions)
            $blockedVars = array_merge($this->getLazyLoadedFieldNames(), $blockedVars);
        }

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function __wakeup()
    {
        if ($this->object) {
            $this->objectId = $this->object->getId();
        }
    }
}
