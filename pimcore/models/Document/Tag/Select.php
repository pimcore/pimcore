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

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Select extends Model\Document\Tag
{
    /**
     * Contains the current selected value
     *
     * @var string
     */
    public $text;

    /**
     * @see TagInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'select';
    }

    /**
     * @see TagInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->text;
    }

    /**
     * @see TagInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        return $this->text;
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
        $this->text = $data;

        return $this;
    }

    /**
     * @see \TagInterface::setDataFromEditmode
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
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param mixed $params
     * @param null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }
}
