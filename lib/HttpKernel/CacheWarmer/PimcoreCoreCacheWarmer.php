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

namespace Pimcore\HttpKernel\CacheWarmer;

use Pimcore\Bootstrap;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use ReflectionClass;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
class PimcoreCoreCacheWarmer implements CacheWarmerInterface
{
    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir): array
    {
        $classes = [];

        $this->libraryClasses($classes);
        $this->modelClasses($classes);
        $this->dataObjectClasses($classes);

        return $classes;
    }

    private function libraryClasses(array &$classes): void
    {
        $excludePattern = '@/lib/(Migrations|Maintenance|Sitemap|Workflow|Console|Composer|Translation/(Import|Export)|Image/Optimizer|DataObject/(GridColumnConfig|Import)|Test|Tool/Transliteration|(Pimcore)\.php)@';

        $reflection = new ReflectionClass(Bootstrap::class);
        $dir = dirname($reflection->getFileName());

        $this->getClassesFromDirectory($dir, $excludePattern, 'Pimcore', $classes);
    }

    private function modelClasses(array &$classes): void
    {
        $excludePattern = '@/models/(GridConfig|ImportConfig|Notification|Schedule|Tool/CustomReport|User|Workflow)@';

        $reflection = new ReflectionClass(Asset::class);
        $dir = dirname($reflection->getFileName());

        $this->getClassesFromDirectory($dir, $excludePattern, 'Pimcore\Model', $classes);
    }

    private function getClassesFromDirectory(string $dir, string $excludePattern, string $NSPrefix, array &$classes): void
    {
        $files = rscandir($dir);

        foreach ($files as $file) {
            $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
            if (is_file($file) && !preg_match($excludePattern, $file)) {
                $className = preg_replace('@^' . preg_quote($dir, '@') . '@', $NSPrefix, $file);
                $className = preg_replace('@\.php$@', '', $className);
                $className = str_replace(DIRECTORY_SEPARATOR, '\\', $className);

                // include classes, interfaces and traits
                // exclude invalid files like helper-functions
                if (!preg_match('/[_.-]/', $className)) {
                    $classes[] = $className;
                }
            }
        }
    }

    private function dataObjectClasses(array &$classes): void
    {
        $objectClassesFolder = PIMCORE_CLASS_DEFINITION_DIRECTORY;
        $files = glob($objectClassesFolder.'/*.php');

        foreach ($files as $file) {
            $className = DataObject::class . '\\' . preg_replace('/^definition_(.*)\.php$/', '$1', basename($file));
            $listingClass = $className . '\\Listing';

            $classes[] = $className;
            $classes[] = $listingClass;
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->loadNames();

        foreach ($list as $brickName) {
            $className = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickName);

            $classes[] = $className;
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->loadNames();

        foreach ($list as $fcName) {
            $className = 'Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\' . ucfirst($fcName);

            $classes[] = $className;
        }
    }
}
