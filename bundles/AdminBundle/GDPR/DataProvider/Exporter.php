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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\GDPR\DataProvider;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * @internal
 */
class Exporter
{
    /**
     * @param Asset $theAsset
     *
     * @return array
     */
    public static function exportAsset(Asset $theAsset)
    {
        $webAsset = [];
        $webAsset['id'] = $theAsset->getId();
        $webAsset['fullpath'] = $theAsset->getRealFullPath();
        $properties = $theAsset->getProperties();
        $finalProperties = [];

        foreach ($properties as $property) {
            $finalProperties[] = $property->serialize();
        }

        $webAsset['properties'] = $finalProperties;
        $webAsset['customSettings'] = $theAsset->getCustomSettings();

        $resultItem = json_decode(json_encode($webAsset), true);
        unset($resultItem['data']);

        return $resultItem;
    }

    /**
     * @param Concrete $object
     * @param array $result
     * @param Objectbrick $container
     * @param Data\Objectbricks $brickFieldDef
     */
    public static function doExportBrick(Concrete $object, array &$result, Objectbrick $container, Data\Objectbricks $brickFieldDef)
    {
        $allowedBrickTypes = $container->getAllowedBrickTypes();
        $resultContainer = [];
        foreach ($allowedBrickTypes as $brickType) {
            $brickDef = Objectbrick\Definition::getByKey($brickType);
            $brickGetter = 'get' . ucfirst($brickType);
            $brickValue = $container->$brickGetter();

            if ($brickValue instanceof Objectbrick\Data\AbstractData) {
                $resultContainer[$brickType] = [];
                $fDefs = $brickDef->getFieldDefinitions();
                foreach ($fDefs as $fd) {
                    $getter = 'get' . ucfirst($fd->getName());
                    $value = $brickValue->$getter();
                    if ($fd instanceof NormalizerInterface) {
                        $marshalledValue = $fd->normalize($value);
                        $resultContainer[$brickType][$fd->getName()] = $marshalledValue;
                    }
                }
            }
        }
        $result[$container->getFieldname()] = $resultContainer;
    }

    /**
     * @param Concrete $object
     * @param array $result
     * @param Fieldcollection $container
     * @param Data\Fieldcollections $containerDef
     *
     * @throws \Exception
     */
    public static function doExportFieldcollection(Concrete $object, array &$result, Fieldcollection $container, Data\Fieldcollections $containerDef)
    {
        $resultContainer = [];

        $items = $container->getItems();
        foreach ($items as $item) {
            $type = $item->getType();

            $itemValues = [];

            $itemContainerDefinition = Fieldcollection\Definition::getByKey($type);
            $fDefs = $itemContainerDefinition->getFieldDefinitions();

            foreach ($fDefs as $fd) {
                $getter = 'get' . ucfirst($fd->getName());
                $value = $item->$getter();

                if ($fd instanceof NormalizerInterface) {
                    $marshalledValue = $fd->normalize($value);
                    $itemValues[$fd->getName()] = $marshalledValue;
                }
            }

            $resultContainer[] = [
                'type' => $type,
                'value' => $itemValues,
            ];
        }

        //TODO block

        $result[$container->getFieldname()] = $resultContainer;
    }

    /**
     * @param Concrete $object
     * @param array $result
     *
     * @throws \Exception
     */
    public static function doExportObject(Concrete $object, &$result = [])
    {
        $fDefs = $object->getClass()->getFieldDefinitions();
        /** @var Data $fd */
        foreach ($fDefs as $fd) {
            $getter = 'get' . ucfirst($fd->getName());
            $value = $object->$getter();

            if ($fd instanceof Data\Fieldcollections) {
                self::doExportFieldcollection($object, $result, $value, $fd);
            } elseif ($fd instanceof Data\Objectbricks) {
                self::doExportBrick($object, $result, $value, $fd);
            } else {
                if ($fd instanceof NormalizerInterface) {
                    $marshalledValue = $fd->normalize($value);
                    $result[$fd->getName()] = $marshalledValue;
                }
            }
        }
    }

    /**
     * @param AbstractObject $object
     *
     * @return array
     */
    public static function exportObject(AbstractObject $object)
    {
        $webObject = [];
        $webObject['id'] = $object->getId();
        $webObject['fullpath'] = $object->getFullPath();

        $properties = $object->getProperties();
        $finalProperties = [];

        foreach ($properties as $property) {
            $finalProperties[] = $property->serialize();
        }

        $webObject['properties'] = $finalProperties;

        if ($object instanceof Concrete) {
            self::doExportObject($object, $webObject);
        }

        $resultItem = json_decode(json_encode($webObject), true);
        unset($resultItem['data']);

        return $resultItem;
    }
}
