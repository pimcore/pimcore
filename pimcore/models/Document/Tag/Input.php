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
class Input extends Model\Document\Tag
{

    /**
     * Contains the text for this element
     *
     * @var integer
     */
    public $text = "";


    /**
     * @see TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "input";
    }

    /**
     * @see TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return $this->text;
    }

    /**
     * @see TagInterface::frontend
     * @return string
     */
    public function frontend()
    {
        $options = $this->getOptions();

        $text = $this->text;
        if (isset($options["htmlspecialchars"]) and $options["htmlspecialchars"] !== false) {
            $text = htmlspecialchars($this->text);
        }

        return $text;
    }

    /**
     *
     */
    public function getDataEditmode()
    {
        return htmlentities($this->text);
    }

    /**
     * @see TagInterface::setDataFromResource
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * @see TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $data = html_entity_decode($data, ENT_HTML5); // this is because the input is now an div contenteditable -> therefore in entities
        $this->text = $data;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return !(boolean) strlen($this->text);
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param null $document
     * @param mixed $params
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }
}
