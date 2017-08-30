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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;

class Textarea extends Model\DataObject\ClassDefinition\Data
{
    use Model\DataObject\ClassDefinition\Data\Extension\Text;

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
     * @see Model\DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
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
}
