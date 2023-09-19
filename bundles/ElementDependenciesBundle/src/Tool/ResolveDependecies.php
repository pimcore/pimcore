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

namespace Pimcore\Bundle\ElementDependenciesBundle\Tool;

use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage;

class ResolveDependecies
{
    public static function resolveDependencies(object $cd, mixed $data, array $params = []) :array
    {
        if ($cd instanceof Data\Block){
            return self::resolveBlock($cd, $data);
        }
        if ($cd instanceof Data\ImageGallery){
            return self::resolveImageGallery($data);
        }
    }

    protected static function resolveBlock($class, array $data) :array
    {
        $dependencies = [];

        if (!is_array($data)) {
            return [];
        }

        foreach ($data as $blockElements) {
            foreach ($blockElements as $elementName => $blockElement) {
                $fd = $class->getFieldDefinition($elementName);
                if (!$fd) {
                    // class definition seems to have changed
                    Logger::warn('class definition seems to have changed, element name: ' . $elementName);

                    continue;
                }
                $elementData = $blockElement->getData();

                $dependencies = array_merge($dependencies, $fd->resolveDependencies($elementData));
            }
        }

        return $dependencies;
    }

    protected static function resolveImageGallery(DataObject\Data\ImageGallery $data) :array
    {
        $dependencies = [];

        $fd = new Hotspotimage();
        foreach ($data as $item) {
            $itemDependencies = $fd->resolveDependencies($item);
            $dependencies = array_merge($dependencies, $itemDependencies);
        }

        return $dependencies;
    }
}
