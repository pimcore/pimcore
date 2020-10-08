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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Multiselect extends Model\Document\Editable
{
    /**
     * Contains the current selected values
     *
     * @var array
     */
    public $values = [];

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'multiselect';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return array
     */
    public function getData()
    {
        return $this->values;
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        return implode(',', $this->values);
    }

    /**
     * @return array
     */
    public function getDataEditmode()
    {
        return $this->values;
    }

    /**
     * @see EditableInterface::setDataFromResource
     *
     * @param string $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->values = \Pimcore\Tool\Serialize::unserialize($data);

        return $this;
    }

    /**
     * @see EditableInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        if (empty($data)) {
            $this->values = [];
        } elseif (is_string($data)) {
            $this->values = explode(',', $data);
        } elseif (is_array($data)) {
            $this->values = $data;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->values);
    }

    /**
     * @deprecated
     *
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param Model\Document\PageSnippet $document
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $this->sanitizeWebserviceData($wsElement->value);
        if ($data->values === null) {
            $this->values = [];
        } elseif ($data->values instanceof  \stdClass) {
            $this->values = get_object_vars($data->values);
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }
}

class_alias(Multiselect::class, 'Pimcore\Model\Document\Tag\Multiselect');
