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

namespace Pimcore\Bundle\CoreBundle\Command\Migration;

use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Controller\Config\ConfigNormalizer;
use Pimcore\Db;
use Pimcore\Model\Document;
use Pimcore\Model\Document\DocType;
use Pimcore\Model\Staticroute;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'documentId',
                'd',
                InputOption::VALUE_OPTIONAL,
                'only process a particular document tree'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // document types
        $docTypes = new DocType\Listing();
        $docTypes->load();
        foreach ($docTypes->getDocTypes() as $docType) {
            $this->migrate($docType);
        }

        // static routes
        $staticRoutes = new Staticroute\Listing();
        $staticRoutes->load();

        foreach ($staticRoutes->getRoutes() as $route) {
            $this->migrate($route);
        }

        $tables = [
            'documents_page',
            'documents_snippet',
            'documents_email',
            'documents_newsletter',
            'documents_printpage',
        ];
        $queryBuilders = [];

        $db = Db::get();

        foreach ($tables as $table) {
            $queryBuilder = $db->createQueryBuilder();
            $queryBuilder
                ->select('*')
                ->from($table);

            if ($input->getOption('documentId')) {
                $document = Document::getById($input->getOption('documentId'));

                if ($document) {
                    $queryBuilder->where('(id = :id or parentId = :id)')
                        ->setParameter('id', $document->getId());
                }
            }

            $queryBuilders[$table] = $queryBuilder;
        }

        $updates = [];

        foreach ($queryBuilders as $table => $queryBuilder) {
            foreach ($queryBuilder->execute()->fetchAll() as $page) {
                $module = $page['module'];
                $controller = $page['controller'];
                $action = $page['action'];

                if (!strpos($controller, '::')) {
                    if ($action || $controller || $module) {
                        $controllerReference = $this->migrateFromString($module, $controller, $action);

                        $updates[] = [
                            'id' => $page['id'],
                            'table' => $table,
                            'values' => [
                                'controller' => $controllerReference,
                                'action' => '',
                                'module' => '',
                            ],
                        ];
                    }
                }
            }
        }

        $cacheTags = [];

        foreach ($updates as $update) {
            try {
                $db->update(
                    $update['table'],
                    $update['values'],
                    ['id' => $update['id']]
                );

                $cacheTags[] = 'document_' . $update['id'];
            } catch (\Exception $ex) {
                $this->output->writeln(sprintf('<error>%s [ID: %s]: %s</error>', $update['table'], $update['id'], $ex->getMessage()));
                $this->errors++;
            }
        }

        Cache::clearTags($cacheTags);

        if ($this->errors) {
            $output->writeln("\n\n");
            $output->writeln('<comment>There were some errors during the migration (see above in <error>red</error>), however, all possible migrations have been applied.</comment>');
            $output->writeln('<comment>You can now fix those issues if necessary and run this command again (as many times as you like, as it ignores already migrated controller references.</comment>');
        }

        return 0;
    }

    private function migrate($entity)
    {
        /**
         * @var DocType|Staticroute $entity
         */
        if (!strpos($entity->getController(), '::')) {
            if ($entity->getAction() || $entity->getController() || $entity->getModule()) {
                try {
                    $controllerReference = $this->migrateFromString($entity->getModule(), $entity->getController(), $entity->getAction());

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

    private function migrateFromString(?string $module = null, ?string $controller = null, ?string $action = null)
    {
        $controllerReference = $this->configNormalizer->formatControllerReference($module, $controller, $action);
        if (!strpos($controllerReference, '::')) {
            $controllerReference = $this->controllerNameParser->parse($controllerReference);
        }

        return $controllerReference;
    }
}
