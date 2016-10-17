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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Tool;

class Persona extends Model\Object\ClassDefinition\Data\Select
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "persona";


    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data = null;
            }
        }

        return $data;
    }

    /**
     * @param string $data
     * @param null $object
     * @param mixed $params
     * @return null|string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            try {
                $this->checkValidity($data, true);
            } catch (\Exception $e) {
                $data = null;
            }
        }

        return $data;
    }


    /**
     *
     */
    public function configureOptions()
    {
        $list = new Tool\Targeting\Persona\Listing();
        $list->setOrder("asc");
        $list->setOrderKey("name");
        $personas = $list->load();

        $options = [];
        foreach ($personas as $persona) {
            $options[] = [
                "value" => $persona->getId(),
                "key" => $persona->getName()
            ];
        }

        $this->setOptions($options);
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and empty($data)) {
            throw new Model\Element\ValidationException("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (!empty($data)) {
            $persona = Tool\Targeting\Persona::getById($data);
            if (!$persona instanceof Tool\Targeting\Persona) {
                throw new Model\Element\ValidationException("Invalid persona reference");
            }
        }
    }

    /**
     * @param $data
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        $options = $obj->getOptions();
        if (\Pimcore::inAdmin() || empty($options)) {
            $obj->configureOptions();
        }

        return $obj;
    }
}
