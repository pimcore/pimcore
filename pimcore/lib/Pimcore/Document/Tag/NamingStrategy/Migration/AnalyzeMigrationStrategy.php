<?php

declare(strict_types=1);

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

namespace Pimcore\Document\Tag\NamingStrategy\Migration;

use Doctrine\DBAL\Connection;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\ConflictResolver;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\ElementTree;
use Pimcore\Document\Tag\NamingStrategy\Migration\Exception\NameMappingException;
use Pimcore\Model\Document;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;

class AnalyzeMigrationStrategy extends AbstractMigrationStrategy
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'analyze';
    }

    public function getStepDescription(): string
    {
        return 'Analyzing DB structure and building element name mapping...';
    }

    /**
     * @inheritdoc
     */
    public function getNameMapping(\Generator $documents, CacheInterface $cache): array
    {
        $errors  = [];
        $mapping = $cache->get('mapping', []);

        $conflictResolver = new ConflictResolver(
            $this->io,
            $this->namingStrategy
        );

        /** @var Document\PageSnippet $document */
        foreach ($documents as $document) {
            if (isset($mapping[$document->getId()])) {
                $this->io->writeln(sprintf(
                    'Loading document <info>%s</info> with ID <info>%d</info> from cache',
                    $document->getRealFullPath(),
                    $document->getId()
                ));
            } else {
                try {
                    $documentMapping = $this->processDocument($document, $conflictResolver);
                    if (!empty($documentMapping)) {
                        $mapping[$document->getId()] = $documentMapping;
                        $cache->set('mapping', $mapping);
                    }
                } catch (\Exception $e) {
                    $errors[$document->getId()] = new MappingError($document, $e);
                    $this->io->error(sprintf('Document %d: %s', $document->getId(), $e->getMessage()));
                }
            }
        }

        $this->io->newLine();

        if (count($errors) > 0) {
            $this->io->error('Not all elements could be mapped.');

            $this->showMappingErrors(
                $errors,
                'The following errors were encountered while while building element mapping:',
                'Errors can be caused by orphaned elements which do not belong to the document anymore. You can try to open a document with errors in the admin interface and trying to re-save it to cleanup orphaned elements.'
            );

            throw new NameMappingException('Aborting migration as not all elements could be mapped', 3);
        }

        $this->io->success('All elements were successfully mapped, now proceeding to update names based on the gathered mapping.');

        return $mapping;
    }

    private function processDocument(Document\PageSnippet $document, ConflictResolver $conflictResolver): array
    {
        $tree = new ElementTree($document, $conflictResolver);

        // add document elements
        $documentElements = $this->addDocumentElements($tree, $document);
        if (count($documentElements) === 0) {
            return [];
        }

        // add inherited elements from content master documents
        $master = $document->getContentMasterDocument();
        while ($master && $master instanceof Document\PageSnippet) {
            $this->addDocumentElements($tree, $master, true);
            $master = $master->getContentMasterDocument();
        }

        $elements = $tree->getElements();
        if (count($elements) !== (count($documentElements) - count($tree->getIgnoredElements()))) {
            throw new \RuntimeException('Failed to resolve the same amount of elements as fetched from DB');
        }

        $table = new Table($this->io);
        $table->setHeaders([
            [new TableCell(
                sprintf('Document <comment>%s</comment> (ID: %d)', $document->getRealFullPath(), $document->getId()),
                ['colspan' => 2]
            )],
            ['Legacy', 'Nested']
        ]);

        $mapping = [];
        foreach ($elements as $element) {
            $newName = $element->getNameForStrategy($this->namingStrategy);

            if ($newName === $element->getName()) {
                continue;
            }

            $mapping[$element->getName()] = $newName;

            $table->addRow([
                $element->getName(),
                $mapping[$element->getName()]
            ]);
        }

        if (count($mapping) > 0) {
            $table->render();
            $this->io->newLine();
        }

        return $mapping;
    }

    private function addDocumentElements(ElementTree $tree, Document\PageSnippet $document, bool $inherited = false): array
    {
        $result = $this->db->fetchAll('SELECT name, type, data FROM documents_elements WHERE documentId = :documentId', [
            'documentId' => $document->getId()
        ]);

        foreach ($result as $row) {
            $tree->add($row['name'], $row['type'], $row['data'], $inherited);
        }

        return $result;
    }
}
