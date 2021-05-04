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

namespace Pimcore\Twig\Extension;

use Pimcore\Model\DataObject;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @internal
 */
class DataObjectHelperExtensions extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('pimcore_data_object', static function ($object) {
                return $object instanceof DataObject\Concrete;
            }),
            new TwigTest('pimcore_data_object_folder', static function ($object) {
                return $object instanceof DataObject\Folder;
            }),
            new TwigTest('pimcore_data_object_class', static function ($object, $className) {
                $className = ucfirst($className);
                $className = 'Pimcore\\Model\\DataObject\\' . $className;

                return class_exists($className) && $object instanceof $className;
            }),
            new TwigTest('pimcore_data_object_gallery', static function ($object) {
                return $object instanceof DataObject\Data\ImageGallery;
            }),
            new TwigTest('pimcore_data_object_hotspot_image', static function ($object) {
                return $object instanceof DataObject\Data\Hotspotimage;
            }),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('pimcore_data_object_select_options', static function ($object, $field) {
                return DataObject\Service::getOptionsForSelectField($object, $field);
            }),
        ];
    }
}
