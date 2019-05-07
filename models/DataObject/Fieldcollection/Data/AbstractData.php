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
use Pimcore\Model\DataObject\Concrete;

/**
 * @method Dao getDao()
 */
abstract class AbstractData extends Model\AbstractModel implements Model\DataObject\LazyLoadedFieldsInterface
{
    use Model\DataObject\Traits\LazyLoadedRelationTrait;

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
     * @param $fieldname
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
     * @return mixed
     */
    public function getDefinition()
    {
        $definition = Model\DataObject\Fieldcollection\Definition::getByKey($this->getType());

        return $definition;
    }

    /**
     * @param Model\DataObject\Concrete $object
     *
     * @return $this
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return Model\DataObject\Concrete
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $fieldName
     * @param null $language
     *
     * @return mixed
     */
    public function get($fieldName, $language = null)
    {
        return $this->{'get'.ucfirst($fieldName)}($language);
    }

    /**
     * @param string $fieldName
     * @param $value
     * @param null $language
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
            if (method_exists($field, 'getLazyLoading') && $field->getLazyLoading()) {
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
        $blockedVars = ['loadedLazyKeys'];
        $finalVars = [];

        if (!isset($this->getObject()->_fulldump)) {
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
}
