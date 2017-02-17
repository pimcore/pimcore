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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Table extends Model\Document\Tag
{

    /**
     * Contains the text for this element
     *
     * @var array
     */
    public $data;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "table";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend()
    {
        $html = "";

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
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->data = \Pimcore\Tool\Serialize::unserialize($data);

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param mixed $params
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->data === null or is_array($data->data)) {
            $this->data = $data->data;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }
}
