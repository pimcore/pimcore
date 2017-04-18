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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\File;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InternalModelDaoMappingGeneratorCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setHidden(true)
            ->setName('internal:model-dao-mapping-generator')
            ->setDescription('For internal use only');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $finder
            ->files()
            ->name('/(?<!Dao)\.php$/')
            ->in(PIMCORE_PATH . "/models");

        $map = [];

        foreach($finder as $file) {
            $className = str_replace([DIRECTORY_SEPARATOR, ".php"],["\\", ""], $file->getRelativePathname());
            $className = "Pimcore\\Model\\" . $className;

            if(class_exists($className)) {

                $parents = class_parents($className);
                if(is_array($parents) && in_array("Pimcore\\Model\\AbstractModel", $parents)) {
                    $reflection = new \ReflectionClass($className);
                    if(!$reflection->isAbstract()) {
                        $daoClass = Asset::locateDaoClass($className);
                        if($daoClass) {
                            $map[$className] = $daoClass;
                        }
                    }
                }
            }
        }

        $mapFile = PIMCORE_PATH . "/config/dao-classmap.php";
        File::putPhpFile($mapFile, to_php_data_file_format($map));
    }
}
