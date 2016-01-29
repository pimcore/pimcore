<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

class Multiselect extends Model\Document\Tag
{

    /**
     * Contains the current selected values
     *
     * @var array
     */
    public $values = array();

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "multiselect";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return $this->values;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend()
    {
        return implode(",", $this->values);
    }

    public function getDataEditmode()
    {
        return implode(",", $this->values);
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data)
    {
        $this->values = \Pimcore\Tool\Serialize::unserialize($data);
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data)
    {
        if (empty($data)) {
            $this->values = [];
        } elseif (is_string($data)) {
            $this->values = explode(",", $data);
        } elseif (is_array($data)) {
            $this->values = $data;
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->values);
    }

    /**
     * @param Model\Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->values === null or is_array($data->values)) {
            $this->values = $data->values;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }
}
