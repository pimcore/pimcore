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
class Select extends Model\Document\Editable
{
    /**
     * Contains the current selected value
     *
     * @var string
     */
    public $text;

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'select';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->text;
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        return $this->text;
    }

    /**
     * @see EditableInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->text = $data;

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
        $this->text = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->text);
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
        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }
}

class_alias(Select::class, 'Pimcore\Model\Document\Tag\Select');
