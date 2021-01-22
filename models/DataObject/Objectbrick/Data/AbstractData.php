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
 * @package    DataObject\Objectbrick
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Objectbrick\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;

/**
 * @method Dao getDao()
 * @method void save(Concrete $object, $params = [])
 * @method array getRelationData($field, $forOwner, $remoteClassId)
 */
abstract class AbstractData extends Model\AbstractModel implements Model\DataObject\LazyLoadedFieldsInterface, Model\Element\ElementDumpStateInterface, Model\Element\DirtyIndicatorInterface
{
    use Model\DataObject\Traits\LazyLoadedRelationTrait;

    use Model\Element\ElementDumpStateTrait;

    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * Will be overriden by the actual ObjectBrick
     *
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @var bool
     */
    protected $doDelete;

    /**
     * @var Model\DataObject\Concrete
     */
    protected $object;

    /**
     * @var int
     */
    protected $objectId;

    /**
     * @param DataObject\Concrete $object
     */
    public function __construct(DataObject\Concrete $object)
    {
        $this->setObject($object);
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
     * @return DataObject\Objectbrick\Definition
     */
    public function getDefinition()
    {
        $definition = DataObject\Objectbrick\Definition::getByKey($this->getType());

        return $definition;
    }

    /**
     * @param bool $doDelete
     *
     * @return $this
     */
    public function setDoDelete($doDelete)
    {
        $this->flushContainer();
        $this->doDelete = $doDelete;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDoDelete()
    {
        return $this->doDelete;
    }

    /**
     * @return DataObject\Concrete
     */
    public function getBaseObject()
    {
        return $this->getObject();
    }

    /**
     * @param Concrete $object
     */
    public function delete($object)
    {
        $this->doDelete = true;
        $this->getDao()->delete($object);
        $this->flushContainer();
    }

    /**
     * Flushes the already collected items of the container object
     */
    protected function flushContainer()
    {
        $object = $this->getObject();
        if ($object) {
            $containerGetter = 'get' . ucfirst($this->fieldname);

            $container = $object->$containerGetter();
            if ($container instanceof DataObject\Objectbrick) {
                $container->setItems([]);
            }
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws InheritanceParentNotFoundException
     */
    public function getValueFromParent($key)
    {
        $object = $this->getObject();
        if ($object) {
            $parent = DataObject\Service::hasInheritableParentObject($object);

            if (!empty($parent)) {
                $containerGetter = 'get' . ucfirst($this->fieldname);
                $brickGetter = 'get' . ucfirst($this->getType());
                $getter = 'get' . ucfirst($key);

                if ($parent->$containerGetter()->$brickGetter()) {
                    return $parent->$containerGetter()->$brickGetter()->$getter();
                }
            }
        }

        throw new InheritanceParentNotFoundException('No parent object available to get a value from');
    }

    /**
     * @param DataObject\Concrete $object
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
     * @return DataObject\Concrete
     */
    public function getObject()
    {
        if ($this->objectId && !$this->object) {
            $this->setObject(Concrete::getById($this->objectId));
        }

        return $this->object;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getValueForFieldName($key)
    {
        if ($this->$key) {
            return $this->$key;
        }

        return false;
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
