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
 * @package    Property
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class DataObject extends Data
{
    /**
     * @param mixed $value
     * @param array $params
     *
     * @return null|int
     */
    public function marshal($value, $params = [])
    {
        $element = Service::getElementByPath('object', $value);
        if ($element) {
            return $element->getId();
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return string
     */
    public function unmarshal($value, $params = [])
    {
        $element = null;
        if (is_numeric($value)) {
            $element = Service::getElementById('object', $value);
        }
        if ($element) {
            $value = $element->getRealFullPath();
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function transformGetterData($data, $params = [])
    {
        if (is_numeric($data)) {
            return \Pimcore\Model\DataObject\Service::getElementById('object', $data);
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function transformSetterData($data, $params = [])
    {
        if ($data instanceof AbstractObject) {
            return $data->getId();
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataFromEditMode($data, $params = [])
    {
        $element = Service::getElementByPath('object', $data);
        if ($element) {
            return $element->getId();
        }

        return '';
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataForResource($data, $params = [])
    {
        if ($data instanceof ElementInterface) {
            return $data->getId();
        }

        return $data;
    }

    /** @inheritDoc */
    public function getDataForEditMode($data, $params = [])
    {
        if (is_numeric($data)) {
            $data = Service::getElementById('object', $data);
        }
        if ($data instanceof ElementInterface) {
            return $data->getRealFullPath();
        } else {
            return '';
        }
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataForListfolderGrid($data, $params = [])
    {
        if (is_numeric($data)) {
            $data = AbstractObject::getById($data);
        }

        if ($data instanceof AbstractObject) {
            return $data->getFullPath();
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return array
     */
    public function resolveDependencies($data, $params = [])
    {
        if ($data instanceof AbstractObject && isset($params['type'])) {
            $elementId = $data->getId();
            $elementType = $params['type'];

            $key = $elementType . '_' . $elementId;

            return [
                $key => [
                    'id' => $elementId,
                    'type' => $elementType,
                ], ];
        }

        return [];
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataFromListfolderGrid($data, $params = [])
    {
        $data = AbstractObject::getByPath($data);
        if ($data instanceof AbstractElement) {
            return $data->getId();
        }

        return null;
    }
}
