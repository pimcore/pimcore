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

namespace Pimcore\Bundle\CoreBundle\Command\Migration;

use Pimcore\Console\AbstractCommand;
use Pimcore\Controller\Config\ConfigNormalizer;
use Pimcore\Model\Document;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Staticroute;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated
 */
class ControllerReferenceMigrationCommand extends AbstractCommand
{
    /**
     * @var ConfigNormalizer
     */
    protected $configNormalizer;

    /**
     * @var ControllerNameParser
     */
    protected $controllerNameParser;

    /**
     * @var int
     */
    private $errors = 0;

    public function __construct(ConfigNormalizer $configNormalizer, ControllerNameParser $controllerNameParser)
    {
        parent::__construct();
        $this->configNormalizer = $configNormalizer;
        $this->controllerNameParser = $controllerNameParser;
    }

    protected function configure()
    {
        $this
            ->setName('migration:controller-reference')
            ->setDescription('Migrates legacy controller references to Symfony v5 reference.')
            ->setHidden(true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // document types
        $docTypes = new DocType\Listing();
        $docTypes->load();
        foreach($docTypes->getDocTypes() as $docType) {
            $this->migrate($docType);
        }

        // static routes
        $staticRoutes = new Staticroute\Listing();
        $staticRoutes->load();

        foreach ($staticRoutes->getRoutes() as $route) {
            $this->migrate($route);
        }

        // documents
        $documents = new Document\Listing();
        $documents->setCondition('type NOT IN (:types)', ['types' => [
            'link',
            'hardlink',
            'folder',
            'printcontainer',
        ]]);

        foreach($documents as $document) {
            if($document instanceof Document\PageSnippet) {
                $this->migrate($document);
            }
        }

        if($this->errors) {
            $output->writeln("\n\n");
            $output->writeln('<comment>There were some errors during the migration (see above in <error>red</error>), however, all possible migrations have been applied.</comment>');
            $output->writeln('<comment>You can now fix those issues if necessary and run this command again (as many times as you like, as it ignores already migrated controller references.</comment>');
        }

        return 0;
    }


    private function migrate($entity) {
        /**
         * @var Document\PageSnippet|DocType|Staticroute $entity
         */
        if(!strpos($entity->getController(), '::')) {
            if($entity->getAction() || $entity->getController() || $entity->getModule()) {
                try {
                    $controllerReference = $this->configNormalizer->formatControllerReference($entity->getModule(), $entity->getController(), $entity->getAction());
                    if(!strpos($controllerReference, '::')) {
                        $controllerReference = $this->controllerNameParser->parse($controllerReference);
                    }

                    $entity->setAction(null);
                    $entity->setModule(null);
                    $entity->setController($controllerReference);
                    $entity->save();
                } catch (\Throwable $exception) {
                    $this->output->writeln(sprintf('<error>%s [ID: %s]: %s</error>', get_class($entity), $entity->getId(), $exception->getMessage()));
                    $this->errors++;
                }
            }
        }
    }
}
