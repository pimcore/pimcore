<?php
declare(strict_types=1);

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

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class DataObject extends Data
{
    public function normalize(mixed $value, array $params = []): mixed
    {
        $element = Service::getElementByPath('object', $value);
        if ($element) {
            return $element->getId();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): mixed
    {
        $element = null;
        if (is_numeric($value)) {
            $element = Service::getElementById('object', $value);
        }

        return $element;
    }

    /**
     * @param mixed $value
     * @param array $params
     *
     * @return string
     *
     * @deprecated use denormalize() instead, will be removed in Pimcore 11
     */
    public function unmarshal(mixed $value, array $params = []): mixed
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.4',
            sprintf('%s is deprecated, please use denormalize() instead. It will be removed in Pimcore 11.', __METHOD__)
        );

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

    public function transformGetterData(mixed $data, array $params = []): mixed
    {
        if (is_numeric($data)) {
            return \Pimcore\Model\DataObject\Service::getElementById('object', $data);
        }

        return $data;
    }

    public function transformSetterData(mixed $data, array $params = []): mixed
    {
        if ($data instanceof AbstractObject) {
            return $data->getId();
        }

        return $data;
    }

    public function getDataFromEditMode(mixed $data, array $params = []): int|string|null
    {
        $element = Service::getElementByPath('object', $data);
        if ($element) {
            return $element->getId();
        }

        return '';
    }

    public function getDataForResource(mixed $data, array $params = []): mixed
    {
        if ($data instanceof ElementInterface) {
            return $data->getId();
        }

        return $data;
    }

    public function getDataForEditMode(mixed $data, array $params = []): mixed
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

    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed
    {
        if (is_numeric($data)) {
            $data = \Pimcore\Model\DataObject::getById($data);
        }

        if ($data instanceof AbstractObject) {
            return $data->getFullPath();
        }

        return $data;
    }

    public function resolveDependencies(mixed $data, array $params = []): array
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

    public function getDataFromListfolderGrid(mixed $data, array $params = []): ?int
    {
        $data = \Pimcore\Model\DataObject::getByPath($data);
        if ($data instanceof ElementInterface) {
            return $data->getId();
        }

        return null;
    }
}
