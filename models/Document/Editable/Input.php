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
class Input extends Model\Document\Editable
{
    /**
     * Contains the text for this element
     *
     * @var string
     */
    public $text = '';

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'input';
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
        $config = $this->getConfig();

        $text = $this->text;
        if (!isset($config['htmlspecialchars']) || $config['htmlspecialchars'] !== false) {
            $text = htmlspecialchars($this->text);
        }

        return $text;
    }

    public function getDataEditmode()
    {
        return htmlentities($this->text);
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
        $data = html_entity_decode($data, ENT_HTML5); // this is because the input is now an div contenteditable -> therefore in entities
        $this->text = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool) strlen($this->text);
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

class_alias(Input::class, 'Pimcore\Model\Document\Tag\Input');
