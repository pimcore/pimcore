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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\CacheWarmer;

use Doctrine\DBAL\Exception\DriverException;
use Pimcore\Bootstrap;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
class PimcoreCoreCacheWarmer implements CacheWarmerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $classes = [];

        $this->libraryClasses($classes);
        $this->modelClasses($classes);

        if (\Pimcore::isInstalled()) {
            try {
                $this->dataObjectClasses($classes);
            } catch (\Exception $exception) {
                if (!$exception instanceof DriverException) {
                    throw $exception;
                }

                //Ignore. Database might not be setup yet
            }
        }

        return $classes;
    }

    private function libraryClasses(array &$classes): void
    {
        $excludePattern = '@/lib/(Migrations|Maintenance|Sitemap|Workflow|Console|Composer|Translation/(Import|Export)|Image/Optimizer|DataObject/(GridColumnConfig|Import)|Test|Tool/Transliteration|(Pimcore)\.php)@';

        $reflection = new \ReflectionClass(Bootstrap::class);
        $dir = dirname($reflection->getFileName());

        $this->getClassesFromDirectory($dir, $excludePattern, 'Pimcore', $classes);
    }

    private function modelClasses(array &$classes): void
    {
        $excludePattern = '@/models/(GridConfig|ImportConfig|Notification|Schedule|Tool/CustomReport|User|Workflow)@';

        $reflection = new \ReflectionClass(Asset::class);
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

                if (class_exists($className)) {
                    $classes[] = $className;
                }
            }
        }
    }

    private function dataObjectClasses(array &$classes): void
    {

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
    }
}
