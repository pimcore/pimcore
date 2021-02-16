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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\CacheWarmer;

use Pimcore\Model\DataObject;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class PimcoreCoreCacheWarmer implements CacheWarmerInterface
{

    /**
     * @inheritDoc
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function warmUp($cacheDir)
    {
        $classes = [];

        // load all data object classes
        $list = new DataObject\ClassDefinition\Listing();
        $list = $list->load();

        foreach ($list as $classDefinition) {
            $className = DataObject::class . '\\' . ucfirst($classDefinition->getName());
            $listingClass = $className . '\\Listing';

            $classes[] = $className;
            $classes[] = $listingClass;
        }


        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        foreach ($list as $brickDefinition) {
            $className = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data' . ucfirst($brickDefinition->getKey());

            $classes[] = $className;
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();

        foreach ($list as $fcDefinition) {
            $className = 'Pimcore\\Model\\DataObject\\Fieldcollection\\Data' . ucfirst($fcDefinition->getKey());

            $classes[] = $className;
        }


        return $classes;
    }
}
