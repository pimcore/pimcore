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
class Table extends Model\Document\Editable
{
    /**
     * Contains the text for this element
     *
     * @var array
     */
    public $data;

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'table';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $html = '';

        if (is_array($this->data) && count($this->data) > 0) {
            $html .= '<table border="0" cellpadding="0" cellspacing="0">';

            foreach ($this->data as $row) {
                $html .= '<tr>';
                foreach ($row as $col) {
                    $html .= '<td>';
                    $html .= $col;
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        return $html;
    }

    /**
     * @see TagInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->data = \Pimcore\Tool\Serialize::unserialize($data);

        return $this;
    }

    /**
     * @see EditableInterface::setDataFromEditmode
     *
     * @param array $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
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
        $data = $wsElement->value;
        if ($data->data === null or is_array($data->data)) {
            $this->data = $data->data;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }
}

class_alias(Table::class, 'Pimcore\Model\Document\Tag\Table');
