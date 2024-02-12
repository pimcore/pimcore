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

namespace Pimcore\Serializer\Normalizer;

use DateTime;
use DateTimeInterface;
use Exception;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Localizedfield;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * This class is NOT used within Pimcore\Serializer\Serializer, but uses the normalize methods of Pimcore's
 * data object field definitions to serialize data objects in a standardized way.
 */
class DataObjectNormalizer implements NormalizerInterface
{
    /**
     * @throws Exception
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        $dataObject = $object;

        if ($dataObject instanceof Folder) {
            return $this->normalizeFolder($dataObject);
        }

        if ($dataObject instanceof Concrete) {
            return $this->normalizeDataObject($dataObject);
        }

        return [];
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof AbstractObject;
    }

    private function normalizeFolder(Folder $folder): array
    {
        return $this->normalizeGeneralAttributes($folder);
    }

    /**
     * @throws Exception
     */
    private function normalizeDataObject(Concrete $dataObject): array
    {
        return array_merge(
            $this->normalizeGeneralAttributes($dataObject),
            $this->normalizeFieldDefinitions($dataObject)
        );
    }

    protected function normalizeGeneralAttributes(AbstractObject $dataObject): array
    {
        $result = [
            'id' => $dataObject->getId(),
            'parentId' => $dataObject->getParentId(),
            'creationDate' => $this->formatDate($dataObject->getCreationDate()),
            'modificationDate' => $this->formatDate($dataObject->getModificationDate()),
            'type' => $dataObject->getType(),
            'key' => $dataObject->getKey(),
            'path' => $dataObject->getPath(),
            'fullPath' => $dataObject->getRealFullPath(),
            'userOwner' => $dataObject->getUserOwner(),
        ];

        if ($dataObject instanceof Concrete) {
            $result = array_merge($result, [
                'className' => $dataObject->getClassName(),
                'published' => $dataObject->getPublished(),
            ]);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    protected function normalizeFieldDefinitions(Concrete $dataObject): array
    {
        $result = [];

        foreach ($dataObject->getClass()->getFieldDefinitions() as $key => $fieldDefinition) {

            $value = $dataObject->get($key);

            if($value instanceof Localizedfield) {
                $value->loadLazyData();
            }

            if($fieldDefinition instanceof \Pimcore\Normalizer\NormalizerInterface) {
                $value = $fieldDefinition->normalize($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function formatDate(?int $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return (new DateTime())
            ->setTimestamp($timestamp)
            ->format(DateTimeInterface::ATOM);
    }
}
