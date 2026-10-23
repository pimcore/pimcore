<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\DataObject\BlockDataMarshaller;

use __PHP_Incomplete_Class;
use Pimcore\Logger;
use Pimcore\Marshaller\MarshallerInterface;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 *
 * Hotspotimage::normalize() hands over its hotspot and marker metadata untouched, and a
 * MarkerHotspotItem built through the PHP API (constructor / setValue()) may hold the referenced
 * Asset, Document or DataObject itself as value. Serialized into a block's resource blob, that
 * element would have to be allowed by Block::getDataFromResource()'s unserialize() allowlist,
 * which must stay limited to plain value objects (GHSA-6324-x2cv-h975). This marshaller therefore
 * stores the metadata as plain arrays with the element reduced to its id - the format the editmode
 * (MarkerHotspotItem::offsetSet()) and Studio write anyway, and the one MarkerHotspotItem::offsetGet()
 * resolves back to the element - and rebuilds the MarkerHotspotItem objects on load. Blobs written
 * before this marshaller existed still contain element objects; unserialize() now reconstructs those
 * as __PHP_Incomplete_Class, from which only the id is taken over.
 */
class Hotspotimage implements MarshallerInterface
{
    public function marshal(mixed $value, array $params = []): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach (['hotspots', 'marker'] as $key) {
            if (isset($value[$key]) && is_array($value[$key])) {
                $value[$key] = $this->marshalLocations($value[$key]);
            }
        }

        return $value;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach (['hotspots', 'marker'] as $key) {
            if (isset($value[$key]) && is_array($value[$key])) {
                $value[$key] = $this->unmarshalLocations($value[$key]);
            }
        }

        return $value;
    }

    private function marshalLocations(array $locations): array
    {
        foreach ($locations as &$location) {
            if (!is_array($location) || !isset($location['data']) || !is_array($location['data'])) {
                continue;
            }

            foreach ($location['data'] as &$metaData) {
                $metaData = $this->marshalMetaData($metaData);
            }
        }

        return $locations;
    }

    private function unmarshalLocations(array $locations): array
    {
        foreach ($locations as &$location) {
            if (!is_array($location) || !isset($location['data']) || !is_array($location['data'])) {
                continue;
            }

            foreach ($location['data'] as &$metaData) {
                $metaData = $this->unmarshalMetaData($metaData);
            }
        }

        return $locations;
    }

    private function marshalMetaData(mixed $metaData): mixed
    {
        $metaData = $this->toArray($metaData);
        if (!is_array($metaData)) {
            return $metaData;
        }

        if (($metaData['value'] ?? null) instanceof ElementInterface) {
            $metaData['value'] = $metaData['value']->getId();
        }

        return $metaData;
    }

    private function unmarshalMetaData(mixed $metaData): mixed
    {
        $metaData = $this->toArray($metaData);
        if (!is_array($metaData)) {
            return $metaData;
        }

        if (($metaData['value'] ?? null) instanceof __PHP_Incomplete_Class) {
            $metaData['value'] = $this->getIdOfIncompleteElement($metaData['value'], $metaData);
        }

        return new MarkerHotspotItem($metaData);
    }

    private function toArray(mixed $metaData): mixed
    {
        if ($metaData instanceof MarkerHotspotItem) {
            return [
                'name' => $metaData->getName(),
                'type' => $metaData->getType(),
                'value' => $metaData->getValue(),
            ];
        }

        return $metaData;
    }

    /**
     * An element object stored by an earlier version comes back as __PHP_Incomplete_Class. Its
     * properties are still readable through an array cast without invoking anything on it, so the
     * id (`o_id` for data objects stored before Pimcore 11) can be taken over; the element type is
     * already given by the metadata type.
     */
    private function getIdOfIncompleteElement(__PHP_Incomplete_Class $element, array $metaData): ?int
    {
        $properties = (array) $element;
        $id = $properties["\0*\0id"] ?? $properties["\0*\0o_id"] ?? $properties['id'] ?? $properties['o_id'] ?? null;

        if (is_int($id) || (is_string($id) && ctype_digit($id))) {
            return (int) $id;
        }

        Logger::warn(sprintf(
            'Dropping unreadable %s element stored in hotspot metadata "%s" (class %s)',
            $metaData['type'] ?? '?',
            $metaData['name'] ?? '?',
            $properties['__PHP_Incomplete_Class_Name'] ?? '?'
        ));

        return null;
    }
}
