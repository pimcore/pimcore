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

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class Textarea extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Model\DataObject\ClassDefinition\Data\Extension\Text;
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use Model\DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'textarea';

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var int
     */
    public $maxLength;

    /**
     * @var bool
     */
    public $showCharCount;

    /**
     * @var bool
     */
    public $excludeFromSearchIndex = false;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'string';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @return bool
     */
    public function isShowCharCount()
    {
        return $this->showCharCount;
    }

    /**
     * @param bool $showCharCount
     */
    public function setShowCharCount($showCharCount)
    {
        $this->showCharCount = $showCharCount;
    }

    /**
     * @return bool
     */
    public function isExcludeFromSearchIndex(): bool
    {
        return $this->excludeFromSearchIndex;
    }

    /**
     * @param bool $excludeFromSearchIndex
     *
     * @return self
     */
    public function setExcludeFromSearchIndex(bool $excludeFromSearchIndex)
    {
        $this->excludeFromSearchIndex = $excludeFromSearchIndex;

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param string|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            $value = [];
            $data = str_replace("\r\n", '<br>', $data);
            $data = str_replace("\n", '<br>', $data);
            $data = str_replace("\r", '<br>', $data);

            $value['html'] = $data;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataForSearchIndex
     *
     * @param Model\DataObject\Concrete|Model\DataObject\Localizedfield|Model\DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        if ($this->isExcludeFromSearchIndex()) {
            return '';
        } else {
            return parent::getDataForSearchIndex($object, $params);
        }
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
        if (!$omitMandatoryCheck && $this->getMaxLength() !== null) {
            if (mb_strlen($data) > $this->getMaxLength()) {
                throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . " ] longer than max length of '" . $this->getMaxLength() . "'");
            }
        }

        parent::checkValidity($data, $omitMandatoryCheck);
    }

    public function isFilterable(): bool
    {
        return true;
    }
}
