<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class Document extends Data
{
    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        $element = Service::getElementByPath('document', $value);
        if ($element) {
            return $element->getId();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        $element = null;
        if (is_numeric($value)) {
            $element = Service::getElementById('document', $value);
        }

        return $element;
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @deprecated use denormalize() instead, will be removed in Pimcore 11
     *
     * @return string
     */
    public function unmarshal($value, $params = [])
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.4',
            sprintf('%s is deprecated, please use denormalize() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

        $element = null;
        if (is_numeric($value)) {
            $element = Service::getElementById('document', $value);
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
            return \Pimcore\Model\Document\Service::getElementById('document', $data);
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
        if ($data instanceof \Pimcore\Model\Document) {
            return $data->getId();
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return int|string|null
     */
    public function getDataFromEditMode($data, $params = [])
    {
        $element = Service::getElementByPath('document', $data);
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

    /**
     * {@inheritdoc}
     */
    public function getDataForEditMode($data, $params = [])
    {
        if (is_numeric($data)) {
            $data = Service::getElementById('document', $data);
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
            $data = \Pimcore\Model\Document::getById($data);
        }

        if ($data instanceof \Pimcore\Model\Document) {
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
        if ($data instanceof \Pimcore\Model\Document && isset($params['type'])) {
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
     * @return int|null
     */
    public function getDataFromListfolderGrid($data, $params = [])
    {
        $data = \Pimcore\Model\Document::getByPath($data);

        if ($data instanceof ElementInterface) {
            return $data->getId();
        }

        return null;
    }
}
