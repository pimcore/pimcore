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
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\ElementTree;
use Pimcore\Document\Tag\NamingStrategy\Migration\Exception\NameMappingException;
use Pimcore\Model\Document;
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
    public function getNameMapping(\Generator $documents): array
    {
        $errors  = [];
        $mapping = [];

        /** @var Document\PageSnippet $document */
        foreach ($documents as $document) {
            try {
                $documentMapping = $this->processDocument($document);
                if (!empty($documentMapping)) {
                    $mapping[$document->getId()] = $documentMapping;
                }
            } catch (\Exception $e) {
                $errors[$document->getId()] = new MappingError($document, $e);
                $this->io->error(sprintf('Document %d: %s', $document->getId(), $e->getMessage()));
            }
        }

        $this->io->writeln('');

        if (count($errors) === 0) {
            $this->io->success('All elements were successfully mapped, now proceeding to update names based on the gathered mapping.');
        } else {
            $this->io->warning('Errors were encountered while building element mapping.');

            $confirmation = $this->confirmProceedAfterRenderingErrors(
                $errors,
                'The following errors were encountered while mapping elements for the selected documents:',
                '<comment>WARNING:</comment> You can proceed the migration for all other documents, but your unmigrated documents will potentially lose their data. It\'s strongly advised to fix any issues before proceeding. Errors can be caused by orphaned elements which do not belong to the document anymore. You can try to open a document with errors in the admin interface and trying to re-save it to cleanup orphaned elements.',
                'Proceed the migration for successfully mapped elements?'
            );

            if (!$confirmation) {
                throw new NameMappingException('Aborting migration as not all elements could be mapped', 3);
            }
        }

        $mapping = $this->removeMappingForErroredDocuments($mapping, $errors);

        return $mapping;
    }

    private function processDocument(Document $document): array
    {
        $result = $this->db->fetchAll('SELECT name, type, data FROM documents_elements WHERE documentId = :documentId', [
            'documentId' => $document->getId()
        ]);

        $table = new Table($this->io->getOutput());
        $table->setHeaders([
            [new TableCell('Document ' . $document->getId(), ['colspan' => 2])],
            ['Legacy', 'Nested']
        ]);

        $tree = new ElementTree();
        foreach ($result as $row) {
            $tree->add($row['name'], $row['type'], $row['data']);
        }

        $mapping = [];
        foreach ($result as $row) {
            $element = $tree->getElement($row['name']);
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

        $table->render();

        return $mapping;
    }
}
