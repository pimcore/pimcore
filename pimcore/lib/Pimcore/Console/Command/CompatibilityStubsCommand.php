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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompatibilityStubsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('compatibility-stubs')
            ->setDescription('Generate stub files for non-namespaced (before pimcore 3) class names');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = [
            PIMCORE_CLASS_DIRECTORY,
        ];
        $output = PIMCORE_WEBSITE_VAR . "/compatibility-2.x-stubs.php";

        $excludePatterns = [

        ];

        $globalMap = [];
        $map = new \stdClass();

        foreach ($paths as $path) {
            if (!empty($path)) {
                // Get the ClassFileLocator, and pass it the library path
                $this->output->writeln("current path: " . $path);
                $l = new \Zend_File_ClassFileLocator($path);

                // Iterate over each element in the path, and create a map of
                // classname => filename, where the filename is relative to the library path
                //$map    = new stdClass;
                //iterator_apply($l, 'createMap', array($l, $map));

                foreach ($l as $file) {
                    $filename  = $file->getRealpath();

                    // Windows portability
                    $filename  = str_replace(DIRECTORY_SEPARATOR, "/", $filename);

                    foreach ($file->getClasses() as $class) {
                        $allowed = true;
                        foreach ($excludePatterns as $excludePattern) {
                            if (preg_match($excludePattern, $class)) {
                                $allowed = false;
                                break;
                            }
                        }

                        if ($allowed) {
                            $map->{$class} = $filename;
                        }
                    }
                }

                $globalMap = array_merge($globalMap, (array) $map);
            }
        }

        $globalMap = (array) $globalMap;

        $content = '<' . "?" . "php \n\n";

        $processedClasses = [];

        foreach ($globalMap as $class => $file) {
            $contents = file_get_contents($file);
            $definition = "";
            if (strpos($contents, "abstract class")) {
                $definition = "abstract class";
            } elseif (strpos($contents, "class ")) {
                $definition = "class";
            } elseif (strpos($contents, "interface ")) {
                $definition = "interface";
            } else {
                continue;
            }

            $alias = str_replace("\\", "_", $class);
            $alias = preg_replace("/_Abstract(.*)/", "_Abstract", $alias);
            $alias = preg_replace("/_[^_]+Interface/", "_Interface", $alias);
            $alias = str_replace("_Listing_", "_List_", $alias);
            $alias = preg_replace("/_Listing$/", "_List", $alias);
            $alias = str_replace("Object_ClassDefinition", "Object_Class", $alias);

            if (strpos($alias, "Pimcore_Model") === 0) {
                if (!preg_match("/^Pimcore_Model_(Abstract|List|Resource|Cache)/", $alias)) {
                    $alias = str_replace("Pimcore_Model_", "", $alias);
                }
            }

            $line = "";
            if ($class != $alias && !in_array($alias, $processedClasses)) {
                $line .= "/**\n * @deprecated \n */\n";
                $line .= $definition . " " . $alias . " extends \\" . $class . " {} \n\n";
            }

            $content .= $line;

            $processedClasses[] = $alias;
        }

        // Write the contents to disk
        file_put_contents($output, $content);

        $this->output->writeln("file is located in: $output");
    }
}
